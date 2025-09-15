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
use App\Http\Requests\Configurations\CreateConfigurationRequest;
use App\Http\Requests\Configurations\UpdateConfigurationRequest;
use App\Models\Configurations;
use App\Service\Contracts\ConfigurationsService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ConfigurationsServiceImpl implements ConfigurationsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        protected Configurations $model,
        protected Logger $logger
    ) {
        $this->guard = $authFactory->guard('web');
    }

    protected function checkPermission(string $action): void
    {
        $user = $this->guard->user();
        if (!$user) throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        if (!$user->hasPermission(ResourcesTypes::CONFIGURATIONS, $action)) {
            throw new PermissionDeniedServiceException("User does not have permission to {$action} configurations.");
        }
    }

    protected function getOrFail(int $id): Configurations
    {
        $row = $this->model->newQuery()->find($id);
        if (!$row) throw new NotFoundServiceException("Configuration not found with id {$id}.");
        return $row;
    }

    public function getAll(PaginationRequest | null $data, bool $onlyConf = false): LengthAwarePaginator|Collection
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
            if (!$onlyConf) {
                $query->with(['serviceEnvironment.service.namespace','serviceEnvironment.environment','channel']);
            };

            if ($page > 0 && $size > 0) return $this->applyPagination($query, $page, $size);
            return $query->get();
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to load configurations. Please try again later.'); }
    }

    public function search(SearchPaginationRequest | null $data, bool $onlyConf = false): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission(ActionsTypes::READ);
            $term   = '';
            $page = 0;
            $size = 0;
            if ($data !== null) {
                $value = $data->validated();
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);
                $term   = trim((string)($value['search'] ?? ''));
            }

            $query = $this->model->newQuery();

            if (!$onlyConf) {
                $query->with(['serviceEnvironment.service.namespace','serviceEnvironment.environment','channel']);
            };

            if ($term !== '') {
                $query->where(function($q) use ($term) {
                    $q->whereHas('channel', fn($cq) => $cq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('serviceEnvironment.service', fn($sq) => $sq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('serviceEnvironment.environment', fn($eq) => $eq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('serviceEnvironment.service.namespace', fn($nq) => $nq->whereLike('name', "%{$term}%"));
                });
            }

            if ($page > 0 && $size > 0) return $this->applyPagination($query, $page, $size);
            return $query->get();
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to search configurations. Please try again later.'); }
    }

    public function create(CreateConfigurationRequest $data): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::CREATE);
            $value = $data->validated();

            $row   = $this->model->newQuery()->create([
                'service_environment_id' => (int)$value['service_environment_id'],
                'channel_id'             => (int)$value['channel_id'],
            ]);

            return collect($row->load(['serviceEnvironment.service.namespace','serviceEnvironment.environment','channel']));
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("The pair (service_environment_id, channel_id) already exists.");
            }
            throw new InternalServiceException("Error when creating configuration: {$e->getMessage()}");
        } catch (\Throwable $e) {
            throw new InternalServiceException('Failed to create configuration. Please try again later.');
        }
    }

    public function update(int $id, UpdateConfigurationRequest $data): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::UPDATE);
            $row = $this->getOrFail($id);
            $value = $data->validated();

            $payload = [];
            if (array_key_exists('service_environment_id', $value)) {
                $payload['service_environment_id'] = (int)$value['service_environment_id'];
            }
            if (array_key_exists('channel_id', $value)) {
                $payload['channel_id'] = (int)$value['channel_id'];
            }

            if (!empty($payload)) $row->fill($payload)->save();
            return collect($row->fresh()->load(['serviceEnvironment.service.namespace','serviceEnvironment.environment','channel']));
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("The pair (service_environment_id, channel_id) already exists.");
            }
            throw new InternalServiceException("Error when updating configuration: {$e->getMessage()}");
        } catch (\Throwable $e) {
            throw new InternalServiceException('Failed to update configuration. Please try again later.');
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
        catch (QueryException $e) { throw new InternalServiceException("Error when deleting configuration: {$e->getMessage()}"); }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to delete configuration. Please try again later.'); }
    }

    public function getById(int $id): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::READ);
            $row = $this->model->newQuery()
                ->with(['serviceEnvironment.service.namespace','serviceEnvironment.environment','channel'])
                ->find($id);
            if (!$row) throw new NotFoundServiceException("Configuration not found with id {$id}.");
            return collect($row);
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to load configuration. Please try again later.'); }
    }
}
