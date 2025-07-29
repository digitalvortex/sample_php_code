<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface RequestInterface
 * 
 * Defines the contract for HTTP request objects.
 * PHP 8.4 compatible with strict typing and comprehensive request handling.
 */
interface RequestInterface
{
    /**
     * Get the HTTP method.
     * 
     * @return string HTTP method (GET, POST, PUT, DELETE, etc.)
     */
    public function getMethod(): string;

    /**
     * Get the request URI.
     * 
     * @return string Request URI
     */
    public function getUri(): string;

    /**
     * Get the request path (URI without query string).
     * 
     * @return string Request path
     */
    public function getPath(): string;

    /**
     * Get query parameters.
     * 
     * @return array<string, mixed> Query parameters
     */
    public function getQueryParams(): array;

    /**
     * Get a specific query parameter.
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed Parameter value
     */
    public function getQueryParam(string $key, mixed $default = null): mixed;

    /**
     * Get POST/PUT body data.
     * 
     * @return array<string, mixed> Request body data
     */
    public function getBody(): array;

    /**
     * Get a specific body parameter.
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed Parameter value
     */
    public function getBodyParam(string $key, mixed $default = null): mixed;

    /**
     * Get request headers.
     * 
     * @return array<string, string> Request headers
     */
    public function getHeaders(): array;

    /**
     * Get a specific header.
     * 
     * @param string $name Header name
     * @param string|null $default Default value if header doesn't exist
     * @return string|null Header value
     */
    public function getHeader(string $name, ?string $default = null): ?string;

    /**
     * Get uploaded files.
     * 
     * @return array<string, array> Uploaded files information
     */
    public function getFiles(): array;

    /**
     * Get cookies.
     * 
     * @return array<string, string> Request cookies
     */
    public function getCookies(): array;

    /**
     * Get a specific cookie.
     * 
     * @param string $name Cookie name
     * @param string|null $default Default value if cookie doesn't exist
     * @return string|null Cookie value
     */
    public function getCookie(string $name, ?string $default = null): ?string;

    /**
     * Get the client IP address.
     * 
     * @return string Client IP address
     */
    public function getClientIp(): string;

    /**
     * Get the user agent string.
     * 
     * @return string User agent
     */
    public function getUserAgent(): string;

    /**
     * Check if request is secure (HTTPS).
     * 
     * @return bool True if request is secure
     */
    public function isSecure(): bool;

    /**
     * Check if request is AJAX.
     * 
     * @return bool True if request is AJAX
     */
    public function isAjax(): bool;

    /**
     * Get route parameters (from URL path matching).
     * 
     * @return array<string, mixed> Route parameters
     */
    public function getRouteParams(): array;

    /**
     * Set route parameters.
     * 
     * @param array<string, mixed> $params Route parameters
     * @return void
     */
    public function setRouteParams(array $params): void;
}