<?php

declare(strict_types=1);

namespace App\Core;

use App\Interfaces\RequestInterface;

/**
 * HTTP Request Implementation
 * 
 * Concrete implementation of RequestInterface for handling HTTP requests.
 * PHP 8.4 compatible with strict typing and comprehensive request handling.
 */
class Request implements RequestInterface
{
    private string $method;
    private string $uri;
    private string $path;
    private array $queryParams;
    private array $body;
    private array $headers;
    private array $files;
    private array $cookies;
    private array $routeParams = [];
    private ?string $locale = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?? '/';
        $this->queryParams = $_GET;
        $this->body = $this->parseBody();
        $this->headers = $this->parseHeaders();
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getCookie(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }

    public function getClientIp(): string
    {
        // Check for IP in various headers (proxy-aware)
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated list (X-Forwarded-For)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function isSecure(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
            (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );
    }

    public function isAjax(): bool
    {
        return strtolower($this->getHeader('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Parse request body based on content type.
     */
    private function parseBody(): array
    {
        $contentType = $this->getHeader('Content-Type', '');
        
        // For POST requests with form data
        if ($this->method === 'POST' && str_contains($contentType, 'application/x-www-form-urlencoded')) {
            return $_POST;
        }
        
        // For POST requests with multipart data
        if ($this->method === 'POST' && str_contains($contentType, 'multipart/form-data')) {
            return $_POST;
        }
        
        // For JSON requests
        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            if ($input) {
                $decoded = json_decode($input, true);
                return is_array($decoded) ? $decoded : [];
            }
        }
        
        // For other methods (PUT, PATCH, DELETE), parse raw input
        if (in_array($this->method, ['PUT', 'PATCH', 'DELETE'], true)) {
            $input = file_get_contents('php://input');
            if ($input) {
                parse_str($input, $data);
                return $data;
            }
        }
        
        return $_POST;
    }

    /**
     * Parse HTTP headers from $_SERVER.
     */
    private function parseHeaders(): array
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // Convert HTTP_CONTENT_TYPE to Content-Type
                $name = str_replace('_', '-', substr($key, 5));
                $name = strtolower($name);
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                // Handle special headers
                $name = str_replace('_', '-', strtolower($key));
                $headers[$name] = $value;
            }
        }
        
        return $headers;
    }
}