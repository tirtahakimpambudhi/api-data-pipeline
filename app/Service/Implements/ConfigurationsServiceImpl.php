<?php

namespace App\Service\Implements;

use App\Constants\ActionsTypes;
use App\Constants\ResourcesTypes;
use App\Exceptions\AppServiceException;
use App\Exceptions\ConflictServiceException;
use App\Exceptions\InternalServiceException;
use App\Exceptions\InvalidArgumentServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Configurations\CreateConfigurationRequest;
use App\Http\Requests\Configurations\UpdateConfigurationRequest;
use App\Http\Resources\Configurations\Destination;
use App\Http\Resources\Configurations\Source;
use App\Models\Configurations;
use App\Service\CommonService;
use App\Service\Contracts\ConfigurationsService;
use App\Service\Contracts\TransformService;
use App\Traits\ConfigurationUtilities;
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

class ConfigurationsServiceImpl extends CommonService implements ConfigurationsService
{
    use Helpers, ConfigurationUtilities;

    protected StatefulGuard|Guard $guard;

    public function __construct(
        AuthFactory $authFactory,
        Configurations $model,
        Logger $logger,
        protected TransformService $transformService,
    ) {
        parent::__construct(
            $authFactory,
            $model,
            ResourcesTypes::CONFIGURATIONS,
            $logger,
            ['serviceEnvironment.service.namespace','serviceEnvironment.environment','channel']
        );
        $this->logger->info("ConfigurationsServiceImpl initialized");
    }

    /**
     * Write an informational message to the log.
     */
    protected function info($string, $verbosity = null)
    {
        $this->logger->info($string, [
            'verbosity' => $verbosity,
            'context' => 'ConfigurationsServiceImpl',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Write an error message to the log.
     */
    protected function error($string, $verbosity = null)
    {
        $this->logger->error($string, [
            'verbosity' => $verbosity,
            'context' => 'ConfigurationsServiceImpl',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }


    protected function warn($string, $verbosity = null)
    {
        $this->logger->warning($string, [
            'verbosity' => $verbosity,
            'context' => 'ConfigurationsServiceImpl',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Write a plain line message to the log (generic purpose).
     */
    protected function line($string, $style = null, $verbosity = null)
    {
        $level = match ($style) {
            'error' => 'error',
            'warn', 'warning' => 'warning',
            'debug' => 'debug',
            'comment', 'info', null => 'info',
            default => 'info',
        };

        $this->logger->{$level}($string, [
            'style' => $style,
            'verbosity' => $verbosity,
            'context' => 'ConfigurationsServiceImpl',
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * @throws AppServiceException
     * @throws InternalServiceException
     */
    public function getAll(PaginationRequest | null $data, bool $onlyConf = false): LengthAwarePaginator|Collection
    {
        return parent::getAll($data, $onlyConf);
    }

    public function search(SearchPaginationRequest | null $data, bool $onlyConf = false): LengthAwarePaginator|Collection
    {
        $this->logger->info("Searching configurations", ['onlyConf' => $onlyConf]);
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
            if (!$onlyConf) {
                $query->with(['serviceEnvironment.service.namespace','serviceEnvironment.environment','channel']);
            };

            if ($term !== '') {
                $query->where(function($q) use ($term) {
                    $q->whereHas('channel', fn($cq) => $cq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('serviceEnvironment.service', fn($sq) => $sq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('serviceEnvironment.environment', fn($eq) => $eq->whereLike('name', "%{$term}%"))
                        ->orWhereHas('serviceEnvironment.service.namespace', fn($nq) => $nq->whereLike('name', "%{$term}%"));
                });
            }

            $result = ($page > 0 && $size > 0)
                ? $this->applyPagination($query, $page, $size)
                : $query->get();

            $this->logger->info("Search completed", [
                'term' => $term,
                'count' => $result instanceof Collection ? $result->count() : $result->total()
            ]);

            return $result;
        } catch (AppServiceException $e) {
            $this->logger->error("AppServiceException: {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error("Error during configuration search", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new InternalServiceException('Failed to search configurations. Please try again later.');
        }
    }

    /**
     * @param CreateConfigurationRequest|FormRequest $data
     * @return Collection
     * @throws AppServiceException
     * @throws InternalServiceException
     * @throws InvalidArgumentServiceException
     */
    public function create(CreateConfigurationRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::create($data);
    }

    /**
     * @param int $id
     * @param UpdateConfigurationRequest|FormRequest $data
     * @return Collection
     * @throws AppServiceException
     * @throws InternalServiceException
     * @throws InvalidArgumentServiceException
     */
    public function update(int $id, UpdateConfigurationRequest|\Illuminate\Foundation\Http\FormRequest $data): Collection
    {
        return parent::update($id, $data);
    }


    protected function getTransformService(): TransformService
    {
        $this->logger->debug("Returning TransformService instance");
        return $this->transformService;
    }

    protected function createModel(array $value): Model
    {
        $this->logger->info("Creating new configuration", [
            'service_environment_id' => $value['service_environment_id'],
            'channel_id'             => $value['channel_id'],
        ]);

        $source = new Source(
            url: $value['source']['url'],
            method: $value['source']['method'],
            headers: $value['source']['headers'],
            body: $value['source']['body'],
            timeout: $value['source']['timeout'],
            retryCount: $value['source']['retry_count'],
        );

        $this->logger->debug("Source configuration created", [
            'url'    => $source->url(),
            'method' => $source->method(),
        ]);

        $destination = new Destination(
            url: $value['destination']['url'],
            method: $value['destination']['method'],
            headers: $value['destination']['headers'],
            extract: $value['destination']['extract'],
            foreach: $value['destination']['foreach'],
            body_template: $value['destination']['body_template'],
            timeout: $value['destination']['timeout'],
            retryCount: $value['destination']['retry_count'],
            rangePerRequest: $value['destination']['range_per_request'] * 1000,
        );

        $this->logger->debug("Destination configuration created", [
            'url'    => $destination->url(),
            'method' => $destination->method(),
        ]);

        $this->logger->info("Starting validation of source and destination configuration");

        $results = $this->runSingleRequest(
            $source,
            $destination,
            'sequential',
            0,
        );

        $this->logger->info("Successfully validated source and destination configuration", [
            'results' => $results,
        ]);

        $this->logger->info("Saving configuration to database");

        $row = $this->model->newQuery()->create([
            'service_environment_id' => (int) $value['service_environment_id'],
            'channel_id'             => (int) $value['channel_id'],
            'cron_expression'      => $value['cron_expression'],
            'source'                 => $source,
            'destination'            => $destination,
        ]);

        $this->logger->info("Configuration created successfully", [
            'id'                     => $row->id,
            'service_environment_id' => $row->service_environment_id,
            'channel_id'             => $row->channel_id,
        ]);

        return $row->load(['serviceEnvironment.service.namespace', 'serviceEnvironment.environment', 'channel']);
    }

    protected function handleErrorCreate(QueryException $exception, array $value): AppServiceException
    {
        if ($exception->getCode() === '23000') {
            $this->logger->warning("Duplicate configuration detected", [
                'service_environment_id' => $value['service_environment_id'] ?? null,
                'channel_id'             => $value['channel_id'] ?? null,
                'error_code'             => $exception->getCode(),
            ]);

            return new ConflictServiceException("The pair (service_environment_id, channel_id) already exists.");
        }

        $this->logger->error("Database query error during configuration creation", [
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
        ]);

        return new InternalServiceException("Error when creating configuration: {$exception->getMessage()}");
    }

    protected function handleErrorUpdate(QueryException $exception, array $value): AppServiceException
    {
        if ($exception->getCode() === '23000') {
            $this->logger->warning("Duplicate configuration detected on update", [
                'payload'    => $value ?? [],
                'error_code' => $exception->getCode(),
            ]);
            return new ConflictServiceException("The pair (service_environment_id, channel_id) already exists.");
        }

        $this->logger->error("Database query error during configuration update", [
            'message' => $exception->getMessage(),
            'code'    => $exception->getCode(),
        ]);
        return new InternalServiceException("Error when updating configuration: {$exception->getMessage()}");

    }

    /**
     * @throws InvalidArgumentServiceException
     * @throws AppServiceException
     */
    protected function updateModel(array $value, Model $model): void
    {
        $this->logger->debug("Validated update payload", ['value'  => $value]);

        $payload = [];

        if (array_key_exists('service_environment_id', $value)) {
            $payload['service_environment_id'] = (int) $value['service_environment_id'];
        }
        if (array_key_exists('channel_id', $value)) {
            $payload['channel_id'] = (int) $value['channel_id'];
        }
        if (array_key_exists('cron_expression', $value)) {
            $payload['cron_expression'] = $value['cron_expression'];
        }

        $newSource = null;
        $newDestination = null;

        if (array_key_exists('source', $value)) {
            $srcVal = $value['source'];

            $newSource = new Source(
                url: $srcVal['url'],
                method: $srcVal['method'],
                headers: $srcVal['headers'],
                body: $srcVal['body'] ?? [],
                timeout: $srcVal['timeout'],
                retryCount: $srcVal['retry_count'],
            );

            $this->logger->debug("Source update candidate created", [
                'url'    => $newSource->url(),
                'method' => $newSource->method(),
            ]);

            $payload['source'] = $newSource;
        }

        if (array_key_exists('destination', $value)) {
            $dstVal = $value['destination'];

            $newDestination = new Destination(
                url: $dstVal['url'],
                method: $dstVal['method'],
                headers: $dstVal['headers'],
                extract: $dstVal['extract'],
                foreach: $dstVal['foreach'] ?? null,
                body_template: $dstVal['body_template'],
                timeout: $dstVal['timeout'],
                retryCount: $dstVal['retry_count'],
                rangePerRequest: ($dstVal['range_per_request'] ?? 1) * 1000,
            );

            $this->logger->debug("Destination update candidate created", [
                'url'    => $newDestination->url(),
                'method' => $newDestination->method(),
            ]);

            $payload['destination'] = $newDestination;
        }

        if ($newSource !== null || $newDestination !== null) {
            $this->logger->info("Starting validation of source/destination for update");

            $sourceForProbe = $newSource ?: $model->source;
            $destForProbe   = $newDestination ?: $model->destination;

            $results = $this->runSingleRequest(
                $sourceForProbe,
                $destForProbe,
                'sequential',
                0
            );

            $this->logger->info("Successfully validated updated source/destination", [
                'results' => $results,
            ]);
        } else {
            $this->logger->info("No source/destination changes detected; skipping connectivity validation");
        }
        $this->logger->info("Applied update", ['payload' => $payload, 'value' => $value]);
        if (!empty($payload)) {
            $this->logger->debug("Applying partial update", ['payload' => $payload]);
            $model->fill($payload)->save();
        } else {
            $this->logger->info("No field provided to update; nothing changed");
        }

        $this->logger->info("Configuration updated successfully");

        $model->fresh()
            ->load(['serviceEnvironment.service.namespace', 'serviceEnvironment.environment', 'channel']);

    }

    protected function searchModel(Builder $query, string $value): void
    {
        if ($value !== '') {
            $query->where(function($q) use ($value) {
                $q->whereHas('channel', fn($cq) => $cq->whereLike('name', "%{$value}%"))
                    ->orWhereHas('serviceEnvironment.service', fn($sq) => $sq->whereLike('name', "%{$value}%"))
                    ->orWhereHas('serviceEnvironment.environment', fn($eq) => $eq->whereLike('name', "%{$value}%"))
                    ->orWhereHas('serviceEnvironment.service.namespace', fn($nq) => $nq->whereLike('name', "%{$value}%"));
            });
        }
    }

    protected function getRelationshipsByID(): array
    {
        return ['serviceEnvironment.service.namespace', 'serviceEnvironment.environment', 'channel'];
    }
}

