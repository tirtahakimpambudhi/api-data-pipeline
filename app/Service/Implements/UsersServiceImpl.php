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
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Roles;
use App\Models\Users;
use App\Service\Contracts\UsersService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;


class UsersServiceImpl implements UsersService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        protected Users $model,
        protected Logger $logger
    )
    {
        $this->guard = $authFactory->guard('web');
        $this->logger->info("UsersServiceImpl initialized");
    }

    protected function checkPermission(string $action, string $resourceType = ResourcesTypes::USERS): void
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

    private function adminUser(): ?Users
    {
        $email = env('ADMIN_EMAIL', "admin@gmail.com");

        if (empty($email)) {
            return null;
        }

        return $this->model->newQuery()
            ->where('email', $email)
            ->first();
    }


    protected function checkSelfManipulation(string $targetUserId): void
    {
        $currentUser = $this->guard->user();

        if ($currentUser->id === $targetUserId) {
            $this->logger->warning('Self-manipulation attempt detected', [
                'user_id'        => $currentUser->id,
                'target_user_id' => $targetUserId,
            ]);

            throw new PermissionDeniedServiceException(
                "You cannot modify or delete your own account."
            );
        }

        $this->logger->debug('Self-manipulation check passed', [
            'user_id'        => $currentUser->id,
            'target_user_id' => $targetUserId,
        ]);
    }

    protected function checkManipulationAdminUser(string $targetUserId): void
    {
        $currentUser = $this->guard->user();

        $adminUser = $this->adminUser();
        if (!$adminUser) {
            if ($targetUserId === $adminUser->id) {
                $this->logger->warning('data admin user manipulation attempt detected', [
                    'user_id'        => $currentUser->id,
                    'target_user_id' => $targetUserId,
                ]);
                throw new PermissionDeniedServiceException(
                    "You cannot modify or delete your admin user."
                );
            }
        }

        $this->logger->debug('Self-manipulation check passed', [
            'user_id'        => $currentUser->id,
            'target_user_id' => $targetUserId,
        ]);
    }

    protected function getOrFail(string $id): Users
    {
        $this->logger->debug('Fetching User by ID', ['id' => $id]);
        $adminUser = $this->adminUser();
        $row = $this->model->newQuery()
            ->whereNot('id', $adminUser->id)
            ->find($id);

        if (!$row) {
            $this->logger->error('User not found', ['id' => $id]);
            throw new NotFoundServiceException("User not found with id {$id}.");
        }

        $this->logger->debug('User found', [
            'id'    => $row->id,
            'name'  => $row->name ?? null,
            'email' => $row->email ?? null,
        ]);

        return $row;
    }

    public function getAll(?PaginationRequest $data, bool $onlyUsers = false): LengthAwarePaginator|Collection
    {
        $this->logger->info('Retrieving all users', ['onlyUsers' => $onlyUsers]);

        try {
            $this->checkPermission(ActionsTypes::READ);
            $user = $this->guard->user();
            $adminUser = $this->adminUser();

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

            $query = $this->model->newQuery()
                ->whereNot('id', $adminUser->id)
                ->whereNot('id', $user->id);

            if (!$onlyUsers) {
                $query->with(['role']);
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($query, $page, $size)
                : $query->get();

            $this->logger->info('Successfully retrieved users', [
                'count' => $result instanceof Collection ? $result->count() : $result->total(),
            ]);

            return $result;
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error retrieving users', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load users. Please try again later.');
        }
    }

    public function getById(string $id): Collection
    {
        $this->logger->info('Retrieving user by ID', ['id' => $id]);

        try {
            $this->checkPermission(ActionsTypes::READ);

            $user = $this->getOrFail($id);

            $this->logger->info('Successfully retrieved user', [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]);

            return collect([
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role_id'     => $user->role_id,
                'role'        => $user->role,
                'created_at'  => $user->created_at,
                'updated_at'  => $user->updated_at,
            ]);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error retrieving user by ID', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load user. Please try again later.');
        }
    }

    public function search(?SearchPaginationRequest $data, bool $onlyUsers = false): LengthAwarePaginator|Collection
    {
        $this->logger->info('Searching users', ['onlyUsers' => $onlyUsers]);

        try {
            $this->checkPermission(ActionsTypes::READ);
            $currentUser = $this->guard->user();

            $value = $data->validated();
            $query = (string)($value['query'] ?? '');
            $page  = (int)($value['page'] ?? 0);
            $size  = (int)($value['size'] ?? 0);

            $this->logger->debug('Search parameters', [
                'query' => $query,
                'page'  => $page,
                'size'  => $size,
            ]);

            $queryBuilder = $this->model->newQuery()
                ->whereNot('id', $currentUser->id)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });

            if (!$onlyUsers) {
                $queryBuilder->with(['role']);
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($queryBuilder, $page, $size)
                : $queryBuilder->get();

            $this->logger->info('Successfully searched users', [
                'query' => $query,
                'count' => $result instanceof Collection ? $result->count() : $result->total(),
            ]);

            return $result;
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error searching users', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to search users. Please try again later.');
        }
    }

    public function create(CreateUserRequest $data): Collection
    {
        $this->logger->info('Creating new user');

        try {
            $this->checkPermission(ActionsTypes::CREATE);

            $validated = $data->validated();

            $this->logger->debug('Validated user data', [
                'name'    => $validated['name'] ?? null,
                'email'   => $validated['email'] ?? null,
                'role_id' => $validated['role_id'] ?? null,
            ]);

            // Check if email already exists
            $existingUser = $this->model->newQuery()
                ->where('email', $validated['email'])
                ->first();

            if ($existingUser) {
                $this->logger->warning('User creation failed: email already exists', [
                    'email' => $validated['email'],
                ]);
                throw new ConflictServiceException('Email address already exists.');
            }

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $user = $this->model->newQuery()->create($validated);

            $this->logger->info('User created successfully', [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]);

            return collect([
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role_id'    => $user->role_id,
                'created_at' => $user->created_at,
            ]);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error creating user', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to create user. Please try again later.');
        }
    }

    public function update(string $id, UpdateUserRequest $data): Collection
    {
        $this->logger->info('Updating user', ['id' => $id]);
        try {
            $this->checkPermission(ActionsTypes::UPDATE);
            $this->checkSelfManipulation($id);
            $this->checkManipulationAdminUser($id);

            $user = $this->getOrFail($id);

            $validated = $data->validated();

            $this->logger->debug('Validated update data', [
                'id'      => $id,
                'fields'  => array_keys($validated),
            ]);

            // Check if email is being changed and if it already exists
            if (isset($validated['email']) && $validated['email'] !== $user->email) {
                $existingUser = $this->model->newQuery()
                    ->where('email', $validated['email'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingUser) {
                    $this->logger->warning('User update failed: email already exists', [
                        'id'    => $id,
                        'email' => $validated['email'],
                    ]);
                    throw new ConflictServiceException('Email address already exists.');
                }
            }

            // Hash password if provided
            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
                $this->logger->debug('Password will be updated', ['id' => $id]);
            }

            $user->update($validated);
            $user->refresh();

            $this->logger->info('User updated successfully', [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]);

            return collect([
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role_id'    => $user->role_id,
                'updated_at' => $user->updated_at,
            ]);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error updating user', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to update user. Please try again later.');
        }
    }

    public function delete(string $id): Collection
    {
        $this->logger->info('Deleting user', ['id' => $id]);

        try {
            $this->checkPermission(ActionsTypes::DELETE);
            $this->checkSelfManipulation($id);
            $this->checkManipulationAdminUser($id);

            $user = $this->getOrFail($id);

            $userData = [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ];

            $this->logger->debug('User data before deletion', $userData);

            $user->delete();

            $this->logger->info('User deleted successfully', $userData);

            return collect([
                'message' => 'User deleted successfully',
                'deleted_user' => $userData,
            ]);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error deleting user', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to delete user. Please try again later.');
        }
    }
}
