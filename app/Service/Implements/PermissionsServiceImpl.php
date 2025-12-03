<?php

namespace App\Service\Implements;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Models\Permissions;
use App\Service\Contracts\PermissionsService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PermissionsServiceImpl implements PermissionsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;
    /**
     * Create a new class instance.
     */
    public function __construct(
        AuthFactory $authFactory,
        protected Permissions $model,
        protected Logger $logger
    )
    {
        $this->guard = $authFactory->guard('web');
        $this->logger->info("PermissionsServiceImpl initialized");
    }

    protected function checkPermission(string $action): void
    {
        $this->logger->debug('Checking permission', [
            'action'   => $action,
            'resource' => ResourcesTypes::PERMISSIONS,
        ]);

        $user = $this->guard->user();

        if (!$user) {
            $this->logger->warning('Permission check failed: user not authenticated');
            throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        }

        if (!$user->hasPermission(ResourcesTypes::PERMISSIONS, $action)) {
            $this->logger->warning('Permission denied', [
                'user_id'  => $user->id ?? null,
                'action'   => $action,
                'resource' => ResourcesTypes::PERMISSIONS,
            ]);

            $actionLower = strtolower($action);

            throw new PermissionDeniedServiceException(
                "User does not have permission to {$actionLower} permissions."
            );
        }

        $this->logger->debug('Permission granted', [
            'user_id'  => $user->id ?? null,
            'action'   => $action,
            'resource' => ResourcesTypes::PERMISSIONS,
        ]);
    }


    public function getAll(?PaginationRequest $data, bool $onlyPermissions = false): LengthAwarePaginator|Collection
    {
        $this->logger->info('Retrieving all Permissions', ['$onlyPermissions' => $onlyPermissions]);

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

            $query = $this->model->newQuery();

            if (!$onlyPermissions) {
                $query->with(['roles','rolesPermissions']);
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($query, $page, $size)
                : $query->get();

            $this->logger->info('Successfully retrieved Permissions', [
                'count' => $result instanceof Collection ? $result->count() : $result->total(),
            ]);

            return $result;
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error retrieving Permissions', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load permissions. Please try again later.');
        }
    }

    public function getById(int $id): Collection
    {
        $this->logger->info('Retrieving Permissions by ID', ['id' => $id]);

        try {
            $this->checkPermission(ActionsTypes::READ);

            $row = $this->model->newQuery()
                ->with(['roles', 'rolesPermissions'])
                ->find($id);

            if (!$row) {
                $this->logger->warning('Permissions not found', ['id' => $id]);
                throw new NotFoundServiceException("Permissions not found with id {$id}.");
            }

            $this->logger->info('Permissions retrieved successfully', [
                'id'   => $row->id,
                'resource_type' => $row->resource_type,
                'action' => $row->action,
            ]);

            return collect($row);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error retrieving Permissions by ID', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load permissions. Please try again later.');
        }
    }

    public function search(?SearchPaginationRequest $data, bool $onlyPermissions = false): LengthAwarePaginator|Collection
    {
        $this->logger->info('Searching Roles', ['$onlyPermissions' => $onlyPermissions]);

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

            $query = $this->model->newQuery();

            if (!$onlyPermissions) {
                $query->with(['roles', 'rolesPermissions']);
            }

            if ($term !== '') {
                $this->logger->debug('Applying search filters', ['term' => $term]);

                $query->where(function ($q) use ($term) {
                    $q->whereLike('resource_type', "%{$term}%")
                        ->orWhereLike('action', "%{$term}%");
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
            $this->logger->error('Error during Permissions search', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw new InternalServiceException('Failed to search permissions. Please try again later.');
        }
    }
}
