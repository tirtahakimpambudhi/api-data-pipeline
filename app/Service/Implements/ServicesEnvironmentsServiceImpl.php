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
        $this->logger->info('ServicesEnvironmentsServiceImpl initialized');
    }

    protected function checkPermission(string $action): void
    {
        $this->logger->debug('Checking permission for action', ['action' => $action]);
        $user = $this->guard->user();

        if (!$user) {
            $this->logger->warning('Permission check failed: user not authenticated');
            throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        }

        if (!$user->hasPermission(ResourcesTypes::SERVICES_ENVIRONMENTS, $action)) {
            $this->logger->warning('Permission denied', [
                'user_id' => $user->id ?? null,
                'action'  => $action,
            ]);
            throw new PermissionDeniedServiceException("User does not have permission to {$action} services_environments.");
        }

        $this->logger->debug('Permission granted', [
            'user_id' => $user->id ?? null,
            'action'  => $action,
        ]);
    }

    protected function getOrFail(int $id): ServicesEnvironments
    {
        $this->logger->debug('Fetching ServiceEnvironment by ID', ['id' => $id]);
        $row = $this->model->newQuery()->find($id);
        if (!$row) {
            $this->logger->error('ServiceEnvironment not found', ['id' => $id]);
            throw new NotFoundServiceException("ServiceEnvironment not found with id {$id}.");
        }
        $this->logger->debug('ServiceEnvironment found', [
            'id'          => $row->id,
            'service_id'  => $row->service_id ?? null,
            'environment_id' => $row->environment_id ?? null,
        ]);
        return $row;
    }

    public function getAll(PaginationRequest | null $data, bool $onlySvcEnv = false): LengthAwarePaginator|Collection
    {
        $this->logger->info('Retrieving all ServiceEnvironments', ['onlySvcEnv' => $onlySvcEnv]);
        try {
            $this->checkPermission(ActionsTypes::READ);

            $page = 0;
            $size = 0;
            if ($data !== null) {
                $value = $data->validated();
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);
                $this->logger->debug('Pagination parameters detected', ['page' => $page, 'size' => $size]);
            }

            $query = $this->model->newQuery();

            if (!$onlySvcEnv) {
                $query->with(['service.namespace', 'environment', 'configurations.channel']);
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($query, $page, $size)
                : $query->get();

            $this->logger->info('Successfully retrieved ServiceEnvironments', [
                'count' => $result instanceof Collection ? $result->count() : $result->total(),
            ]);

            return $result;
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error retrieving ServiceEnvironments', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load services_environments. Please try again later.');
        }
    }

    public function search(SearchPaginationRequest | null $data, bool $onlySvcEnv = false): LengthAwarePaginator|Collection
    {
        $this->logger->info('Searching ServiceEnvironments', ['onlySvcEnv' => $onlySvcEnv]);
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
                $this->logger->debug('Search parameters', ['term' => $term, 'page' => $page, 'size' => $size]);
            }

            $query = $this->model->newQuery();

            if (!$onlySvcEnv) {
                $query->with(['service.namespace', 'environment', 'configurations.channel']);
            }

            if ($term !== '') {
                $this->logger->debug('Applying search filters', ['term' => $term]);
                $query->where(function ($q) use ($term) {
                    $q->whereHas('service', fn($sq) => $sq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('service.namespace', fn($nq) => $nq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('environment', fn($eq) => $eq->whereLike('name', "%{$term}%"));
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
            $this->logger->error('Error during ServiceEnvironments search', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to search services_environments. Please try again later.');
        }
    }

    public function create(CreateServiceEnvironmentRequest $data): Collection
    {
        $this->logger->info('Creating ServiceEnvironment process started');
        try {
            $this->checkPermission(ActionsTypes::CREATE);
            $value = $data->validated();
            $this->logger->debug('Validated creation payload', $value);

            $row = $this->model->newQuery()->create([
                'service_id'     => (int) $value['service_id'],
                'environment_id' => (int) $value['environment_id'],
            ]);

            $this->logger->info('ServiceEnvironment created successfully', [
                'id'             => $row->id,
                'service_id'     => $row->service_id,
                'environment_id' => $row->environment_id,
            ]);

            return collect($row->load(['service.namespace', 'environment']));
        } catch (AppServiceException $e) {
            throw $e;
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->logger->warning('Duplicate (service_id, environment_id) detected on create', [
                    'service_id'     => $value['service_id'] ?? null,
                    'environment_id' => $value['environment_id'] ?? null,
                    'error_code'     => $e->getCode(),
                ]);
                throw new ConflictServiceException("The pair (service_id, environment_id) already exists.");
            }
            $this->logger->error('Database query error during ServiceEnvironment creation', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);
            throw new InternalServiceException("Error when creating service_environment: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->critical('Unexpected error during ServiceEnvironment creation', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to create service_environment. Please try again later.');
        }
    }

    public function update(int $id, UpdateServiceEnvironmentRequest $data): Collection
    {
        $this->logger->info('Updating ServiceEnvironment', ['id' => $id]);
        try {
            $this->checkPermission(ActionsTypes::UPDATE);
            $row   = $this->getOrFail($id);
            $value = $data->validated();
            $this->logger->debug('Validated update payload', $value);

            $payload = [];
            if (array_key_exists('service_id', $value)) {
                $payload['service_id'] = (int) $value['service_id'];
            }
            if (array_key_exists('environment_id', $value)) {
                $payload['environment_id'] = (int) $value['environment_id'];
            }

            if (!empty($payload)) {
                $this->logger->debug('Applying update fields', $payload);
                $row->fill($payload)->save();
            }

            $this->logger->info('ServiceEnvironment updated successfully', [
                'id'             => $row->id,
                'service_id'     => $row->service_id,
                'environment_id' => $row->environment_id,
            ]);

            return collect($row->fresh()->load(['service.namespace', 'environment']));
        } catch (AppServiceException $e) {
            throw $e;
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->logger->warning('Duplicate (service_id, environment_id) detected on update', [
                    'id'             => $id,
                    'service_id'     => $value['service_id'] ?? null,
                    'environment_id' => $value['environment_id'] ?? null,
                    'error_code'     => $e->getCode(),
                ]);
                throw new ConflictServiceException("The pair (service_id, environment_id) already exists.");
            }
            $this->logger->error('Database query error during ServiceEnvironment update', [
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);
            throw new InternalServiceException("Error when updating service_environment: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error('Error updating ServiceEnvironment', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to update service_environment. Please try again later.');
        }
    }

    public function delete(int $id): Collection
    {
        $this->logger->info('Deleting ServiceEnvironment', ['id' => $id]);
        try {
            $this->checkPermission(ActionsTypes::DELETE);
            $row = $this->getOrFail($id);
            $row->delete();

            $this->logger->info('ServiceEnvironment deleted successfully', ['id' => $id]);

            return collect(['id' => $id, 'deleted' => true]);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error('Database query error during ServiceEnvironment deletion', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);
            throw new InternalServiceException("Error when deleting service_environment: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error('Error deleting ServiceEnvironment', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to delete service_environment. Please try again later.');
        }
    }

    public function getById(int $id): Collection
    {
        $this->logger->info('Retrieving ServiceEnvironment by ID', ['id' => $id]);
        try {
            $this->checkPermission(ActionsTypes::READ);
            $row = $this->model->newQuery()
                ->with(['service.namespace', 'environment', 'configurations.channel'])
                ->find($id);

            if (!$row) {
                $this->logger->warning('ServiceEnvironment not found', ['id' => $id]);
                throw new NotFoundServiceException("ServiceEnvironment not found with id {$id}.");
            }

            $this->logger->info('ServiceEnvironment retrieved successfully', [
                'id'             => $row->id,
                'service_id'     => $row->service_id,
                'environment_id' => $row->environment_id,
            ]);

            return collect($row);
        } catch (AppServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Error retrieving ServiceEnvironment by ID', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load service_environment. Please try again later.');
        }
    }
}
