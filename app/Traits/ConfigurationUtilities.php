<?php

namespace App\Traits;

use App\Exceptions\AppServiceException;
use App\Exceptions\InvalidArgumentServiceException;
use App\Http\Resources\Configurations\Destination;
use App\Http\Resources\Configurations\Source;
use App\Service\Contracts\TransformService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

trait ConfigurationUtilities
{
    /**
     * Transform service instance
     * Must be provided by the class using this trait
     */
    abstract protected function getTransformService(): TransformService;


    abstract protected function info($string, $verbosity = null);
    abstract protected function warn($string, $verbosity = null);
    abstract protected function error($string, $verbosity = null);
    abstract protected function line($string, $style = null, $verbosity = null);

    /**
     * Get SUCCESS constant (for Command compatibility)
     */
    protected function getSuccessCode(): int
    {
        return defined('static::SUCCESS') ? static::SUCCESS : 0;
    }

    /**
     * Get FAILURE constant (for Command compatibility)
     */
    protected function getFailureCode(): int
    {
        return defined('static::FAILURE') ? static::FAILURE : 1;
    }

    // ------------------------------------------------------------------------------------
    // High-level run modes
    // ------------------------------------------------------------------------------------

    private function runProbeMode(
        Source $source,
        Destination $destination,
        int $id,
        string $method,
        int $batchSize
    ): int {
        $this->info("🔍 Running in PROBE mode for configuration ID {$id}...");

        try {
            $ok = $this->probe($source, $destination, $method, $batchSize);

            if ($ok) {
                $this->info("✅ Probe completed successfully");
                return $this->getSuccessCode();
            }

            $this->error("❌ Probe failed");
            return $this->getFailureCode();
        } catch (\Throwable $e) {
            $this->error("❌ Probe failed with exception: {$e->getMessage()}");
            $this->line("📍 File: {$e->getFile()}:{$e->getLine()}", 'v');
            return $this->getFailureCode();
        }
    }

    private function runExecutionMode(
        Source $source,
        Destination $destination,
        int $id,
        string $method,
        int $batchSize
    ): int {
        $this->info("🚀 Running execution mode for configuration ID {$id}...");

        try {
            $result = $this->exec($source, $destination, $method, $batchSize);

            $this->info("✅ Source responded with status: {$result['source_status']}");
            $this->line("📤 Fanout count: {$result['fanout']}");

            if ($result['fanout'] === 0) {
                $this->warn("⚠️  No payloads to send to destination");
                return $this->getSuccessCode();
            }

            $this->info(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $successCount = 0;
            $failureCount = 0;
            foreach ($result['results'] as $i => $r) {
                if (!empty($r['error'])) {
                    $failureCount++;
                    $this->error("❌ [{$i}] Error: {$r['error']}");
                    $this->line("   Status: {$r['status']}", 'v');
                } else {
                    $successCount++;
                    $this->info("✅ [{$i}] Status: {$r['status']}");
                }
            }

            $this->line("📊 Summary: {$successCount} succeeded, {$failureCount} failed");

            return $failureCount > 0 ? $this->getFailureCode() : $this->getSuccessCode();
        } catch (\Throwable $e) {
            $this->error("❌ Execution failed: {$e->getMessage()}");
            $this->line("📍 File: {$e->getFile()}:{$e->getLine()}", 'v');
            $this->line("🔍 Trace: {$e->getTraceAsString()}", 'vv');
            return $this->getFailureCode();
        }
    }

    private function runSingleRequest(
        Source $source,
        Destination $destination,
        string $method,
        int $batchSize
    ) {
        $this->info("🚀 Running execution mode for configuration source destination ...");

        try {
            $result = $this->singleExec($source, $destination, $method, $batchSize);

            if ($result['source_status'] >= 400 && $result['source_status'] < 500) {
                $sourceResponse = json_encode($result['source_body'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                throw new InvalidArgumentServiceException("invalid configuration source try request and result status code {$result['source_status']}.\n{$sourceResponse}");
            }

            if ($result['source_status'] >= 500) {
                $sourceResponse = json_encode($result['source_body'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                throw new InvalidArgumentServiceException("something wrong with source server, try request and result status code {$result['source_status']}.\n{$sourceResponse}");
            }

            $this->info("✅ Source responded with status: {$result['source_status']}");
            $this->line("📤 Fanout count: {$result['fanout']}");

            if ($result['fanout'] === 0) {
                $this->warn("⚠️  No payloads to send to destination");
                return [
                    'success_count' => 0,
                    'failure_count' => 0,
                    'results' => $result,
                ];
            }

            $successCount = 0;
            $failureCount = 0;
            foreach ($result['results'] as $i => $r) {
                if (($r['status'] >= 400 && $r['status'] < 500) && !empty($r['error'])) {
                    throw new InvalidArgumentServiceException("invalid configuration destination try request and result status code {$r['status']}. {$r['error']}");
                }

                if ($r['status'] >= 500 && !empty($r['error'])) {
                    throw new InvalidArgumentServiceException("something wrong with destination server, try request and result status code {$r['status']}. {$r['error']}");
                }

                if (!empty($r['error'])) {
                    $failureCount++;
                    $this->error("❌ [{$i}] Error: {$r['error']}");
                    $this->line("   Status: {$r['status']}", 'v');
                } else {
                    $successCount++;
                    $this->info("✅ [{$i}] Status: {$r['status']}");
                }
            }

            $this->line("📊 Summary: {$successCount} succeeded, {$failureCount} failed");

            return [
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $result,
            ];
        } catch (AppServiceException|\Exception $e) {
            $this->error("❌ Execution failed when try request in source or destination: {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            $this->error("❌ Execution failed: {$e->getMessage()}");
            $this->line("📍 File: {$e->getFile()}:{$e->getLine()}", 'v');
            $this->line("🔍 Trace: {$e->getTraceAsString()}", 'vv');
            return $e;
        }
    }

    // ------------------------------------------------------------------------------------
    // Unified HTTP helpers (no auto-throw; normalize outcomes)
    // ------------------------------------------------------------------------------------

    /**
     * Response|RequestException|Throwable -> normalized array outcome.
     */
    private function normalizeHttpOutcome(mixed $item): array
    {
        if ($item instanceof Response) {
            return [
                'ok'     => $item->successful(),
                'status' => $item->status(),
                'body'   => $this->decode($item),
                'error'  => null,
                'raw'    => $item,
            ];
        }

        if ($item instanceof RequestException) {
            $resp   = $item->response;
            $status = $resp ? $resp->status() : 0;

            return [
                'ok'     => false,
                'status' => $status,
                'body'   => $resp ? $this->decode($resp) : $item->getMessage(),
                'error'  => $resp ? $resp->body() : $item->getMessage(),
                'raw'    => $resp ?: $item,
            ];
        }

        if ($item instanceof \Throwable) {
            return [
                'ok'     => false,
                'status' => 0,
                'body'   => $item->getMessage(),
                'error'  => $item->getMessage(),
                'raw'    => $item,
            ];
        }

        return [
            'ok'     => false,
            'status' => 0,
            'body'   => is_object($item) ? get_class($item) : (string) $item,
            'error'  => 'unknown-error',
            'raw'    => $item,
        ];
    }

    /**
     * Execute HTTP without calling ->throw(); catch and normalize any auto-throw.
     */
    private function performHttp(
        PendingRequest $req,
        string $method,
        string $url,
        mixed $body
    ): array {
        try {
            $resp = $this->executeHttpMethod($req, $method, $url, $body); // jangan ->throw()
            return $this->normalizeHttpOutcome($resp);
        } catch (RequestException $e) {
            return $this->normalizeHttpOutcome($e);
        } catch (\Throwable $e) {
            return $this->normalizeHttpOutcome($e);
        }
    }

    /**
     * Build PendingRequest dengan unit waktu yang benar.
     * - timeout: detik
     * - retry sleep: milidetik
     */
    private function buildRequest(array $headers, int $timeoutSeconds, int $retryTimes, int $retrySleepMs): PendingRequest
    {
        $req = Http::withHeaders($headers)
            ->timeout($timeoutSeconds)            // detik!
            ->retry($retryTimes, $retrySleepMs);  // milidetik

        $contentType = $headers['Content-Type'] ?? null;
        return $this->applyContentType($req, $contentType);
    }

    private function requestSource(Source $source): array
    {
        $this->line("   URL: {$source->method()} {$source->url()}", 'v');
        $this->line("   Timeout: {$source->timeout()}s, Retry: {$source->retryCount()}x", 'v');

        $req  = $this->buildRequest($source->headers(), $source->timeout(), $source->retryCount(), 300);
        $body = $this->prepareBodyData($source->body());

        return $this->performHttp($req, $source->method(), $source->url(), $body);
    }

    private function assertSourceOk(array $outcome): void
    {
        if ($outcome['ok']) {
            return;
        }

        $status = (int) ($outcome['status'] ?? 0);
        $body   = $outcome['body'];
        $bodyStr = is_scalar($body) ? (string) $body : json_encode($body, JSON_UNESCAPED_SLASHES);

        if ($status >= 400 && $status < 500) {
            throw new InvalidArgumentServiceException("invalid configuration source (HTTP {$status}): {$bodyStr}");
        }
        if ($status >= 500) {
            throw new InvalidArgumentServiceException("source server error (HTTP {$status}): {$bodyStr}");
        }

        throw new InvalidArgumentServiceException("source unknown failure: {$bodyStr}");
    }

    // ------------------------------------------------------------------------------------
    // Sending strategies
    // ------------------------------------------------------------------------------------

    /**
     * Send requests sequentially (one by one) using performHttp()
     */
    private function sendSequentialRequests(array $payloads, Destination $dst): array
    {
        $responses = [];
        $total = count($payloads);

        $this->info("📤 Sending {$total} requests sequentially...");

        foreach ($payloads as $i => $p) {
            $index = $i + 1;
            $this->line("🔄 [{$index}/{$total}] Preparing request", 'v');

            $headers = $p['headers'] ?? [];
            $body    = $this->prepareBodyData($p['body'] ?? []);

            $req = $this->buildRequest(
                $headers,
                $dst->timeout(),
                $dst->retryCount(),
                $dst->rangePerRequest()
            );

            $out = $this->performHttp($req, $dst->method(), $dst->url(), $body);

            if ($out['ok']) {
                $this->info("✅ [{$index}/{$total}] Completed: HTTP {$out['status']}");
            } else {
                $this->error("❌ [{$index}/{$total}] HTTP {$out['status']} - " . substr((string)($out['error'] ?? 'unknown error'), 0, 200));
            }

            $responses[] = $out;

            // rate limit antar request
            if ($i < $total - 1) {
                $sleepSeconds = max(0, (int) ceil($dst->rangePerRequest() / 1000)); // ms -> s
                if ($sleepSeconds > 0) {
                    $this->line("⏳ Waiting {$sleepSeconds} second(s)...", 'v');
                    sleep($sleepSeconds);
                }
            }
        }

        return $responses;
    }

    /**
     * Send requests in batches (concurrent per batch) using Http::pool
     * Catatan: kita tetap menghindari ->throw() dan menormalkan hasil.
     */
    private function sendBatchedRequests(array $payloads, Destination $dst, int $batchSize = 5): array
    {
        $allOutcomes = [];
        $batches = array_chunk($payloads, max(1, $batchSize));
        $totalBatches = count($batches);

        $this->info("📦 Sending requests in {$totalBatches} batches (size: {$batchSize})");

        foreach ($batches as $batchIndex => $batch) {
            $batchNum = $batchIndex + 1;
            $this->line("🔄 Processing batch {$batchNum}/{$totalBatches} (" . count($batch) . " requests)", 'v');

            try {
                $responses = Http::pool(function (Pool $pool) use ($batch, $dst) {
                    $reqs = [];
                    foreach ($batch as $p) {
                        $headers = $p['headers'] ?? [];
                        $body    = $this->prepareBodyData($p['body'] ?? []);
                        $contentType = $headers['Content-Type'] ?? null;

                        // Pool per-request config
                        $r = $pool
                            ->withHeaders($headers)
                            ->timeout($dst->timeout())
                            ->retry($dst->retryCount(), $dst->rangePerRequest());

                        // Apply content-type per-request
                        $r = $this->applyContentType($r, $contentType);

                        // build request per method (tanpa ->throw())
                        $reqs[] = match ($dst->method()) {
                            RequestAlias::METHOD_GET    => $r->get($dst->url()),
                            RequestAlias::METHOD_POST   => $r->post($dst->url(), $body),
                            RequestAlias::METHOD_PUT    => $r->put($dst->url(), $body),
                            RequestAlias::METHOD_PATCH  => $r->patch($dst->url(), $body),
                            RequestAlias::METHOD_DELETE => $r->delete($dst->url(), $body),
                            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$dst->method()}"),
                        };
                    }
                    return $reqs;
                });

                // Normalize semua item hasil pool
                foreach ($responses as $r) {
                    $allOutcomes[] = $this->normalizeHttpOutcome($r);
                }
            } catch (RequestException $e) {
                // Jika pool melempar karena auto-throw global → normalisasi
                $allOutcomes[] = $this->normalizeHttpOutcome($e);
            } catch (\Throwable $e) {
                $allOutcomes[] = $this->normalizeHttpOutcome($e);
            }

            // ringkasan batch
            $batchSuccess = 0;
            $batchFailed  = 0;
            $hasClientErr = false;
            $lastIndex = count($allOutcomes) - 1;

            // hitung hanya outcome untuk batch ini
            $startIndex = $lastIndex - count($batch) + 1;
            $startIndex = max(0, $startIndex);

            for ($i = $startIndex; $i <= $lastIndex; $i++) {
                $o = $allOutcomes[$i];
                if ($o['ok']) {
                    $batchSuccess++;
                } else {
                    $batchFailed++;
                    if ($o['status'] >= 400 && $o['status'] < 500) {
                        $hasClientErr = true;
                        $this->error("❌ HTTP {$o['status']}: " . substr((string)($o['error'] ?? ''), 0, 200));
                    }
                }
            }

            $this->info("✅ Batch {$batchNum} completed: {$batchSuccess} success, {$batchFailed} failed");

            if ($hasClientErr) {
                throw new \RuntimeException("Batch {$batchNum} contains client errors (4xx). Stopping execution.");
            }

            // jeda antar batch (opsional kecil agar tidak menekan server)
            if ($batchIndex < $totalBatches - 1) {
                $sleepSeconds = 3;
                $this->line("⏳ Waiting {$sleepSeconds} seconds before next batch...", 'v');
                sleep($sleepSeconds);
            }
        }

        return $allOutcomes;
    }

    /**
     * Send all requests concurrently (pool mode)
     */
    private function sendPooledRequests(array $payloads, Destination $dst): array
    {
        $total = count($payloads);
        $this->info("📤 Sending {$total} requests concurrently (pool mode)...");
        $this->warn("⚠️  Note: All requests will be sent simultaneously!");

        $outcomes = [];

        try {
            $responses = Http::pool(function (Pool $pool) use ($payloads, $dst) {
                $reqs = [];
                foreach ($payloads as $p) {
                    $headers = $p['headers'] ?? [];
                    $body    = $this->prepareBodyData($p['body'] ?? []);
                    $contentType = $headers['Content-Type'] ?? null;

                    $r = $pool
                        ->withHeaders($headers)
                        ->timeout($dst->timeout())
                        ->retry($dst->retryCount(), $dst->rangePerRequest());

                    $r = $this->applyContentType($r, $contentType);

                    $reqs[] = match ($dst->method()) {
                        RequestAlias::METHOD_GET    => $r->get($dst->url()),
                        RequestAlias::METHOD_POST   => $r->post($dst->url(), $body),
                        RequestAlias::METHOD_PUT    => $r->put($dst->url(), $body),
                        RequestAlias::METHOD_PATCH  => $r->patch($dst->url(), $body),
                        RequestAlias::METHOD_DELETE => $r->delete($dst->url(), $body),
                        default => throw new \InvalidArgumentException("Unsupported HTTP method: {$dst->method()}"),
                    };
                }
                return $reqs;
            });

            foreach ($responses as $r) {
                $outcomes[] = $this->normalizeHttpOutcome($r);
            }
        } catch (RequestException $e) {
            $outcomes[] = $this->normalizeHttpOutcome($e);
        } catch (\Throwable $e) {
            $outcomes[] = $this->normalizeHttpOutcome($e);
        }

        // stop lebih dini jika ada 4xx (konsisten dengan batch)
        foreach ($outcomes as $i => $o) {
            if (!$o['ok'] && $o['status'] >= 400 && $o['status'] < 500) {
                $this->error("❌ [{$i}] HTTP {$o['status']}: " . substr((string)($o['error'] ?? ''), 0, 200));
                throw new InvalidArgumentServiceException("Pool request [{$i}] failed with HTTP {$o['status']}");
            }
        }

        return $outcomes;
    }

    // ------------------------------------------------------------------------------------
    // Main pipelines (probe / exec / singleExec)
    // ------------------------------------------------------------------------------------

    private function probe(Source $source, Destination $dst, string $method, int $batchSize): bool
    {
        $this->info("🔄 Step 1: Requesting to source endpoint");

        $src = $this->requestSource($source);
        if (!$src['ok']) {
            $this->error("❌ Source returned non-2xx status: {$src['status']}");
            return false;
        }

        $this->info("📥 Source response status: {$src['status']}");
        $sourceBody = $src['body'];
        $this->line("📦 Source response decoded successfully", 'v');

        // Build payloads
        $this->info("🔄 Step 2: Building payloads for destination");
        $payloads = $this->getTransformService()->buildPayloads($sourceBody, $dst);
        $payloadCount = count($payloads);
        $this->info("📤 Generated {$payloadCount} payload(s)");

        if ($payloadCount === 0) {
            $this->warn("⚠️  No payloads to send");
            return true;
        }

        // Validate destination URL
        $this->validateDestinationUrl($dst->url());

        // Send to destination
        $this->info("🔄 Step 3: Sending payloads to destination using '{$method}' method");

        $outcomes = match ($method) {
            'sequential' => $this->sendSequentialRequests($payloads, $dst),
            'batch'      => $this->sendBatchedRequests($payloads, $dst, $batchSize),
            'pool'       => $this->sendPooledRequests($payloads, $dst),
            default      => throw new \InvalidArgumentException("Unknown method: {$method}"),
        };

        return $this->processProbeOutcomes($outcomes);
    }

    /**
     * @return array{
     *   source_status:int,
     *   source_body:mixed,
     *   fanout:int,
     *   results: list<array{status:int, body:mixed, error:?string}>
     * }
     */
    private function exec(Source $source, Destination $dst, string $method, int $batchSize): array
    {
        try {
            $this->info("🔄 Step 1/3: Requesting to source endpoint");
            $src = $this->requestSource($source);   // not throwing
            $this->assertSourceOk($src);            // convert to domain exception if needed

            $sourceStatus = (int) $src['status'];
            $sourceBody   = $src['body'];
            $this->line("📦 Response body decoded successfully", 'v');

            $this->info("🔄 Step 2/3: Building payloads for destination");
            $payloads = $this->getTransformService()->buildPayloads($sourceBody, $dst);
            $fanout   = count($payloads);
            $this->info("📤 Generated {$fanout} payload(s) to send");

            if ($fanout === 0) {
                $this->warn("⚠️  No payloads generated, skipping destination requests");
                return [
                    'source_status' => $sourceStatus,
                    'source_body'   => $sourceBody,
                    'fanout'        => 0,
                    'results'       => [],
                ];
            }

            $this->info("🔄 Step 3/3: Sending {$fanout} request(s) to destination using '{$method}' method");

            $outcomes = match ($method) {
                'sequential' => $this->sendSequentialRequests($payloads, $dst),
                'batch'      => $this->sendBatchedRequests($payloads, $dst, $batchSize),
                'pool'       => $this->sendPooledRequests($payloads, $dst),
                default      => throw new \InvalidArgumentException("Unknown method: {$method}"),
            };

            foreach ($outcomes as $o) {
                if (!$o['ok'] && $o['status'] >= 400 && $o['status'] < 500) {
                    throw new InvalidArgumentServiceException("destination client error (HTTP {$o['status']}): " . substr((string)($o['error'] ?? ''), 0, 500));
                }
            }

            $this->info("✅ Execution completed");

            return [
                'source_status' => $sourceStatus,
                'source_body'   => $sourceBody,
                'fanout'        => $fanout,
                'results'       => $this->toResults($outcomes),
            ];
        } catch (ConnectionException $e) {
            $this->error("🔌 Connection error: {$e->getMessage()}");
            $this->line("🔍 Trace: {$e->getTraceAsString()}", 'vv');
            throw $e;
        } catch (AppServiceException $e) {
            $this->error("⚠️  Exception during execution: {$e->getMessage()}");
            $this->line("🔍 Trace: {$e->getTraceAsString()}", 'vv');
            throw $e;
        } catch (\Exception $e) {
            $this->error("⚠️  Exception during execution: {$e->getMessage()}");
            $this->line("🔍 Trace: {$e->getTraceAsString()}", 'vv');
            throw $e;
        }
    }

    /**
     * @return array{
     *   source_status:int,
     *   source_body:mixed,
     *   fanout:int,
     *   results: list<array{status:int, body:mixed, error:?string}>
     * }
     */
    private function singleExec(Source $source, Destination $dst, string $method, int $batchSize): array
    {
        try {
            $this->info("🔄 Step 1/3: Requesting to source endpoint");
            $src = $this->requestSource($source);
            $this->assertSourceOk($src);

            $sourceStatus = (int) $src['status'];
            $sourceBody   = $src['body'];
            $this->line("📦 Response body decoded successfully", 'v');

            $this->info("🔄 Step 2/3: Building payloads for destination");
            $payloads = $this->getTransformService()->buildPayloads($sourceBody, $dst);
            $fanout   = count($payloads);
            $this->info("📤 Generated {$fanout} payload(s) to send");

            if ($fanout === 0) {
                $this->warn("⚠️  No payloads generated, skipping destination requests");
                return [
                    'source_status' => $sourceStatus,
                    'source_body'   => $sourceBody,
                    'fanout'        => 0,
                    'results'       => [],
                ];
            }

            if ($fanout > 1) {
                $payloads = [$payloads[0]];
            }

            $this->info("🔄 Step 3/3: Sending {$fanout} request(s) to destination using '{$method}' method");

            $outcomes = match ($method) {
                'sequential' => $this->sendSequentialRequests($payloads, $dst),
                'batch'      => $this->sendBatchedRequests($payloads, $dst, $batchSize),
                'pool'       => $this->sendPooledRequests($payloads, $dst),
                default      => throw new \InvalidArgumentException("Unknown method: {$method}"),
            };

            $this->info("✅ Execution completed");

            return [
                'source_status' => $sourceStatus,
                'source_body'   => $sourceBody,
                'fanout'        => $fanout,
                'results'       => $this->toResults($outcomes),
            ];
        } catch (ConnectionException $e) {
            $this->error("🔌 Connection error: {$e->getMessage()}");
            $this->line("🔍 Trace: {$e->getTraceAsString()}", 'vv');
            throw $e;
        } catch (AppServiceException $e) {
            $this->error("⚠️  Exception during execution: {$e->getMessage()}");
            $this->line("🔍 Trace: {$e->getTraceAsString()}", 'vv');
            throw $e;
        } catch (\Exception $e) {
            $this->error("⚠️  Exception during execution: {$e->getMessage()}");
            $this->line("🔍 Trace: {$e->getTraceAsString()}", 'vv');
            throw $e;
        }
    }

    // ------------------------------------------------------------------------------------
    // Outcomes -> results & probe helpers
    // ------------------------------------------------------------------------------------

    private function toResults(array $outcomes): array
    {
        return array_map(function (array $o) {
            return [
                'status' => (int) ($o['status'] ?? 0),
                'body'   => $o['body'] ?? null,
                'error'  => ($o['ok'] ?? false) ? null : ($o['error'] ?? 'request-failed'),
            ];
        }, $outcomes);
    }

    private function processProbeOutcomes(array $outcomes): bool
    {
        foreach ($outcomes as $i => $o) {
            if (!is_array($o)) {
                $o = $this->normalizeHttpOutcome($o);
            }
            if (!empty($o['ok'])) {
                $this->info("✅ [{$i}] Probe succeeded with status: {$o['status']}");
                return true;
            }

            $this->error("❌ [{$i}] Probe failed with status: {$o['status']}");
            $this->line("   Response: " . substr((string)($o['error'] ?? ''), 0, 200), 'v');
            return false;
        }

        return true;
    }

    // ------------------------------------------------------------------------------------
    // Low-level HTTP utilities
    // ------------------------------------------------------------------------------------

    /**
     * Apply content type to pending request
     */
    private function applyContentType(PendingRequest|Pool $request, ?string $contentType): PendingRequest|Pool
    {
        return match ($contentType) {
            'application/json' => $request->asJson(),
            'application/x-www-form-urlencoded' => $request->asForm(),
            default => $request,
        };
    }

    /**
     * Execute HTTP request based on method
     * (jangan panggil ->throw() di sini)
     * @throws ConnectionException
     */
    private function executeHttpMethod(
        PendingRequest|Pool $request,
        string $method,
        string $url,
        mixed $body
    ): Response {
        $bodyData = $body ?? [];

        return match ($method) {
            RequestAlias::METHOD_GET    => $request->get($url),
            RequestAlias::METHOD_POST   => $request->post($url, $bodyData),
            RequestAlias::METHOD_PUT    => $request->put($url, $bodyData),
            RequestAlias::METHOD_PATCH  => $request->patch($url, $bodyData),
            RequestAlias::METHOD_DELETE => $request->delete($url, $bodyData),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Decode response body intelligently
     */
    private function decode(Response $r): mixed
    {
        $ct = $r->header('Content-Type') ?? '';
        $body = $r->body();

        if (str_contains($ct, 'json')) {
            return $r->json();
        }

        $trim = ltrim($body);
        if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $body;
    }

    /**
     * Validate destination URL format
     */
    private function validateDestinationUrl(string $url): void
    {
        $parsed = parse_url(trim($url));

        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host']) || empty($parsed['path'])) {
            throw new \InvalidArgumentException("Destination URL is malformed: {$url}");
        }

        $this->line("🌐 Destination: {$parsed['host']}{$parsed['path']}", 'v');
    }

    /**
     * Prepare body data - convert JSON string to array if needed
     */
    private function prepareBodyData(mixed $body): mixed
    {
        if ($body === null || $body === '') return [];
        if (is_array($body)) return $body;

        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;  // JSON string → array
            }
            return $body;  // Not JSON, return as-is
        }

        return $body;
    }
}
