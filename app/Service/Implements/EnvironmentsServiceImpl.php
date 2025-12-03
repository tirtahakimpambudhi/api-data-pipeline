<?php

namespace App\Service\Implements;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Environments\CreateEnvironmentRequest;
use App\Http\Requests\Environments\UpdateEnvironmentRequest;
use App\Models\Environments;
use App\Service\CommonService;
use App\Service\Contracts\EnvironmentsService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EnvironmentsServiceImpl extends CommonService implements EnvironmentsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        Environments $model,
        Logger $logger
    ) {
        parent::__construct(
            $authFactory,
            $model,
            ResourcesTypes::ENVIRONMENTS,
            $logger,
            ['services', 'servicesEnvironments', 'configurations']
        );
        $this->logger->info("EnvironmentsServiceImpl initialized");
    }

    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function getAll(PaginationRequest | null $data, bool $onlyEnv = false): LengthAwarePaginator|Collection
    {
        return parent::getAll($data, $onlyEnv);
    }

    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function search(SearchPaginationRequest | null $data, bool $onlyEnv = false): LengthAwarePaginator|Collection
    {
        return parent::search($data, $onlyEnv);
    }

    /**
     * @throws AppServiceException
     * @throws ConflictServiceException
     * @throws InternalServiceException
     */
    public function create(CreateEnvironmentRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::create($data);
    }

    /**
     * @throws AppServiceException
     * @throws ConflictServiceException
     * @throws InternalServiceException
     */
    public function update(int $id, UpdateEnvironmentRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::update($id, $data);
    }


    protected function createModel(array $value): Model
    {
        return $this->model->newQuery()->create(['name' => (string)$value['name']]);
    }

    protected function updateModel(array $value, Model $model): void
    {
        $payload = [];
        if (array_key_exists('name', $value)) {
            $name = trim((string)$value['name']);
            if ($name !== '') $payload['name'] = $name;
        }

        if (!empty($payload)) {
            $model->fill($payload)->save();
        }
    }

    protected function getRelationshipsByID(): array
    {
        return ['services', 'servicesEnvironments.service', 'configurations.channel'];
    }

    protected function searchModel(Builder $query, string $value)
    {
        if ($value !== '') {
            $query->whereLike('name', "%{$value}%");
        }
    }

    protected function handleErrorCreate(QueryException $exception, array $value): AppServiceException
    {
        if ($exception->getCode() === '23000') {
            return new ConflictServiceException("Environment with name '{$value['name']}' already exists.");
        }
        return new InternalServiceException("Error when creating environment: {$exception->getMessage()}");

    }

    protected function handleErrorUpdate(QueryException $exception, array $value): AppServiceException
    {
        if ($exception->getCode() === '23000') {
            return new ConflictServiceException("Environment name already exists.");
        }
        return new InternalServiceException("Error when updating environment: {$exception->getMessage()}");

    }
}
