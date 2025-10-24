<?php

namespace App\Http\Resources\Configurations;

use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

final class Destination implements JsonSerializable
{
    /**
     * @param array<string,string> $headers
     * @param array<string,string> $extract  // field -> JSONPath
     */
    public function __construct(
        private string $url,
        private readonly string $method = RequestAlias::METHOD_POST,
        private array $headers = ['Content-Type' => 'application/json'],
        private array $extract = [],
        private ?string $foreach = null,      // JSONPath or null
        private string $body_template = '',    // Mustache template (string)
        private int $timeout = 5000, // In Milliseconds
        private int $retryCount = 2, // retry when request failed
        private int $rangePerRequest = 2 // In Seconds
    ) {
        $this->url = self::assertUrl($url);
        $this->headers = self::assertHeaders($headers);
        $this->extract = self::assertExtract($extract);
        $this->foreach = self::assertForeach($foreach);
        $this->body_template = self::assertTemplate($body_template);
    }

    public function url(): string { return $this->url; }
    public function method(): string { return $this->method; }
    public function headers(): array { return $this->headers; }
    public function extract(): array { return $this->extract; }
    public function foreach(): ?string { return $this->foreach; }
    public function bodyTemplate(): string { return $this->body_template; }

    public function timeout(): int { return $this->timeout; }
    public function retryCount(): int { return $this->retryCount; }
    public function rangePerRequest(): int { return $this->rangePerRequest; }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'headers' => $this->headers,
            'extract' => $this->extract,
            'foreach' => $this->foreach,
            'body_template' => $this->body_template,
            'timeout' => $this->timeout,
            'retryCount' => $this->retryCount,
            'rangePerRequest' => $this->rangePerRequest,
        ];
    }

    public function jsonSerialize(): array { return $this->toArray(); }

    public function toJson(bool $pretty = false): string
    {
        $json = json_encode(
            $this,
            $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : JSON_UNESCAPED_SLASHES
        );
        if ($json === false) {
            throw new InvalidArgumentException('Failed to encode Destination to JSON.');
        }
        return $json;
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        if (!isset($data['url']) || !is_string($data['url'])) {
            throw new InvalidArgumentException("Destination.url required");
        }

        $method = RequestAlias::METHOD_POST; // default
        if (isset($data['method']) && is_string($data['method']) && $data['method'] !== '') {
            $method = $data['method'];
        }

        $headers = ['Content-Type' => 'application/json']; // default
        if (isset($data['headers']) && is_array($data['headers'])) {
            $headers = $data['headers'];
        }

        $extract = [];
        if (isset($data['extract']) && is_array($data['extract'])) {
            $extract = $data['extract'];
        }

        $foreach = null;
        if (isset($data['foreach']) && is_string($data['foreach'])) {
            $foreach = $data['foreach'];
        }

        $tpl = '';
        if (isset($data['body_template']) && is_string($data['body_template'])) {
            $tpl = $data['body_template'];
        }

        // default timeout 5 seconds (5000ms)
        $timeout = 5000;
        if (isset($data['timeout']) && is_int($data['timeout'])) {
            $timeout = $data['timeout'];
        }

        // default retry count 2
        $retryCount = 2;
        if (isset($data['retryCount']) && is_int($data['retryCount'])) {
            $retryCount = $data['retryCount'];
        }

        // default range per request 2 seconds
        $rangePerRequest = 2;
        if (isset($data['rangePerRequest']) && is_int($data['rangePerRequest'])) {
            $rangePerRequest = $data['rangePerRequest'];
        }

        return new self(
            url: $data['url'],
            method: $method,
            headers: $headers,
            extract: $extract,
            foreach: $foreach,
            body_template: $tpl,
            timeout: $timeout,
            retryCount: $retryCount,
            rangePerRequest: $rangePerRequest
        );
    }

    public static function fromJson(string $json): self
    {
        $arr = json_decode($json, true);
        if (!is_array($arr)) {
            throw new InvalidArgumentException("Invalid JSON for Destination");
        }
        return self::fromArray($arr);
    }

    private static function assertBodyTemplate(string $bodyTemplate): string
    {
        if (json_validate($bodyTemplate)) {
            return $bodyTemplate;
        }
        throw new InvalidArgumentException("Invalid Destination bodyTemplate");
    }

    private static function assertUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Invalid URL: {$url}");
        }
        return $url;
    }

    /** @param array<string,string> $headers */
    private static function assertHeaders(array $headers): array
    {
        foreach ($headers as $k => $v) {
            if (!is_string($k) || !is_string($v)) {
                throw new InvalidArgumentException("Headers must be string map");
            }
        }
        return $headers;
    }

    /** @param array<string,string> $extract */
    private static function assertExtract(array $extract): array
    {
        foreach ($extract as $k => $v) {
            if (!is_string($k) || !is_string($v) || $k === '') {
                throw new InvalidArgumentException("extract must be map<string,string>");
            }
        }
        return $extract;
    }

    private static function assertForeach(?string $foreach): ?string
    {
        $foreach = $foreach !== null ? trim($foreach) : null;
        return $foreach === '' ? null : $foreach;
    }

    private static function assertTemplate(string $tpl): string
    {
        $bodyTemplate = self::assertBodyTemplate($tpl);
        return trim($bodyTemplate);
    }
}
