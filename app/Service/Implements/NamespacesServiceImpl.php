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
use App\Http\Requests\Namespaces\CreateServiceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Models\Namespaces;
use App\Service\Contracts\NamespacesService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NamespacesServiceImpl implements NamespacesService
{
    use Helpers;
    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        protected Namespaces $model,
        protected Logger $logger
    ) {
        $this->guard = $authFactory->guard('web');
    }

    /**
     * Return paginated or full collection (when page/size omitted or <=0).
     */
    public function getAll(PaginationRequest $data): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission("read");

            $this->logger->info("Start of getAll namespaces (service layer)");

            $value = $data->validated();
            $page  = (int)($value['page'] ?? 0);
            $size  = (int)($value['size'] ?? 0);

            $query = $this->model->newQuery()->with(['services', 'servicesEnvironments']);

            if ($page > 0 && $size > 0) {
                return $this->applyPagination(query: $query, page: $page, size: $size);
            }


            $this->logger->info("Apply non-paginated query");
            $rows = $query->get();
            $this->logger->info("Successfully fetched namespaces (non-paginated), count={$rows->count()}");
            return $rows;
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException: {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in getAll: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to load namespaces. Please try again later.');
        }
    }

    /**
     * Same pattern as getAll, with LIKE filter by name.
     */
    public function search(SearchPaginationRequest $data): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission("read");

            $this->logger->info("Start of search namespaces (service layer)");

            $value       = $data->validated();
            $searchValue = trim((string)($value['search'] ?? ''));
            $page        = (int)($value['page'] ?? 0);
            $size        = (int)($value['size'] ?? 0);

            $query = $this->model->newQuery()->with(['services', 'servicesEnvironments']);

            if ($searchValue !== '') {
                $query->whereLike('name',  "%{$searchValue}%");
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
            throw new InternalServiceException('Failed to search namespaces. Please try again later.');
        }
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

        if (!$user->hasPermission('namespaces', $action)) {
            $this->logger->warning("{$user->name} does not have permission to {$action} namespaces");
            throw new PermissionDeniedServiceException("User does not have permission to {$action} namespaces.");
        }
    }

    public function getNamespaceOrFail(int $id) : Namespaces
    {
        $namespace = $this->model->newQuery()->find($id);
        if (!$namespace) {
            $this->logger->error("Namespace not found id={$id}");
            throw new NotFoundServiceException("Namespace not found with id {$id}.");
        }
        return $namespace;
    }

    public function create(CreateNamespaceRequest $data): Collection
    {
        try {
            $this->checkPermission("create");

            $this->logger->info("Start of create namespace (service layer)");

            $value = $data->validated();
            $name  = (string)($value['name'] ?? '');

            $this->logger->info("Attempt to create namespace with name='{$name}'");

            $namespace = $this->model->newQuery()->create(['name' => $name]);

            $this->logger->info("Namespace created with id={$namespace->id}");

            return collect($namespace);
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (create): {$e->getMessage()}");
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error("QueryException (create): {$e->getMessage()}");
            // 23000 = integrity constraint violation (duplicate, FK, unique, dll)
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Namespace with name '{$data->validated()['name']}' already exists.");
            }
            throw new InternalServiceException("Error when creating namespace: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in create: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to create namespace. Please try again later.');
        }
    }

    /**
     * Update only provided fields.
     * If 'name' empty or not provided, it will be skipped.
     */
    public function update(int $id, UpdateNamespaceRequest $data): Collection
    {
        try {
            $this->checkPermission("update");

            $this->logger->info("Start of update namespace id={$id} (service layer)");

            $namespace = $this->getNamespaceOrFail($id);

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

            if (empty($payload)) {
                $this->logger->info("No updatable fields provided for id={$id}; skipping update");
                return collect( $namespace->fresh());
            }

            $this->logger->info("Applying update for id={$id}", $payload);
            $namespace->fill($payload);
            $namespace->save();

            $this->logger->info("Successfully updated namespace id={$id}");

            return collect($namespace->fresh());
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (update): {$e->getMessage()}");
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error("QueryException (update): {$e->getMessage()}");
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Namespace name already exists.");
            }
            throw new InternalServiceException("Error when updating namespace: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in update: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to update namespace. Please try again later.');
        }
    }

    public function delete(int $id): Collection
    {
        try {
            $this->checkPermission("delete");

            $this->logger->info("Start of delete namespace id={$id} (service layer)");

            $namespace = $this->getNamespaceOrFail($id);

            $this->logger->info("Deleting namespace id={$id}");
            $namespace->delete();

            $this->logger->info("Successfully deleted namespace id={$id}");

            return collect(['id' => $id, 'deleted' => true]);
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (delete): {$e->getMessage()}");
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error("QueryException (delete): {$e->getMessage()}");
            throw new InternalServiceException("Error when deleting namespace: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in delete: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to delete namespace. Please try again later.');
        }
    }

    public function getById(int $id): Collection
    {
        try {
            $this->checkPermission("read");

            $this->logger->info("Start of getById namespace id={$id} (service layer)");

            $namespace = $this->model->newQuery()
                ->with(['services.configurations', 'servicesEnvironments', 'services.environments'])
                ->find($id);

            if (!$namespace) {
                $this->logger->error("Namespace not found id={$id}");
                throw new NotFoundServiceException("Namespace not found with id {$id}.");
            }

            $this->logger->info("Successfully fetched namespace id={$id}");
            return collect($namespace);
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (getById): {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in getById: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to load namespace. Please try again later.');
        }
    }

    public function createService(int $id, CreateServiceRequest $data): Collection
    {
        try {
            $user = $this->guard->user();

            if (!$user) {
                $this->logger->warning("User not authenticated");
                throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
            }

            if (!$user->hasPermission('services', "create")) {
                $this->logger->warning("{$user->name} does not have permission to create services");
                throw new PermissionDeniedServiceException("User does not have permission to create services.");
            }

            $this->logger->info("Start of create service with namespace id={$id} (service layer)");

            $value = $data->validated();
            $nameSvc = trim((string)$value['name']);

            $namespace = $this->getNamespaceOrFail($id);

            $namespace->services()->create([ 'name' => $nameSvc ]);
            $namespace->loadMissing('services');
            $this->logger->info("Successfully created service with namespace id={$id}");

            return collect( $namespace->refresh());
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (createService): {$e->getMessage()}");
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error("QueryException (createService): {$e->getMessage()}");
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Service name already exists.");
            }
            throw new InternalServiceException("Error when create service: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in create service: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to create service. Please try again later.');
        }
    }
}
