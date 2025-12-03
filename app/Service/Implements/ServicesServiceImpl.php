<?php

namespace App\Service\Implements;

use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Services\CreateServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use App\Models\Services;
use App\Service\CommonService;
use App\Service\Contracts\ServicesService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Log\Logger;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
class ServicesServiceImpl extends CommonService implements ServicesService
{
    use Helpers;
    protected StatefulGuard|Guard $guard;
    /**
     * Create a new class instance.
     */
    public function __construct(
        AuthFactory $authFactory,
        Services $model,
        Logger $logger
    )
    {
        parent::__construct(
            $authFactory,
            $model,
            ResourcesTypes::SERVICES,
            $logger,
            ['configurations', 'namespace', 'servicesEnvironments', 'environments']
        );
        $this->logger->info("ServicesServiceImpl initialized");
    }


    public function getServiceOrFail(int $id) : \Illuminate\Database\Eloquent\Collection|Model
    {
        $service = $this->model->newQuery()->find($id);
        if (!$service) {
            $this->logger->error("Service not found id={$id}");
            throw new NotFoundServiceException("Service not found with id {$id}.");
        }
        return $service;
    }


    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function getAll(PaginationRequest | null $data, bool $onlyService = false): LengthAwarePaginator|Collection
    {
        return parent::getAll($data, $onlyService);
    }


    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function search(SearchPaginationRequest | null $data, bool $onlyService = false): LengthAwarePaginator|Collection
    {
        return parent::search($data, $onlyService);
    }

    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function create(CreateServiceRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::create($data);
    }

    /**
     * @param int $id
     * @param UpdateServiceRequest|FormRequest $data
     * @return Collection
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function update(int $id, UpdateServiceRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::update($id, $data);
    }

    protected function createModel(array $value): Model
    {
        $name  = (string)($value['name'] ?? '');
        $namespaceId  = (string)($value['namespace_id'] ?? '');

        $this->logger->info("Attempt to create service with name='{$name}'");

        $svc = $this->model->newQuery()->create(['name' => $name, 'namespace_id' => $namespaceId]);
        $svc->loadMissing([
            'namespace',
            'environments',
            'configurations',
        ]);
        $this->logger->info("Services created with id={$svc->id}");
        return $svc;
    }

    protected function handleErrorCreate(QueryException $exception, array $value): AppServiceException
    {
        $this->logger->error("QueryException (create): {$exception->getMessage()}");
        if ($exception->getCode() === '23000') {
            return new ConflictServiceException("Services with name '{$value['name']}' already exists.");
        }
        return new InternalServiceException("Error when creating services: {$exception->getMessage()}");
    }

    protected function handleErrorUpdate(QueryException $exception, array $value): AppServiceException
    {
        $this->logger->error("QueryException (update): {$exception->getMessage()}");
        if ($exception->getCode() === '23000') {
            return new ConflictServiceException("Namespace name already exists.");
        }
        return new InternalServiceException("Error when updating services: {$exception->getMessage()}");
    }

    protected function updateModel(array $value, Model $model): void
    {
        $payload = [];
        if (array_key_exists('name', $value)) {
            $name = trim((string)$value['name']);
            if ($name !== '') {
                $payload['name'] = $name;
            } else {
                $this->logger->info("Update skip 'name' (empty input)");
            }
        }

        if (array_key_exists('namespace_id', $value)) {
            $namespaceId = trim((int)$value['namespace_id']);
            if ($namespaceId != 0) {
                $payload['namespace_id'] = $namespaceId;
            } else {
                $this->logger->info("Update skip 'namespace_id' (empty input)");
            }
        }

        if (empty($payload)) {
            $this->logger->info("No updatable fields provided; skipping update");
            return;
        }

        $this->logger->info("Applying update", $payload);
        $model->fill($payload);
        $model->save();

        $this->logger->info("Successfully updated services");

    }

    protected function searchModel(Builder $query, string $value): void
    {
        if ($value !== '') {
            $query->whereLike('name', "%{$value}%");
        }
    }

    protected function getRelationshipsByID(): array
    {
        return ['configurations', 'namespace', 'servicesEnvironments.channels', 'environments'];
    }
}
