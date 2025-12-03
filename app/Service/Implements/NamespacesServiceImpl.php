<?php

namespace App\Service\Implements;

use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Namespaces\CreateNamespaceRequest;
use App\Http\Requests\Namespaces\CreateServiceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Models\Namespaces;
use App\Service\CommonService;
use App\Service\Contracts\NamespacesService;
use App\Traits\Helpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NamespacesServiceImpl extends CommonService implements NamespacesService
{
    use Helpers;
    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        Namespaces $model,
        Logger $logger
    ) {
        parent::__construct(
            $authFactory,
            $model,
            ResourcesTypes::NAMESPACES,
            $logger,
            ['services', 'servicesEnvironments']
        );
        $this->logger->info("NamespacesServiceImpl initialized");
    }


    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function getAll(PaginationRequest | null $data, bool $onlyNamespace = false): LengthAwarePaginator|Collection
    {
        return parent::getAll($data, $onlyNamespace);
    }

    /**
     * @param SearchPaginationRequest|null $data
     * @param bool $onlyNamespace
     * @return LengthAwarePaginator|Collection
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function search(SearchPaginationRequest | null $data, bool $onlyNamespace = false): LengthAwarePaginator|Collection
    {
        return parent::search($data, $onlyNamespace);
    }

    public function getNamespaceOrFail(int $id) : \Illuminate\Database\Eloquent\Collection|Model
    {
        $namespace = $this->model->newQuery()->find($id);
        if (!$namespace) {
            $this->logger->error("Namespace not found id={$id}");
            throw new NotFoundServiceException("Namespace not found with id {$id}.");
        }
        return $namespace;
    }

    /**
     * @param CreateNamespaceRequest|FormRequest $data
     * @return Collection
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function create(CreateNamespaceRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::create($data);
    }


    /**
     * @param int $id
     * @param UpdateNamespaceRequest|FormRequest $data
     * @return Collection
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function update(int $id, UpdateNamespaceRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::update($id, $data);
    }


    public function createService(int $id, CreateServiceRequest $data): Collection
    {
        try {
            $user = $this->guard->user();

            if (!$user) {
                $this->logger->warning("User not authenticated");
                throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
            }

            if (!$user->hasPermission('services', "create")) {
                $this->logger->warning("{$user->name} does not have permission to create services");
                throw new PermissionDeniedServiceException("User does not have permission to create services.");
            }

            $this->logger->info("Start of create service with namespace id={$id} (service layer)");

            $value = $data->validated();
            $nameSvc = trim((string)$value['name']);

            $namespace = $this->getNamespaceOrFail($id);

            $namespace->services()->create([ 'name' => $nameSvc ]);
            $namespace->loadMissing('services');
            $this->logger->info("Successfully created service with namespace id={$id}");

            return collect( $namespace->refresh());
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (createService): {$e->getMessage()}");
            throw $e;
        } catch (QueryException $e) {
            $this->logger->error("QueryException (createService): {$e->getMessage()}");
            if ($e->getCode() === '23000') {
                throw new ConflictServiceException("Service name already exists.");
            }
            throw new InternalServiceException("Error when create service: {$e->getMessage()}");
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in create service: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to create service. Please try again later.');
        }
    }

    protected function createModel(array $value): Model
    {
        $name  = (string)($value['name'] ?? '');

        $this->logger->info("Attempt to create namespace with name='{$name}'");

        $namespace = $this->model->newQuery()->create(['name' => $name]);

        $this->logger->info("Namespace created with id={$namespace->id}");
        return $namespace;
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

        if (empty($payload)) {
            $this->logger->info("No updatable fields provided, skipping update");
            return;
        }

        $this->logger->info("Applying update for namespace", $payload);
        $model->fill($payload);
        $model->save();
    }

    protected function searchModel(Builder $query, string $value): void
    {
        if ($value !== '') {
            $query->whereLike('name',  "%{$value}%");
        }
    }

    protected function getRelationshipsByID(): array
    {
        return ['services.configurations', 'servicesEnvironments.channels', 'services.environments'];
    }

    protected function handleErrorCreate(QueryException $exception, array $value): AppServiceException
    {
        $this->logger->error("QueryException (create): {$exception->getMessage()}");
        // 23000 = integrity constraint violation (duplicate, FK, unique, dll)
        if ($exception->getCode() === '23000') {
            return new ConflictServiceException("Namespace with name '{$value['name']}' already exists.");
        }
        return new InternalServiceException("Error when creating namespace: {$exception->getMessage()}");

    }

    protected function handleErrorUpdate(QueryException $exception, array $value): AppServiceException
    {
        $this->logger->error("QueryException (update): {$exception->getMessage()}");
        if ($exception->getCode() === '23000') {
            return new ConflictServiceException("Namespace name already exists.");
        }
        return new InternalServiceException("Error when updating namespace: {$exception->getMessage()}");

    }
}
