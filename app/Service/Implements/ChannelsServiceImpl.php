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
use App\Http\Requests\Channels\CreateChannelRequest;
use App\Http\Requests\Channels\UpdateChannelRequest;
use App\Models\Channels;
use App\Service\Contracts\ChannelsService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ChannelsServiceImpl implements ChannelsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        protected Channels $model,
        protected Logger $logger
    ) {
        $this->guard = $authFactory->guard('web');
        $this->logger->info("ChannelsServiceImpl initialized");
    }

    protected function checkPermission(string $action): void
    {
        $this->logger->debug("Checking permission for action", ['action' => $action]);
        $user = $this->guard->user();

        if (!$user) {
            $this->logger->warning("Permission check failed: user not authenticated");
            throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        }

        if (!$user->hasPermission(ResourcesTypes::CHANNELS, $action)) {
            $this->logger->warning("Permission denied", [
                'user_id' => $user->id ?? null,
                'action' => $action,
            ]);
            throw new PermissionDeniedServiceException("User does not have permission to {$action} channels.");
        }

        $this->logger->debug("Permission granted", ['user_id' => $user->id ?? null, 'action' => $action]);
    }

    protected function getOrFail(int $id): Channels
    {
        $this->logger->debug("Fetching channel by ID", ['id' => $id]);
        $row = $this->model->newQuery()->find($id);
        if (!$row) {
            $this->logger->error("Channel not found", ['id' => $id]);
            throw new NotFoundServiceException("Channel not found with id {$id}.");
        }
        $this->logger->debug("Channel found", ['id' => $row->id, 'name' => $row->name]);
        return $row;
    }

    public function getAll(PaginationRequest | null $data, bool $onlyChannel = false): LengthAwarePaginator|Collection
    {
        $this->logger->info("Retrieving all channels", ['onlyChannel' => $onlyChannel]);
        try {
            $this->checkPermission(ActionsTypes::READ);

            $page = 0;
            $size = 0;
            if ($data !== null) {
                $value = $data->validated();
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);
                $this->logger->debug("Pagination detected", ['page' => $page, 'size' => $size]);
            }

            $query = $this->model->newQuery();
            if (!$onlyChannel) {
                $query->with(['servicesEnvironments', 'configurations']);
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($query, $page, $size)
                : $query->get();

            $this->logger->info("Successfully retrieved channels", [
                'count' => $result instanceof Collection ? $result->count() : $result->total(),
            ]);
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error("Error retrieving channels", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load channels. Please try again later.');
        }
    }

    public function search(SearchPaginationRequest | null $data, bool $onlyChannel = false): LengthAwarePaginator|Collection
    {
        $this->logger->info("Searching channels", ['onlyChannel' => $onlyChannel]);
        try {
            $this->checkPermission(ActionsTypes::READ);

            $term = '';
            $page = 0;
            $size = 0;

            if ($data !== null) {
                $value = $data->validated();
                $term  = trim((string)($value['search'] ?? ''));
                $page  = (int)($value['page'] ?? 0);
                $size  = (int)($value['size'] ?? 0);
                $this->logger->debug("Search parameters", ['term' => $term, 'page' => $page, 'size' => $size]);
            }

            $query = $this->model->newQuery();
            if (!$onlyChannel) {
                $query->with(['servicesEnvironments', 'configurations']);
            }
            if ($term !== '') {
                $this->logger->debug("Applying search filter", ['term' => $term]);
                $query->whereLike('name', "%{$term}%");
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($query, $page, $size)
                : $query->get();

            $this->logger->info("Search completed", [
                'term' => $term,
                'count' => $result instanceof Collection ? $result->count() : $result->total(),
            ]);
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error("Error during channel search", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to search channels. Please try again later.');
        }
    }

    public function create(CreateChannelRequest $data): Collection
    {
        $this->logger->info("Creating channel process started");
        try {
            $this->checkPermission(ActionsTypes::CREATE);
            $value = $data->validated();
            $this->logger->debug("Validated data for creation", $value);

            $row = $this->model->newQuery()->create([
                'name' => (string)$value['name'],
            ]);

            $this->logger->info("Channel created successfully", [
                'id' => $row->id,
                'name' => $row->name,
            ]);

            return collect($row);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->logger->warning("Duplicate channel name detected", [
                    'name' => $data->validated()['name'] ?? null,
                ]);
                throw new ConflictServiceException("Channel with name '{$data->validated()['name']}' already exists.");
            }
            $this->logger->error("Database query error during channel creation", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new InternalServiceException("Error when creating channel: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->critical("Unexpected error during channel creation", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to create channel. Please try again later.');
        }
    }

    public function update(int $id, UpdateChannelRequest $data): Collection
    {
        $this->logger->info("Updating channel", ['id' => $id]);
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
                $this->logger->debug("Applying update payload", $payload);
                $row->fill($payload)->save();
            }

            $this->logger->info("Channel updated successfully", [
                'id' => $row->id,
                'name' => $row->name,
            ]);

            return collect($row->fresh());
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $this->logger->warning("Channel name conflict detected", [
                    'id' => $id,
                    'name' => $value['name'] ?? null,
                ]);
                throw new ConflictServiceException("Channel name already exists.");
            }
            $this->logger->error("Database query error during channel update", [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new InternalServiceException("Error when updating channel: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Error updating channel", [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to update channel. Please try again later.');
        }
    }

    public function delete(int $id): Collection
    {
        $this->logger->info("Deleting channel", ['id' => $id]);
        try {
            $this->checkPermission(ActionsTypes::DELETE);
            $row = $this->getOrFail($id);
            $row->delete();
            $this->logger->info("Channel deleted successfully", ['id' => $id]);
            return collect(['id' => $id, 'deleted' => true]);
        } catch (\Throwable $e) {
            $this->logger->error("Error deleting channel", [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to delete channel. Please try again later.');
        }
    }

    public function getById(int $id): Collection
    {
        $this->logger->info("Retrieving channel by ID", ['id' => $id]);
        try {
            $this->checkPermission(ActionsTypes::READ);
            $row = $this->model->newQuery()
                ->with(['servicesEnvironments.service', 'configurations.serviceEnvironment'])
                ->find($id);

            if (!$row) {
                $this->logger->warning("Channel not found", ['id' => $id]);
                throw new NotFoundServiceException("Channel not found with id {$id}.");
            }

            $this->logger->info("Channel retrieved successfully", [
                'id' => $row->id,
                'name' => $row->name,
            ]);

            return collect($row);
        } catch (\Throwable $e) {
            $this->logger->error("Error retrieving channel by ID", [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new InternalServiceException('Failed to load channel. Please try again later.');
        }
    }
}
