<?php

namespace App\Service\Implements;

use App\Exceptions\InternalServiceException;
use App\Exceptions\InvalidArgumentServiceException;
use App\Http\Resources\Configurations\Destination;
use App\Service\Contracts\TransformService;
use Flow\JSONPath\JSONPath;
use Flow\JSONPath\JSONPathException;
use Illuminate\Log\Logger;
use Mustache\Engine;


class TransformServiceImpl implements TransformService
{
    public function __construct(public Engine $engine,public Logger $logger)
    {
        //
    }

    /**
     * @param mixed $sourceBody
     * @return list<array{body:string, headers:array<string,string>}>
     */
    public function buildPayloads($sourceBody, Destination $dst): array
    {
        $this->logger->info("start to build payloads with arguments", ["response_source" => $sourceBody, "configuration_destination" => $dst->toJson()]);
        $root = $this->normalizeJson($sourceBody);
        $headers = $dst->headers();
        try {
            $this->logger->info("calling handle foreach");
            $items = $this->resolveForeach($root, $dst->foreach());
        } catch (InternalServiceException|InvalidArgumentServiceException $e) {
            $this->logger->error("error when calling handle foreach: ".$e->getMessage());
            throw $e;
        }

        $payloads = [];
        foreach ($items as $item) {
            try {
                $this->logger->info("calling handle extract body template");
                $vars = $this->runExtract($root, $item, $dst->extract());
            } catch (InternalServiceException|InvalidArgumentServiceException $e) {
                $this->logger->error("error when calling handle extract body template: ".$e->getMessage());
                throw $e;
            }
            try {
                $body = $this->engine->render($dst->bodyTemplate(), $vars);
                $payloads[] = [
                    'body'    => $body,
                    'headers' => $headers,
                ];
                $this->logger->info("successfully build payload with payload", ["payload" => [
                    'body'    => $body,
                    'headers' => $headers
                ]]);
            } catch (\Exception $exception) {
                throw new InvalidArgumentServiceException("invalid body template: ".$exception->getMessage());
            }


        }
        $this->logger->info("successfully build all payload with payloads", ["count_payloads" => count($payloads)]);
        return $payloads;
    }

    private function resolveForeach(array $root, ?string $foreach): array
    {
        try {
            $this->logger->info("start to handle foreach arguments", ["foreach" => $foreach]);
            if ($foreach === null) {
                return [$root];
            }
            $matches = (new JSONPath($root))->find($foreach)->getData();
            $this->logger->info("successfully handle foreach arguments", ["foreach" => $foreach]);
            return is_array($matches) ? $matches : [];
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ["exception" => $exception]);
            if ($exception instanceof  JSONPathException) {
                $this->logger->error("invalid json path", ["exception" => $exception]);
                throw new InvalidArgumentServiceException($exception->getMessage(), 400);
            }
            throw new InternalServiceException($exception->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $root
     * @param mixed $currentItem
     * @param array<string,string> $extract
     * @return array<string,mixed>
     */
    private function runExtract(array $root, $currentItem, array $extract): array
    {
        try {
            $this->logger->info("start to run extract json to get build body template from json_path", ["extract" => $extract]);
            $out = [];

            foreach ($extract as $field => $path) {
                $path = trim($path);
                $value = null;

                if ($path !== '') {
                    if ($path[0] === '@') {
                        $local = $this->normalizeJson($currentItem);
                        $matches = (new JSONPath($local))->find('$' . substr($path, 1))->getData();
                        $value = $this->firstOrNull($matches);
                    } else {
                        $expr = ($path[0] === '$') ? $path : '$.' . $path;
                        $matches = (new JSONPath($root))->find($expr)->getData();
                        $value = $this->firstOrNull($matches);
                    }
                }
                $this->logger->info("successfully get data from json_path with data", [$field => $value]);
                $out[$field] = $value;
            }
            $this->logger->info("end to extract body template from json_path", ["output" => $out]);
            return $out;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), ["exception" => $exception]);
            if ($exception instanceof JSONPathException) {
                $this->logger->error("invalid json path", ["exception" => $exception]);
                throw new InvalidArgumentServiceException($exception->getMessage(), 400);
            }
            throw new InternalServiceException($exception->getMessage());
        }
    }

    private function normalizeJson($val): array
    {
        if (is_array($val)) return $val;
        if (is_object($val)) return json_decode(json_encode($val), true) ?? [];
        return ['value' => $val];
    }

    private function firstOrNull($jsonPathResult)
    {
        $this->logger->info("start to get first object or return null", ["json_path" => $jsonPathResult]);
        if (is_array($jsonPathResult)) {
            if (array_is_list($jsonPathResult)) {
                $this->logger->info("get first object from array index 0", ["json_path" => $jsonPathResult]);
                return $jsonPathResult[0] ?? null;
            }
            $this->logger->info("get first object from array associative", ["json_path" => $jsonPathResult]);
            return $jsonPathResult;
        }
        return $jsonPathResult ?? null;
    }
}
