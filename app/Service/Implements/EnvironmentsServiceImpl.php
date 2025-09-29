<?php

namespace App\Service\Implements;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Environments\CreateEnvironmentRequest;
use App\Http\Requests\Environments\UpdateEnvironmentRequest;
use App\Models\Environments;
use App\Service\Contracts\EnvironmentsService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EnvironmentsServiceImpl implements EnvironmentsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        protected Environments $model,
        protected Logger $logger
    ) {
        $this->guard = $authFactory->guard('web');
    }

    protected function checkPermission(string $action): void
    {
        $user = $this->guard->user();
        if (!$user) {
            $this->logger->warning("User not authenticated");
            throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        }
        if (!$user->hasPermission(ResourcesTypes::ENVIRONMENTS, $action)) {
            $this->logger->warning("{$user->name} does not have permission to {$action} environments");
            throw new PermissionDeniedServiceException("User does not have permission to {$action} environments.");
        }
    }

    protected function getOrFail(int $id): Environments
    {
        $row = $this->model->newQuery()->find($id);
        if (!$row) {
            $this->logger->error("Environment not found id={$id}");
            throw new NotFoundServiceException("Environment not found with id {$id}.");
        }
        return $row;
    }

    public function getAll(PaginationRequest | null $data, bool $onlyEnv = false): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission(ActionsTypes::READ);

            $page = 0;
            $size = 0;
            if ($data !== null) {
                $value = $data->validated();
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);
            }

            $query = $this->model->newQuery();

            if (!$onlyEnv) {
                $query->with(['services', 'servicesEnvironments', 'configurations']);
            }

            if ($page > 0 && $size > 0) {
                return $this->applyPagination($query, $page, $size);
            }
            return $query->get();
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in Environments.getAll: {$e->getMessage()}", ['exception'=>$e]);
            throw new InternalServiceException('Failed to load environments. Please try again later.');
        }
    }

    public function search(SearchPaginationRequest | null $data, bool $onlyEnv = false): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission(ActionsTypes::READ);
            $page = 0;
            $size = 0;
            $term = '';
            if ($data !== null) {
                $value = $data->validated();
                $term  = trim((string)($value['search'] ?? ''));
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);
            }

            $query = $this->model->newQuery();
            if (!$onlyEnv) {
                $query->with(['services', 'servicesEnvironments', 'configurations']);
            }
            if ($term !== '') {
                $query->whereLike('name', "%{$term}%");
            }

            if ($page > 0 && $size > 0) {
                return $this->applyPagination($query, $page, $size);
            }
            return $query->get();
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in Environments.search: {$e->getMessage()}", ['exception'=>$e]);
            throw new InternalServiceException('Failed to search environments. Please try again later.');
        }
    }

    public function create(CreateEnvironmentRequest $data): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::CREATE);
            $value = $data->validated();
            $row   = $this->model->newQuery()->create(['name' => (string)$value['name']]);
            return collect($row);
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Environment with name '{$data->validated()['name']}' already exists.");
            }
            throw new InternalServiceException("Error when creating environment: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in Environments.create: {$e->getMessage()}", ['exception'=>$e]);
            throw new InternalServiceException('Failed to create environment. Please try again later.');
        }
    }

    public function update(int $id, UpdateEnvironmentRequest $data): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::UPDATE);
            $row = $this->getOrFail($id);

            $value = $data->validated();
            $payload = [];
            if (array_key_exists('name', $value)) {
                $name = trim((string)$value['name']);
                if ($name !== '') $payload['name'] = $name;
            }

            if (!empty($payload)) {
                $row->fill($payload)->save();
            }
            return collect($row->fresh());
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Environment name already exists.");
            }
            throw new InternalServiceException("Error when updating environment: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in Environments.update: {$e->getMessage()}", ['exception'=>$e]);
            throw new InternalServiceException('Failed to update environment. Please try again later.');
        }
    }

    public function delete(int $id): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::DELETE);
            $row = $this->getOrFail($id);
            $row->delete();
            return collect(['id'=>$id, 'deleted'=>true]);
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            throw new InternalServiceException("Error when deleting environment: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in Environments.delete: {$e->getMessage()}", ['exception'=>$e]);
            throw new InternalServiceException('Failed to delete environment. Please try again later.');
        }
    }

    public function getById(int $id): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::READ);
            $row = $this->model->newQuery()
                ->with(['services', 'servicesEnvironments.service', 'configurations.channel'])
                ->find($id);
            if (!$row) {
                throw new NotFoundServiceException("Environment not found with id {$id}.");
            }
            return collect($row);
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in Environments.getById: {$e->getMessage()}", ['exception'=>$e]);
            throw new InternalServiceException('Failed to load environment. Please try again later.');
        }
    }
}
