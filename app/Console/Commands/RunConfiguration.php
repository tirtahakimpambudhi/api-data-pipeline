<?php

namespace App\Console\Commands;

use App\Models\Configurations;
use App\Service\Contracts\TransformService;
use App\Http\Resources\Configurations\Source;
use App\Http\Resources\Configurations\Destination;
use App\Traits\ConfigurationUtilities;
use Illuminate\Console\Command;

class RunConfiguration extends Command
{
    use ConfigurationUtilities;

    protected $signature = 'configuration:run
                            {id : Configuration ID to run}
                            {--probe : Run in probe mode}
                            {--method=sequential : Request method (pool|sequential|batch)}
                            {--batch-size=5 : Batch size when using batch method}';

    protected $description = 'Running Configuration Single Record With ID';

    private const METHOD_POOL = 'pool';
    private const METHOD_SEQUENTIAL = 'sequential';
    private const METHOD_BATCH = 'batch';

    private const VALID_METHODS = [
        self::METHOD_POOL,
        self::METHOD_SEQUENTIAL,
        self::METHOD_BATCH,
    ];

    public function __construct(private TransformService $transform)
    {
        parent::__construct();
    }

    /**
     * Get transform service for trait
     */
    protected function getTransformService(): TransformService
    {
        return $this->transform;
    }

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $method = strtolower($this->option('method'));
        $batchSize = (int) $this->option('batch-size');

        // Validate method
        if (!in_array($method, self::VALID_METHODS)) {
            $this->error("❌ Invalid method: {$method}");
            $this->line("   Valid methods: " . implode(', ', self::VALID_METHODS));
            return self::FAILURE;
        }

        // Validate batch size
        if ($method === self::METHOD_BATCH && $batchSize < 1) {
            $this->error("❌ Batch size must be at least 1");
            return self::FAILURE;
        }

        $this->info("📋 Loading configuration ID: {$id}");
        $this->line("   Request method: {$method}" . ($method === self::METHOD_BATCH ? " (size: {$batchSize})" : ""));

        $config = Configurations::query()->find($id);
        if (!$config) {
            $this->error("❌ Configuration with ID {$id} not found.");
            return self::FAILURE;
        }

        /** @var Source|null $source */
        $source = $config->source;

        /** @var Destination|null $destination */
        $destination = $config->destination;

        if (!$source || !$destination) {
            $this->error("❌ Configuration ID {$id} has incomplete source or destination.");
            return self::FAILURE;
        }

        $this->info("✅ Configuration loaded successfully");
        $this->line("   Source: {$source->method()} {$source->url()}");
        $this->line("   Destination: {$destination->method()} {$destination->url()}");

        if ($this->option('probe')) {
            return $this->runProbeMode($source, $destination, $id, $method, $batchSize);
        }

        return $this->runExecutionMode($source, $destination, $id, $method, $batchSize);
    }
}
