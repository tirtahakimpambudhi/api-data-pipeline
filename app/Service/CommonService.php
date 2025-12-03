<?php

namespace App\Service;

use App\Constants\ActionsTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\InvalidArgumentServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Log\Logger;
use Illuminate\Support\Collection;

abstract class CommonService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    /**
     * @param AuthFactory $authFactory
     * @param Model       $model                Base model instance (used for table name & newQuery)
     * @param string      $permissions          Resource type (mis. ResourcesTypes::ROLES)
     * @param Logger      $logger               PSR-3 logger
     * @param array       $relationshipsTable   Default relationships for listing
     */
    public function __construct(
        AuthFactory $authFactory,
        protected Model $model,
        protected string $permissions,
        protected Logger $logger,
        protected array $relationshipsTable = [],
    ) {
        $this->guard = $authFactory->guard('web');

        $this->logger->debug('CommonService initialized', [
            'table'       => $this->model->getTable(),
            'permissions' => $this->permissions,
            'relations'   => $this->relationshipsTable,
        ]);
    }

    abstract protected function createModel(array $value): Model;

    abstract protected function handleErrorCreate(QueryException $exception, array $value): AppServiceException;
    abstract protected function handleErrorUpdate(QueryException $exception, array $value): AppServiceException;

    abstract protected function updateModel(array $value, Model $model): void;

    abstract protected function searchModel(Builder $query, string $value);


    abstract protected function getRelationshipsByID(): array;


    protected function checkPermission(string $action): void
    {
        $user = $this->guard->user();

        if (!$user) {
            $this->logger->warning('Permission check failed: user not authenticated', [
                'action' => $action,
                'table'  => $this->model->getTable(),
            ]);
            throw new UnauthorizedServiceException('User not authenticated, must be logged in.');
        }

        $this->logger->debug('Checking user permission', [
            'user_id'     => $user->id ?? null,
            'user_name'   => $user->name ?? null,
            'resource'    => $this->permissions,
            'action'      => $action,
            'table'       => $this->model->getTable(),
        ]);

        if (!$user->hasPermission($this->permissions, $action)) {
            $this->logger->warning('User does not have required permission', [
                'user_id'   => $user->id ?? null,
                'user_name' => $user->name ?? null,
                'resource'  => $this->permissions,
                'action'    => $action,
                'table'     => $this->model->getTable(),
            ]);

            throw new PermissionDeniedServiceException(
                "User does not have permission to {$action} {$this->model->getTable()}."
            );
        }
    }


    protected function getOrFail(int $id): Model
    {
        $this->logger->debug('Fetching single record', [
            'table' => $this->model->getTable(),
            'id'    => $id,
        ]);

        $row = $this->model->newQuery()->find($id);

        if (!$row) {
            $this->logger->error("Record not found", [
                'table' => $this->model->getTable(),
                'id'    => $id,
            ]);

            throw new NotFoundServiceException("{$this->model->getTable()} not found with id {$id}.");
        }

        return $row;
    }

    /**
     * List data (with optional pagination)
     */
    public function getAll(PaginationRequest|null $data, bool $onlyModel = false): LengthAwarePaginator|Collection
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

            $this->logger->info('Fetching list of records', [
                'table'         => $this->model->getTable(),
                'page'          => $page,
                'size'          => $size,
                'with_relation' => !$onlyModel,
            ]);

            $query = $this->model->newQuery();

            if (!$onlyModel && !empty($this->relationshipsTable)) {
                $query->with($this->relationshipsTable);
            }

            if ($page > 0 && $size > 0) {
                $result = $this->applyPagination($query, $page, $size);

                $this->logger->info('Fetched paginated records', [
                    'table' => $this->model->getTable(),
                    'page'  => $page,
                    'size'  => $size,
                    'total' => $result->total(),
                ]);

                return $result;
            }

            $result = $query->get();

            $this->logger->info('Fetched all records (no pagination)', [
                'table' => $this->model->getTable(),
                'count' => $result->count(),
            ]);

            return $result;
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error(
                "Unhandled exception in {$this->model->getTable()}.getAll",
                ['exception' => $e]
            );

            throw new InternalServiceException(
                "Failed to load {$this->model->getTable()}. Please try again later."
            );
        }
    }


    public function search(SearchPaginationRequest|null $data, bool $onlyModel = false): LengthAwarePaginator|Collection
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

            $this->logger->info('Searching records', [
                'table'         => $this->model->getTable(),
                'term'          => $term,
                'page'          => $page,
                'size'          => $size,
                'with_relation' => !$onlyModel,
            ]);

            $query = $this->model->newQuery();

            if (!$onlyModel && !empty($this->relationshipsTable)) {
                $query->with($this->relationshipsTable);
            }

            $this->searchModel($query, $term);

            if ($page > 0 && $size > 0) {
                $result = $this->applyPagination($query, $page, $size);

                $this->logger->info('Search with pagination completed', [
                    'table' => $this->model->getTable(),
                    'term'  => $term,
                    'page'  => $page,
                    'size'  => $size,
                    'total' => $result->total(),
                ]);

                return $result;
            }

            $result = $query->get();

            $this->logger->info('Search without pagination completed', [
                'table' => $this->model->getTable(),
                'term'  => $term,
                'count' => $result->count(),
            ]);

            return $result;
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error(
                "Unhandled exception in {$this->model->getTable()}.search",
                ['exception' => $e]
            );

            throw new InternalServiceException(
                "Failed to search {$this->model->getTable()}. Please try again later."
            );
        }
    }

    /**
     * Create data
     */
    public function create(FormRequest $data): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::CREATE);

            $value = $data->validated();

            $this->logger->info("Creating new {$this->model->getTable()}", [
                'table'   => $this->model->getTable(),
            ]);

            $row = $this->createModel($value);

            $this->logger->info('Record created successfully', [
                'table' => $this->model->getTable(),
                'id'    => $row->getKey(),
            ]);

            return collect($row);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            $this->logger->error("Invalid argument provided", [
                'message' => $e->getMessage(),
            ]);
            throw new InvalidArgumentServiceException($e->getMessage());
        } catch (QueryException $e) {
            throw $this->handleErrorCreate($e, $data->validated());
        } catch (\Throwable $e) {
            $this->logger->error(
                "Unhandled exception in {$this->model->getTable()}.create",
                ['exception' => $e]
            );

            throw new InternalServiceException(
                "Failed to create {$this->model->getTable()}. Please try again later."
            );
        }
    }

    /**
     * Update data
     */
    public function update(int $id, FormRequest $data): Collection
    {
        $this->logger->info("Updating {$this->model->getTable()}", ['id' => $id]);
        try {
            $this->checkPermission(ActionsTypes::UPDATE);

            $this->logger->info("Updating {$this->model->getTable()} record", [
                'table' => $this->model->getTable(),
                'id'    => $id,
            ]);

            $row   = $this->getOrFail($id);
            $value = $data->validated();

            $this->logger->debug('Update payload preview', [
                'table'   => $this->model->getTable(),
                'id'      => $id,
                'preview' => array_intersect_key($value, array_flip(['name'])),
            ]);

            $this->updateModel($value, $row);

            $fresh = $row->fresh();

            $this->logger->info('Record updated successfully', [
                'table' => $this->model->getTable(),
                'id'    => $fresh?->getKey(),
            ]);

            return collect($fresh);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\InvalidArgumentException $e) {
            $this->logger->error("Invalid argument provided", [
                'message' => $e->getMessage(),
            ]);
            throw new InvalidArgumentServiceException($e->getMessage());
        }  catch (QueryException $e) {
            throw $this->handleErrorUpdate($e, $data->validated());
        } catch (\Throwable $e) {
            $this->logger->error(
                "Unhandled exception in {$this->model->getTable()}.update",
                ['exception' => $e]
            );

            throw new InternalServiceException(
                "Failed to update {$this->model->getTable()}. Please try again later."
            );
        }
    }

    /**
     * Delete data
     */
    public function delete(int $id): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::DELETE);

            $this->logger->info('Deleting record', [
                'table' => $this->model->getTable(),
                'id'    => $id,
            ]);

            $row = $this->getOrFail($id);
            $row->delete();

            $this->logger->info('Record deleted', [
                'table' => $this->model->getTable(),
                'id'    => $id,
            ]);

            return collect([
                'id'      => $id,
                'deleted' => true,
            ]);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error('QueryException on delete', [
                'table'   => $this->model->getTable(),
                'id'      => $id,
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            throw new InternalServiceException(
                "Error when deleting {$this->model->getTable()}: {$e->getMessage()}"
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                "Unhandled exception in {$this->model->getTable()}.delete",
                ['exception' => $e]
            );

            throw new InternalServiceException(
                "Failed to delete {$this->model->getTable()}. Please try again later."
            );
        }
    }

    /**
     * Detail by ID
     */
    public function getById(int $id): Collection
    {
        try {
            $this->checkPermission(ActionsTypes::READ);

            $this->logger->info('Fetching record by id', [
                'table'       => $this->model->getTable(),
                'id'          => $id,
                'relations'   => $this->getRelationshipsByID(),
            ]);

            $row = $this->model->newQuery()
                ->with($this->getRelationshipsByID())
                ->find($id);

            if (!$row) {
                $this->logger->warning('Record not found on getById', [
                    'table' => $this->model->getTable(),
                    'id'    => $id,
                ]);

                throw new NotFoundServiceException("{$this->model->getTable()} not found with id {$id}.");
            }

            $this->logger->info('Record fetched by id', [
                'table' => $this->model->getTable(),
                'id'    => $row->getKey(),
            ]);

            return collect($row);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error(
                "Unhandled exception in {$this->model->getTable()}.getById",
                ['exception' => $e]
            );

            throw new InternalServiceException(
                "Failed to load {$this->model->getTable()}. Please try again later."
            );
        }
    }
}
