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
use App\Service\CommonService;
use App\Service\Contracts\ServicesEnvironmentsService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ServicesEnvironmentsServiceImpl extends CommonService implements ServicesEnvironmentsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        ServicesEnvironments $model,
        Logger $logger
    ) {
        parent::__construct(
            $authFactory,
            $model,
            ResourcesTypes::SERVICES_ENVIRONMENTS,
            $logger,
            ['service.namespace', 'environment', 'configurations.channel']
        );
        $this->logger->info('ServicesEnvironmentsServiceImpl initialized');
    }


    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function getAll(PaginationRequest | null $data, bool $onlySvcEnv = false): LengthAwarePaginator|Collection
    {
        return parent::getAll($data, $onlySvcEnv);
    }

    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function search(SearchPaginationRequest | null $data, bool $onlySvcEnv = false): LengthAwarePaginator|Collection
    {
        return parent::search($data, $onlySvcEnv);
    }

    /**
     * @param CreateServiceEnvironmentRequest|FormRequest $data
     * @return Collection
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function create(CreateServiceEnvironmentRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::create($data);
    }

    public function update(int $id, UpdateServiceEnvironmentRequest|FormRequest $data): Collection
    {
        return parent::update($id, $data);
    }


    protected function createModel(array $value): Model
    {
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

        return $row->load(['service.namespace', 'environment']);
    }

    protected function handleErrorCreate(QueryException $exception, array $value): AppServiceException
    {
        if ($exception->getCode() === '23000') {
            $this->logger->warning('Duplicate (service_id, environment_id) detected on create', [
                'service_id'     => $value['service_id'] ?? null,
                'environment_id' => $value['environment_id'] ?? null,
                'error_code'     => $exception->getCode(),
            ]);
            return new ConflictServiceException("The pair (service_id, environment_id) already exists.");
        }
        $this->logger->error('Database query error during ServiceEnvironment creation', [
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
        ]);
        return new InternalServiceException("Error when creating service_environment: {$exception->getMessage()}");
    }

    protected function handleErrorUpdate(QueryException $exception, array $value): AppServiceException
    {
        if ($exception->getCode() === '23000') {
            $this->logger->warning('Duplicate (service_id, environment_id) detected on update', [
                'service_id'     => $value['service_id'] ?? null,
                'environment_id' => $value['environment_id'] ?? null,
                'error_code'     => $exception->getCode(),
            ]);
            throw new ConflictServiceException("The pair (service_id, environment_id) already exists.");
        }
        $this->logger->error('Database query error during ServiceEnvironment update', [
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
        ]);
        throw new InternalServiceException("Error when updating service_environment: {$exception->getMessage()}");
    }

    protected function updateModel(array $value, Model $model): void
    {
        $payload = [];
        if (array_key_exists('service_id', $value)) {
            $payload['service_id'] = (int) $value['service_id'];
        }
        if (array_key_exists('environment_id', $value)) {
            $payload['environment_id'] = (int) $value['environment_id'];
        }

        if (!empty($payload)) {
            $this->logger->debug('Applying update fields', $payload);
            $model->fill($payload)->save();
        }

        $this->logger->info('ServiceEnvironment updated successfully', [
            'id'             => $model->id,
            'service_id'     => $model->service_id,
            'environment_id' => $model->environment_id,
        ]);

        $model->fresh()->load(['service.namespace', 'environment']);
    }

    protected function searchModel(Builder $query, string $value): void
    {

        if ($value !== '') {
            $this->logger->debug('Applying search filters', ['term' => $value]);
            $query->where(function ($q) use ($value) {
                $q->whereHas('service', fn($sq) => $sq->whereLike('name', "%{$value}%"))
                    ->orWhereHas('service.namespace', fn($nq) => $nq->whereLike('name', "%{$value}%"))
                    ->orWhereHas('environment', fn($eq) => $eq->whereLike('name', "%{$value}%"));
            });
        }
    }

    protected function getRelationshipsByID(): array
    {
        return ['service.namespace', 'environment', 'configurations.channel'];
    }
}
