<?php

namespace App\Service\Implements;

use App\Exceptions\AppServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Exceptions\ValidationServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Models\Namespaces;
use App\Service\Contracts\NamespacesService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NamespacesServiceImpl implements NamespacesService
{
    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        protected Namespaces $model,
        protected Logger $logger
    ) {
        $this->guard = $authFactory->guard('web');
    }

    /**
     * Return paginated or full collection (when page/size omitted or <=0).
     */
    public function getAll(PaginationRequest $data): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission("read");

            $this->logger->info("Start of getAll namespaces (service layer)");

            $value    = $data->validated();
            $page = (int)($value['page'] ?? 0);
            $size = (int)($value['size'] ?? 0);

            $query = $this->model->newQuery()->with(['services']);

            if ($page > 0 && $size > 0) {
                $this->logger->info("Apply pagination: page={$page}, size={$size}");
                /** @var LengthAwarePaginator $paginator */
                $paginator  = $query->paginate(perPage: $size, page: $page);
                $totalPages = max(1, (int)ceil($paginator->total() / $paginator->perPage()));

                if ($page > $totalPages) {
                    $this->logger->error("Page not found with current page {$page}");
                    throw new NotFoundServiceException("Page not found with current page {$page}.");
                }
                $this->logger->info("Successfully fetched namespaces page={$page} size={$size}");
                return $paginator;
            }

            $this->logger->info("Apply non-paginated query");
            $rows = $query->get();
            $this->logger->info("Successfully fetched namespaces (non-paginated), count={$rows->count()}");
            return $rows;
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException: {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in getAll: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to load namespaces. Please try again later.');
        }
    }

    /**
     * Same pattern as getAll, with LIKE filter by name.
     */
    public function search(SearchPaginationRequest $data): LengthAwarePaginator|Collection
    {
        try {
            $this->checkPermission("read");

            $this->logger->info("Start of search namespaces (service layer)");

            $value      = $data->validated();
            $searchValue   = trim((string)($value['search'] ?? ''));
            $page   = (int)($value['page'] ?? 0);
            $size   = (int)($value['size'] ?? 0);

            $query = $this->model->newQuery()->with(['services']);

            if ($searchValue !== '') {
                $query->whereLike('name', "%{$searchValue}%");
            }

            if ($page > 0 && $size > 0) {
                $this->logger->info("Apply pagination in search: page={$page}, size={$size}, term='{$searchValue}'");
                /** @var LengthAwarePaginator $paginator */
                $paginator  = $query->paginate(perPage: $size, page: $page);
                $totalPages = max(1, (int)ceil($paginator->total() / $paginator->perPage()));

                if ($page > $totalPages) {
                    $this->logger->error("Search page not found: page {$page}");
                    throw new NotFoundServiceException("Page not found with current page {$page}.");
                }
                $this->logger->info("Search fetched page={$page} size={$size} total={$paginator->total()}");
                return $paginator;
            }

            $rows = $query->get();
            $this->logger->info("Search non-paginated fetched count={$rows->count()} term='{$searchValue}'");
            return $rows;
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException (search): {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error("Unhandled exception in search: {$e->getMessage()}", ['exception' => $e]);
            throw new InternalServiceException('Failed to search namespaces. Please try again later.');
        }
    }

    /**
     * @param string $action
     * @return void
     * @throws PermissionDeniedServiceException
     * @throws UnauthorizedServiceException
     */
    public function checkPermission(string $action): void
    {
        $user = $this->guard->user();

        if (!$user) {
            $this->logger->warning("User not authenticated");
            throw new UnauthorizedServiceException("User not authenticated, must be logged in.");
        }

        if (!$user->hasPermission('namespaces', $action)) {
            $this->logger->warning("{$user->name} does not have permission to read namespaces");
            throw new PermissionDeniedServiceException("User does not have permission to read namespaces.");
        }
    }
}
