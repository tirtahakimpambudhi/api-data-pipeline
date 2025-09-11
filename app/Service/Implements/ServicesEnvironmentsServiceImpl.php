<?php

namespace App\Service\Implements;

use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\ServiceEnvironment\CreateServiceEnvironmentRequest;
use App\Http\Requests\ServiceEnvironment\UpdateServiceEnvironmentRequest;
use App\Models\ServicesEnvironments;
use App\Service\Contracts\ServicesEnvironmentsService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ServicesEnvironmentsServiceImpl implements ServicesEnvironmentsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        protected ServicesEnvironments $model,
        protected Logger $logger
    ) {
        $this->guard = $authFactory->guard('web');
    }

    protected function checkPermission(string $action): void
    {
        $user = $this->guard->user();
        if (!$user) throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        if (!$user->hasPermission('services_environments', $action)) {
            throw new PermissionDeniedServiceException("User does not have permission to {$action} services_environments.");
        }
    }

    protected function getOrFail(int $id): ServicesEnvironments
    {
        $row = $this->model->newQuery()->find($id);
        if (!$row) throw new NotFoundServiceException("ServiceEnvironment not found with id {$id}.");
        return $row;
    }

    public function getAll(PaginationRequest $data): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission('read');
            $value = $data->validated();
            $page  = (int)($value['page'] ?? 0);
            $size  = (int)($value['size'] ?? 0);

            $query = $this->model->newQuery()->with(['service.namespace','environment','configurations.channel']);

            if ($page > 0 && $size > 0) return $this->applyPagination($query, $page, $size);
            return $query->get();
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to load services_environments. Please try again later.'); }
    }

    public function search(SearchPaginationRequest $data): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission('read');
            $value = $data->validated();
            $term  = trim((string)($value['search'] ?? ''));
            $page  = (int)($value['page'] ?? 0);
            $size  = (int)($value['size'] ?? 0);

            $serviceId = $value['service_id'] ?? null;
            $envId     = $value['environment_id'] ?? null;

            $query = $this->model->newQuery()->with(['service.namespace','environment','configurations.channel']);

            if ($serviceId) {
                $query->where('service_id', (int)$serviceId);
            }
            if ($envId) {
                $query->where('environment_id', (int)$envId);
            }

            // Pencarian dengan join relasi (nama namespace/service/environment)
            if ($term !== '') {
                $query->where(function($q) use ($term) {
                    $q->whereHas('service', fn($sq) => $sq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('service.namespace', fn($nq) => $nq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('environment', fn($eq) => $eq->whereLike('name', "%{$term}%"));
                });
            }

            if ($page > 0 && $size > 0) return $this->applyPagination($query, $page, $size);
            return $query->get();
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to search services_environments. Please try again later.'); }
    }

    public function create(CreateServiceEnvironmentRequest $data): Collection
    {
        try {
            $this->checkPermission('create');
            $value = $data->validated();
            $row   = $this->model->newQuery()->create([
                'service_id'     => (int)$value['service_id'],
                'environment_id' => (int)$value['environment_id'],
            ]);
            return collect($row->load(['service.namespace','environment']));
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("The pair (service_id, environment_id) already exists.");
            }
            throw new InternalServiceException("Error when creating service_environment: {$e->getMessage()}");
        } catch (\Throwable $e) {
            throw new InternalServiceException('Failed to create service_environment. Please try again later.');
        }
    }

    public function update(int $id, UpdateServiceEnvironmentRequest $data): Collection
    {
        try {
            $this->checkPermission('update');
            $row = $this->getOrFail($id);
            $value = $data->validated();

            $payload = [];
            if (array_key_exists('service_id', $value)) {
                $payload['service_id'] = (int)$value['service_id'];
            }
            if (array_key_exists('environment_id', $value)) {
                $payload['environment_id'] = (int)$value['environment_id'];
            }

            if (!empty($payload)) $row->fill($payload)->save();
            return collect($row->fresh()->load(['service.namespace','environment']));
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("The pair (service_id, environment_id) already exists.");
            }
            throw new InternalServiceException("Error when updating service_environment: {$e->getMessage()}");
        } catch (\Throwable $e) {
            throw new InternalServiceException('Failed to update service_environment. Please try again later.');
        }
    }

    public function delete(int $id): Collection
    {
        try {
            $this->checkPermission('delete');
            $row = $this->getOrFail($id);
            $row->delete();
            return collect(['id'=>$id, 'deleted'=>true]);
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) { throw new InternalServiceException("Error when deleting service_environment: {$e->getMessage()}"); }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to delete service_environment. Please try again later.'); }
    }

    public function getById(int $id): Collection
    {
        try {
            $this->checkPermission('read');
            $row = $this->model->newQuery()
                ->with(['service.namespace','environment','configurations.channel'])
                ->find($id);
            if (!$row) throw new NotFoundServiceException("ServiceEnvironment not found with id {$id}.");
            return collect($row);
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to load service_environment. Please try again later.'); }
    }
}
