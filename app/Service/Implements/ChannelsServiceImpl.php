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
    }

    protected function checkPermission(string $action): void
    {
        $user = $this->guard->user();
        if (!$user) throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        if (!$user->hasPermission('channels', $action)) {
            throw new PermissionDeniedServiceException("User does not have permission to {$action} channels.");
        }
    }

    protected function getOrFail(int $id): Channels
    {
        $row = $this->model->newQuery()->find($id);
        if (!$row) throw new NotFoundServiceException("Channel not found with id {$id}.");
        return $row;
    }

    public function getAll(PaginationRequest $data): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission('read');
            $value = $data->validated();
            $page  = (int)($value['page'] ?? 0);
            $size  = (int)($value['size'] ?? 0);

            $query = $this->model->newQuery()->with(['servicesEnvironments','configurations']);

            if ($page > 0 && $size > 0) return $this->applyPagination($query, $page, $size);
            return $query->get();
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to load channels. Please try again later.'); }
    }

    public function search(SearchPaginationRequest $data): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission('read');
            $value = $data->validated();
            $term  = trim((string)($value['search'] ?? ''));
            $page  = (int)($value['page'] ?? 0);
            $size  = (int)($value['size'] ?? 0);

            $query = $this->model->newQuery()->with(['servicesEnvironments','configurations']);
            if ($term !== '') $query->whereLike('name', "%{$term}%");

            if ($page > 0 && $size > 0) return $this->applyPagination($query, $page, $size);
            return $query->get();
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to search channels. Please try again later.'); }
    }

    public function create(CreateChannelRequest $data): Collection
    {
        try {
            $this->checkPermission('create');
            $value = $data->validated();
            $row   = $this->model->newQuery()->create(['name' => (string)$value['name']]);
            return collect($row);
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Channel with name '{$data->validated()['name']}' already exists.");
            }
            throw new InternalServiceException("Error when creating channel: {$e->getMessage()}");
        } catch (\Throwable $e) {
            throw new InternalServiceException('Failed to create channel. Please try again later.');
        }
    }

    public function update(int $id, UpdateChannelRequest $data): Collection
    {
        try {
            $this->checkPermission('update');
            $row = $this->getOrFail($id);

            $value = $data->validated();
            $payload = [];
            if (array_key_exists('name', $value)) {
                $name = trim((string)$value['name']);
                if ($name !== '') $payload['name'] = $name;
            }

            if (!empty($payload)) $row->fill($payload)->save();
            return collect($row->fresh());
        } catch (AppServiceException $e) { throw $e; }
        catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Channel name already exists.");
            }
            throw new InternalServiceException("Error when updating channel: {$e->getMessage()}");
        } catch (\Throwable $e) {
            throw new InternalServiceException('Failed to update channel. Please try again later.');
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
        catch (QueryException $e) {
            throw new InternalServiceException("Error when deleting channel: {$e->getMessage()}");
        } catch (\Throwable $e) {
            throw new InternalServiceException('Failed to delete channel. Please try again later.');
        }
    }

    public function getById(int $id): Collection
    {
        try {
            $this->checkPermission('read');
            $row = $this->model->newQuery()
                ->with(['servicesEnvironments.service','configurations.serviceEnvironment'])
                ->find($id);
            if (!$row) throw new NotFoundServiceException("Channel not found with id {$id}.");
            return collect($row);
        } catch (AppServiceException $e) { throw $e; }
        catch (\Throwable $e) { throw new InternalServiceException('Failed to load channel. Please try again later.'); }
    }
}
