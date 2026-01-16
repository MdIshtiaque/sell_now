<?php

declare(strict_types=1);

namespace SellNow\Http;

/**
 * HTTP Response Helper
 * Provides clean methods for sending responses
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        echo $this->body;
    }

    /**
     * Send JSON response
     */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }

    /**
     * Send HTML response
     */
    public static function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
        exit;
    }

    /**
     * Send 404 Not Found
     */
    public static function notFound(string $message = '404 Not Found'): void
    {
        http_response_code(404);
        echo $message;
        exit;
    }

    /**
     * Send 403 Forbidden
     */
    public static function forbidden(string $message = '403 Forbidden'): void
    {
        http_response_code(403);
        echo $message;
        exit;
    }

    /**
     * Send 500 Server Error
     */
    public static function serverError(string $message = '500 Internal Server Error'): void
    {
        http_response_code(500);
        echo $message;
        exit;
    }
}
