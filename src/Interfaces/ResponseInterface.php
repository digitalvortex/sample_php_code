<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface ResponseInterface
 * 
 * Defines the contract for HTTP response objects.
 * PHP 8.4 compatible with strict typing and comprehensive response handling.
 */
interface ResponseInterface
{
    /**
     * Get the response status code.
     * 
     * @return int HTTP status code
     */
    public function getStatusCode(): int;

    /**
     * Set the response status code.
     * 
     * @param int $code HTTP status code
     * @return static For method chaining
     */
    public function setStatusCode(int $code): static;

    /**
     * Get response headers.
     * 
     * @return array<string, string> Response headers
     */
    public function getHeaders(): array;

    /**
     * Get a specific header.
     * 
     * @param string $name Header name
     * @return string|null Header value
     */
    public function getHeader(string $name): ?string;

    /**
     * Set a response header.
     * 
     * @param string $name Header name
     * @param string $value Header value
     * @return static For method chaining
     */
    public function setHeader(string $name, string $value): static;

    /**
     * Add a response header (allows multiple values).
     * 
     * @param string $name Header name
     * @param string $value Header value
     * @return static For method chaining
     */
    public function addHeader(string $name, string $value): static;

    /**
     * Remove a response header.
     * 
     * @param string $name Header name
     * @return static For method chaining
     */
    public function removeHeader(string $name): static;

    /**
     * Get the response body.
     * 
     * @return string Response body
     */
    public function getBody(): string;

    /**
     * Set the response body.
     * 
     * @param string $body Response body
     * @return static For method chaining
     */
    public function setBody(string $body): static;

    /**
     * Append to the response body.
     * 
     * @param string $content Content to append
     * @return static For method chaining
     */
    public function appendBody(string $content): static;

    /**
     * Set response as JSON.
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $options JSON encoding options
     * @return static For method chaining
     */
    public function json(mixed $data, int $options = 0): static;

    /**
     * Set response as HTML.
     * 
     * @param string $html HTML content
     * @return static For method chaining
     */
    public function html(string $html): static;

    /**
     * Set response as plain text.
     * 
     * @param string $text Plain text content
     * @return static For method chaining
     */
    public function text(string $text): static;

    /**
     * Set response as redirect.
     * 
     * @param string $url Redirect URL
     * @param int $code HTTP status code (default 302)
     * @return static For method chaining
     */
    public function redirect(string $url, int $code = 302): static;

    /**
     * Set a cookie.
     * 
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $expires Expiration time (timestamp)
     * @param string $path Cookie path
     * @param string $domain Cookie domain
     * @param bool $secure Secure flag
     * @param bool $httpOnly HTTP only flag
     * @return static For method chaining
     */
    public function setCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): static;

    /**
     * Send the response to the client.
     * 
     * @return void
     */
    public function send(): void;

    /**
     * Check if response has been sent.
     * 
     * @return bool True if response has been sent
     */
    public function isSent(): bool;
}