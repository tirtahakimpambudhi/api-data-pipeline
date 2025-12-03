<?php

namespace App\Service\Implements;

use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Channels\CreateChannelRequest;
use App\Http\Requests\Channels\UpdateChannelRequest;
use App\Models\Channels;
use App\Service\CommonService;
use App\Service\Contracts\ChannelsService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ChannelsServiceImpl extends CommonService implements ChannelsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        Channels $model,
        Logger $logger
    ) {
        parent::__construct(
            $authFactory,
            $model,
            ResourcesTypes::CHANNELS,
            $logger,
            ['servicesEnvironments', 'configurations']

        );
        $this->logger->info("ChannelsServiceImpl initialized");
    }


    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function getAll(PaginationRequest | null $data, bool $onlyChannel = false): LengthAwarePaginator|Collection
    {
        return parent::getAll($data, $onlyChannel);
    }

    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function search(SearchPaginationRequest | null $data, bool $onlyChannel = false): LengthAwarePaginator|Collection
    {
        return parent::search($data, $onlyChannel);
    }

    /**
     * @throws ConflictServiceException
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function create(CreateChannelRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::create($data);
    }

    /**
     * @throws ConflictServiceException
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function update(int $id, UpdateChannelRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::update($id, $data);
    }

    protected function createModel(array $value): Model
    {
        return $this->model->newQuery()->create([
            'name' => (string)$value['name'],
        ]);
    }

    protected function updateModel(array $value, Model $model): void
    {
        $payload = [];
        if (array_key_exists('name', $value)) {
            $name = trim((string)$value['name']);
            if ($name !== '') $payload['name'] = $name;
        }

        if (!empty($payload)) {
            $this->logger->debug("Applying update payload", $payload);
            $model->fill($payload)->save();
        }
    }

    protected function searchModel(Builder $query, string $value)
    {
        if ($value !== '') {
            $this->logger->debug("Applying search filter", ['term' => $value]);
            $query->whereLike('name', "%{$value}%");
        }
    }

    protected function getRelationshipsByID(): array
    {
        return ['servicesEnvironments.service', 'configurations.serviceEnvironment'];
    }

    protected function handleErrorCreate(QueryException $exception, array $value): AppServiceException
    {
        if ($exception->getCode() === '23000') {
            $this->logger->warning("Duplicate channel name detected", [
                'name' => $value['name'] ?? null,
            ]);
            return new ConflictServiceException("Channel with name '{$value['name']}' already exists.");
        }
        $this->logger->error("Database query error during channel creation", [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ]);
        return new InternalServiceException("Error when creating channel: {$exception->getMessage()}");

    }

    protected function handleErrorUpdate(QueryException $exception, array $value): AppServiceException
    {
        if ($exception->getCode() === '23000') {
            $this->logger->warning("Channel name conflict detected", [
                'name' => $value['name'] ?? null,
            ]);
            return new ConflictServiceException("Channel name already exists.");
        }
        $this->logger->error("Database query error during channel update", [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ]);
        return new InternalServiceException("Error when updating channel: {$exception->getMessage()}");

    }
}
