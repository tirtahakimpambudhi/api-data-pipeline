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
use App\Http\Requests\Roles\CreateRolesRequest;
use App\Http\Requests\Roles\UpdateRolesRequest;
use App\Models\Roles;
use App\Models\RolesPermissions;
use App\Service\Contracts\RolesService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RolesServiceImpl implements RolesService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        protected Roles $rolesModel,
        protected Logger $logger
    )
    {
        $this->guard = $authFactory->guard('web');
        $this->logger->info("RolesServiceImpl initialized");
    }

    protected function checkPermission(string $action, string $resourceType = ResourcesTypes::ROLES): void
    {
        $this->logger->debug('Checking permission', [
            'action'   => $action,
            'resource' => $resourceType,
        ]);

        $user = $this->guard->user();

        if (!$user) {
            $this->logger->warning('Permission check failed: user not authenticated');
            throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        };

        if (!$user->hasPermission($resourceType, $action)) {
            $this->logger->warning('Permission denied', [
                'user_id'  => $user->id ?? null,
                'action'   => $action,
                'resource' => $resourceType,
            ]);

            $actionLower = strtolower($action);

            throw new PermissionDeniedServiceException(
                "User does not have permission to {$actionLower} {$resourceType}."
            );
        }

        $this->logger->debug('Permission granted', [
            'user_id'  => $user->id ?? null,
            'action'   => $action,
            'resource' => $resourceType,
        ]);
    }

    protected function getOrFail(int $id): Roles
    {
        $this->logger->debug('Fetching Roles by ID', ['id' => $id]);

        $row = $this->rolesModel->newQuery()->find($id);

        if (!$row) {
            $this->logger->error('Roles not found', ['id' => $id]);
            throw new NotFoundServiceException("Roles not found with id {$id}.");
        }

        $this->logger->debug('Roles found', [
            'id'          => $row->id,
            'name'        => $row->name ?? null,
            'description' => $row->description ?? null,
        ]);

        return $row;
    }

    public function getAll(?PaginationRequest $data, bool $onlyRoles = false): LengthAwarePaginator|Collection
    {
        $this->logger->info('Retrieving all Roles', ['onlyRoles' => $onlyRoles]);

        try {
            $this->checkPermission(ActionsTypes::READ);

            $page = 0;
            $size = 0;
            if ($data !== null) {
                $value = $data->validated();
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);

                $this->logger->debug('Pagination parameters detected', [
                    'page' => $page,
                    'size' => $size,
                ]);
            }

            $query = $this->rolesModel->newQuery();

            if (!$onlyRoles) {
                $query->with(['permissions','rolesPermissions']);
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($query, $page, $size)
                : $query->get();

            $this->logger->info('Successfully retrieved Roles', [
                'count' => $result instanceof Collection ? $result->count() : $result->total(),
            ]);

            return $result;
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error retrieving Roles', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load roles. Please try again later.');
        }
    }

    public function search(?SearchPaginationRequest $data, bool $onlyRoles = false): LengthAwarePaginator|Collection
    {
        $this->logger->info('Searching Roles', ['onlyRoles' => $onlyRoles]);

        try {
            $this->checkPermission(ActionsTypes::READ);

            $term = '';
            $page = 0;
            $size = 0;

            if ($data !== null) {
                $value = $data->validated();
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);
                $term  = trim((string)($value['search'] ?? ''));

                $this->logger->debug('Search parameters', [
                    'term' => $term,
                    'page' => $page,
                    'size' => $size,
                ]);
            }

            $query = $this->rolesModel->newQuery();

            if (!$onlyRoles) {
                $query->with(['permissions', 'rolesPermissions']);
            }

            if ($term !== '') {
                $this->logger->debug('Applying search filters', ['term' => $term]);

                $query->where(function ($q) use ($term) {
                    $q->whereLike('name', "%{$term}%")
                        ->orWhereHas('permissions', function ($sq) use ($term) {
                            $sq->whereLike('resource_type', "%{$term}%")
                                ->orWhereLike('action', "%{$term}%");
                        });
                });
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($query, $page, $size)
                : $query->get();

            $this->logger->info('Search completed', [
                'term'  => $term,
                'count' => $result instanceof Collection ? $result->count() : $result->total(),
            ]);

            return $result;
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error during Roles search', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw new InternalServiceException('Failed to search roles. Please try again later.');
        }
    }

    public function getById(int $id): Collection
    {
        $this->logger->info('Retrieving Roles by ID', ['id' => $id]);

        try {
            $this->checkPermission(ActionsTypes::READ);

            $row = $this->rolesModel->newQuery()
                ->with(['permissions', 'rolesPermissions'])
                ->find($id);

            if (!$row) {
                $this->logger->warning('Roles not found', ['id' => $id]);
                throw new NotFoundServiceException("Roles not found with id {$id}.");
            }

            $this->logger->info('Roles retrieved successfully', [
                'id'   => $row->id,
                'name' => $row->name,
            ]);

            return collect($row);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error retrieving Roles by ID', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load roles. Please try again later.');
        }
    }

    public function create(CreateRolesRequest $data): Collection
    {
        $this->logger->info('Creating Roles process started');

        try {
            $this->checkPermission(ActionsTypes::CREATE);
            $this->checkPermission(ActionsTypes::CREATE, ResourcesTypes::ROLES_PERMISSIONS);

            $value = $data->validated();
            $this->logger->debug('Validated creation payload', $value);

            $role = $this->rolesModel->newQuery()->create([
                'name'        => $value['name'],
                'description' => $value['description'],
            ]);

            $role->permissions()->syncWithoutDetaching($value['permissions_ids']);

            $this->logger->info('Roles created successfully', [
                'id'          => $role->id,
                'name'        => $role->name,
                'description' => $role->description,
            ]);

            return collect($role->load(['permissions', 'rolesPermissions']));
        } catch (AppServiceException $e) {
            throw $e;
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->logger->warning('Duplicate role name detected on create', [
                    'name'       => $value['name'] ?? null,
                    'error_code' => $e->getCode(),
                ]);
                throw new ConflictServiceException("The role name already exists.");
            }

            $this->logger->error('Database query error during Roles creation', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            throw new InternalServiceException("Error when creating role: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->critical('Unexpected error during Roles creation', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw new InternalServiceException('Failed to create role. Please try again later.');
        }
    }

    public function update(int $id, UpdateRolesRequest $data): Collection
    {
        $this->logger->info('Updating Roles', ['id' => $id]);

        try {
            $this->checkPermission(ActionsTypes::UPDATE);
            $this->checkPermission(ActionsTypes::CREATE, ResourcesTypes::ROLES_PERMISSIONS);
            $this->checkPermission(ActionsTypes::UPDATE, ResourcesTypes::ROLES_PERMISSIONS);

            $role  = $this->getOrFail($id);
            $value = $data->validated();

            $this->logger->debug('Validated update payload', $value);

            $payload = [];

            if (array_key_exists('name', $value)) {
                $payload['name'] = $value['name'];
            }

            if (array_key_exists('description', $value)) {
                $payload['description'] = $value['description'];
            }

            if (!empty($payload)) {
                $this->logger->debug('Applying update fields', $payload);
                $role->fill($payload)->save();
            }

            if (array_key_exists('permissions_ids', $value) && count($value['permissions_ids']) > 0) {
                $role->permissions()->sync($value['permissions_ids']);
            }

            $this->logger->info('Roles updated successfully', [
                'id'          => $role->id,
                'name'        => $role->name,
                'description' => $role->description,
            ]);

            return collect($role->fresh()->load(['permissions', 'rolesPermissions']));
        } catch (AppServiceException $e) {
            throw $e;
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->logger->warning('Duplicate role name detected on update', [
                    'id'         => $id,
                    'name'       => $value['name'] ?? null,
                    'error_code' => $e->getCode(),
                ]);

                throw new ConflictServiceException("The role name already exists.");
            }

            $this->logger->error('Database query error during Roles update', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            throw new InternalServiceException("Error when updating role: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error('Error updating Roles', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw new InternalServiceException('Failed to update role. Please try again later.');
        }
    }

    public function delete(int $id): Collection
    {
        $this->logger->info('Deleting Roles', ['id' => $id]);

        try {
            $this->checkPermission(ActionsTypes::DELETE);

            $row = $this->getOrFail($id);
            $row->delete();

            $this->logger->info('Roles deleted successfully', ['id' => $id]);

            return collect(['id' => $id, 'deleted' => true]);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error('Database query error during Roles deletion', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            throw new InternalServiceException("Error when deleting roles: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error('Error deleting Roles', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw new InternalServiceException('Failed to delete roles. Please try again later.');
        }
    }
}
