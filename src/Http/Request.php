<?php

declare(strict_types=1);

namespace SellNow\Http;

/**
 * HTTP Request Wrapper
 * Encapsulates request data and provides clean access to input
 */
class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $query;
    private array $post;
    private array $files;
    private array $server;
    private array $cookies;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?: '/';
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }

    /**
     * Get query parameter (?key=value)
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get POST data
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get input from POST or GET
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Get all input data
     */
    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    /**
     * Get only specified keys
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (isset($this->post[$key]) || isset($this->query[$key])) {
                $result[$key] = $this->input($key);
            }
        }
        return $result;
    }

    /**
     * Check if input exists
     */
    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]);
    }

    /**
     * Get uploaded file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Check if file was uploaded
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get server variable
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get cookie
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get client IP address
     */
    public function ip(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['REMOTE_ADDR'] 
            ?? '0.0.0.0';
    }

    /**
     * Check if AJAX request
     */
    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Expects JSON response
     */
    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json');
    }
}
