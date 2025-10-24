<?php

namespace App\Http\Resources\Configurations;

use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

final class Source implements JsonSerializable
{
    /** @var non-empty-string */
    private string $url;

    private string $method;

    /** @var array<string,string> */
    private array $headers;

    /** @var mixed|null */
    private mixed $body;

    /** @var int */
    private int $timeout;

    /** @var int */
    private int $retryCount;

    /**
     * @param non-empty-string $url
     * @param string $method
     * @param array<string,string> $headers
     * @param mixed|array $body
     */
    public function __construct(
        string $url,
        string $method = RequestAlias::METHOD_GET,
        array $headers = ['Content-Type' => 'application/json'],
        mixed $body = [],
        int $timeout = 5000, // in milliseconds
        int $retryCount = 2,
    ) {
        $this->url = self::assertValidUrl($url);
        $this->method = $method;
        $this->headers = self::normalizeHeaders($headers);
        $this->body = $body;
        $this->timeout = $timeout;
        $this->retryCount = $retryCount;
    }

    /** @return non-empty-string */
    public function url(): string { return $this->url; }

    public function method(): string { return $this->method; }

    /** @return array<string,string> */
    public function headers(): array { return $this->headers; }

    /** @return mixed|null */
    public function body(): mixed { return $this->body; }

    /** @return int */
    public function timeout(): int { return $this->timeout; }

    /** @return int */
    public function retryCount(): int { return $this->retryCount; }

    // Implement JsonSerializable

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'method' => $this->method,
            'headers' => $this->headers,
            'body' => $this->body,
            'timeout' => $this->timeout,
            'retryCount' => $this->retryCount,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(bool $pretty = false): string
    {
        $json = json_encode(
            $this,
            $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : JSON_UNESCAPED_SLASHES
        );
        if ($json === false) {
            throw new InvalidArgumentException('Failed to encode Source to JSON.');
        }
        return $json;
    }

    /**
     * @param non-empty-string $json
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON for Source.');
        }
        return self::fromArray($data);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        // Validate URL
        if (!isset($data['url']) || !is_string($data['url']) || trim($data['url']) === '') {
            throw new InvalidArgumentException("Field 'url' is required and must be a non-empty string.");
        }

        $method = RequestAlias::METHOD_GET; // default
        if (isset($data['method']) && is_string($data['method']) && $data['method'] !== '') {
            $method = $data['method'];
        }

        $headers = [];
        if (isset($data['headers']) && is_array($data['headers'])) {
            $headers = $data['headers'];
        }

        $timeout = 5000; // default 5 seconds
        if (isset($data['timeout']) && is_int($data['timeout'])) {
            $timeout = $data['timeout'];
        }

        $retryCount = 2; // default
        if (isset($data['retryCount']) && is_int($data['retryCount'])) {
            $retryCount = $data['retryCount'];
        }

        $body = [];
        if (isset($data['body'])) {
            $body = $data['body']; // Accept any type: array, string, null, etc.
        }

        return new self(
            url: $data['url'],
            method: $method,
            headers: $headers,
            body: $body,
            timeout: $timeout,
            retryCount: $retryCount
        );
    }

    /** @return non-empty-string */
    private static function assertValidUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Invalid URL: '{$url}'");
        }
        return $url;
    }

    /** @param array<string,string> $headers @return array<string,string> */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('Header name must be string.');
            }
            $name = self::assertHeaderName($name);
            if (!is_string($value)) {
                throw new InvalidArgumentException("Header '{$name}' value must be string.");
            }
            $normalized[$name] = trim($value);
        }
        return $normalized;
    }

    private static function assertHeaderName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || preg_match('/[^A-Za-z0-9\-]/', $name)) {
            throw new InvalidArgumentException("Invalid header name: '{$name}'");
        }
        return $name;
    }
}
