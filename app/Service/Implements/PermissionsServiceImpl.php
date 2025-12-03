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
use App\Service\CommonService;
use App\Service\Contracts\PermissionsService;
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

class PermissionsServiceImpl extends CommonService implements PermissionsService
{
    use Helpers;

    protected StatefulGuard|Guard $guard;
    /**
     * Create a new class instance.
     */
    public function __construct(
        AuthFactory $authFactory,
        Permissions $model,
        Logger $logger
    )
    {
        parent::__construct(
            $authFactory,
            $model,
            ResourcesTypes::PERMISSIONS,
            $logger,
            ['roles','rolesPermissions']
        );
        $this->logger->info("PermissionsServiceImpl initialized");
    }

    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function getAll(?PaginationRequest $data, bool $onlyPermissions = false): LengthAwarePaginator|Collection
    {
        return parent::getAll($data, $onlyPermissions);
    }

    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function search(?SearchPaginationRequest $data, bool $onlyPermissions = false): LengthAwarePaginator|Collection
    {
        return parent::search($data, $onlyPermissions);
    }

    protected function createModel(array $value): Model
    {
        return $this->model;
    }

    protected function handleErrorCreate(QueryException $exception, array $value): AppServiceException
    {
        return new InternalServiceException("unused action");
    }

    protected function handleErrorUpdate(QueryException $exception, array $value): AppServiceException
    {
        return new InternalServiceException("unused action");
    }

    protected function updateModel(array $value, Model $model): void
    {
        // TODO: Implement updateModel() method.
    }

    protected function searchModel(Builder $query, string $value): void
    {
        if ($value !== '') {
            $this->logger->debug('Applying search filters', ['term' => $value]);

            $query->where(function ($q) use ($value) {
                $q->whereLike('resource_type', "%{$value}%")
                    ->orWhereLike('action', "%{$value}%");
            });
        }
    }

    protected function getRelationshipsByID(): array
    {
        return ['roles', 'rolesPermissions'];
    }
}
