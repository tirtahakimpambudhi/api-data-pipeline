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
use App\Http\Requests\Namespaces\CreateNamespaceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Http\Requests\Services\CreateServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Models\Services;
use App\Service\Contracts\ServicesService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Log\Logger;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
class ServicesServiceImpl implements ServicesService
{
    use Helpers;
    protected StatefulGuard|Guard $guard;
    /**
     * Create a new class instance.
     */
    public function __construct(
        AuthFactory $authFactory,
        protected Services $model,
        protected Logger $logger
    )
    {
        $this->guard = $authFactory->guard('web');
    }


    /**
     * @param string $action
     * @return void
     * @throws PermissionDeniedServiceException
     * @throws UnauthorizedServiceException
     */
    public function checkPermission(string $action): void
    {
        $user = $this->guard->user();

        if (!$user) {
            $this->logger->warning("User not authenticated");
            throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        }

        if (!$user->hasPermission('services', $action)) {
            $this->logger->warning("{$user->name} does not have permission to {$action} services");
            throw new PermissionDeniedServiceException("User does not have permission to {$action} services.");
        }
    }

    public function getServiceOrFail(int $id) : Services
    {
        $service = $this->model->newQuery()->find($id);
        if (!$service) {
            $this->logger->error("Service not found id={$id}");
            throw new NotFoundServiceException("Service not found with id {$id}.");
        }
        return $service;
    }


    public function getAll(PaginationRequest | null $data, bool $onlyService = false): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission("read");

            $this->logger->info("Start of getAll services (service layer)");

            $page = 0;
            $size = 0;
            if ($data !== null) {
                $value = $data->validated();
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);
            }

            $query = $this->model->newQuery();

            if (!$onlyService) {
                $query->with(['configurations', 'namespace', 'servicesEnvironments', 'environments']);
            }

            if ($page > 0 && $size > 0) {
                return $this->applyPagination(query: $query, page: $page, size: $size);
            }

            $this->logger->info("Apply non-paginated query");
            $rows = $query->get();
            $this->logger->info("Successfully fetched services (non-paginated), count={$rows->count()}");
            return $rows;
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException: {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in getAll: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to load services. Please try again later.');
        }
    }


    public function getById(int $id): Collection
    {
        try {
            $this->checkPermission("read");

            $this->logger->info("Start of getById services id={$id} (service layer)");

            $svc = $this->model->newQuery()
                ->with(['configurations', 'namespace', 'servicesEnvironments.channels', 'environments'])
                ->find($id);

            if (!$svc) {
                $this->logger->error("Services not found id={$id}");
                throw new NotFoundServiceException("Services not found with id {$id}.");
            }

            $this->logger->info("Successfully fetched services id={$id}");
            return collect($svc);
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (getById): {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in getById: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to load services. Please try again later.');
        }
    }

    public function search(SearchPaginationRequest | null $data, bool $onlyService = false): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission("read");

            $this->logger->info("Start of search services (service layer)");

            $searchValue = '';
            $page = 0;
            $size = 0;

            if ($data !== null) {
                $value       = $data->validated();
                $searchValue = trim((string)($value['search'] ?? ''));
                $page        = (int)($value['page'] ?? 0);
                $size        = (int)($value['size'] ?? 0);
            }

            $query = $this->model->newQuery();

            if (!$onlyService) {
                $query->with(['configurations', 'namespace', 'servicesEnvironments', 'environments']);
            }

            if ($searchValue !== '') {
                $query->whereLike('name', "%{$searchValue}%");
            }
            if ($page > 0 && $size > 0) {
                return $this->applyPagination(query: $query, page: $page, size: $size);
            }
            $rows = $query->get();
            $this->logger->info("Search non-paginated fetched count={$rows->count()} term='{$searchValue}'");
            return $rows;
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (search): {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in search: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to search services. Please try again later.');
        }
    }

    public function create(CreateServiceRequest $data): Collection
    {
        try {
            $this->checkPermission("create");

            $this->logger->info("Start of create services (service layer)");

            $value = $data->validated();
            $name  = (string)($value['name'] ?? '');
            $namespaceId  = (string)($value['namespace_id'] ?? '');

            $this->logger->info("Attempt to create service with name='{$name}'");

            $svc = $this->model->newQuery()->create(['name' => $name, 'namespace_id' => $namespaceId]);
            $svc->loadMissing([
                'namespace',
                'environments',
                'configurations',
            ]);
            $this->logger->info("Services created with id={$svc->id}");

            return collect($svc);
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (create): {$e->getMessage()}");
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error("QueryException (create): {$e->getMessage()}");
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Services with name '{$data->validated()['name']}' already exists.");
            }
            throw new InternalServiceException("Error when creating services: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in create: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to create services. Please try again later.');
        }
    }

    public function update(int $id, UpdateServiceRequest $data): Collection
    {
        try {
            $this->checkPermission("update");

            $this->logger->info("Start of update services id={$id} (service layer)");

            $svc = $this->getServiceOrFail($id);

            $value = $data->validated();
            $payload = [];

            if (array_key_exists('name', $value)) {
                $name = trim((string)$value['name']);
                if ($name !== '') {
                    $payload['name'] = $name;
                } else {
                    $this->logger->info("Update skip 'name' (empty input) for id={$id}");
                }
            }

            if (array_key_exists('namespace_id', $value)) {
                $namespaceId = trim((int)$value['namespace_id']);
                if ($namespaceId != 0) {
                    $payload['namespace_id'] = $namespaceId;
                } else {
                    $this->logger->info("Update skip 'namespace_id' (empty input) for id={$id}");
                }
            }

            if (empty($payload)) {
                $this->logger->info("No updatable fields provided for id={$id}; skipping update");
                return collect( $svc->fresh());
            }

            $this->logger->info("Applying update for id={$id}", $payload);
            $svc->fill($payload);
            $svc->save();

            $this->logger->info("Successfully updated services id={$id}");

            return collect($svc->fresh());
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (update): {$e->getMessage()}");
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error("QueryException (update): {$e->getMessage()}");
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Namespace name already exists.");
            }
            throw new InternalServiceException("Error when updating services: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in update: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to update services. Please try again later.');
        }
    }

    public function delete(int $id): Collection
    {
        try {
            $this->checkPermission("delete");

            $this->logger->info("Start of delete services id={$id} (service layer)");

            $svc = $this->getServiceOrFail($id);

            $this->logger->info("Deleting services id={$id}");
            $svc->delete();

            $this->logger->info("Successfully deleted services id={$id}");

            return collect(['id' => $id, 'deleted' => true]);
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (delete): {$e->getMessage()}");
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error("QueryException (delete): {$e->getMessage()}");
            throw new InternalServiceException("Error when deleting services: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in delete: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to delete services. Please try again later.');
        }
    }

}
