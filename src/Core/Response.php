<?php

declare(strict_types=1);

namespace App\Core;

use App\Interfaces\ResponseInterface;

/**
 * HTTP Response Implementation
 * 
 * Concrete implementation of ResponseInterface for handling HTTP responses.
 * PHP 8.4 compatible with strict typing and comprehensive response handling.
 */
class Response implements ResponseInterface
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';
    private array $cookies = [];
    private bool $sent = false;

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * @var array<int, string> HTTP status codes and their messages
     */
    private static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[strtolower($name)] = $value;
        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        $name = strtolower($name);
        if (isset($this->headers[$name])) {
            // Convert to array if not already
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = [$this->headers[$name]];
            }
            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    public function removeHeader(string $name): static
    {
        unset($this->headers[strtolower($name)]);
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function appendBody(string $content): static
    {
        $this->body .= $content;
        return $this;
    }

    public function json(mixed $data, int $options = 0): static
    {
        $json = json_encode($data, $options | JSON_THROW_ON_ERROR);
        return $this
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setBody($json);
    }

    public function html(string $html): static
    {
        return $this
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setBody($html);
    }

    public function text(string $text): static
    {
        return $this
            ->setHeader('Content-Type', 'text/plain; charset=utf-8')
            ->setBody($text);
    }

    public function redirect(string $url, int $code = 302): static
    {
        return $this
            ->setStatusCode($code)
            ->setHeader('Location', $url);
    }

    public function setCookie(
        string $name,
        string $value,
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true
    ): static {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expires' => $expires,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
        ];
        return $this;
    }

    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        // Send status line
        $statusText = self::$statusTexts[$this->statusCode] ?? 'Unknown Status';
        header("HTTP/1.1 {$this->statusCode} {$statusText}");

        // Send headers
        foreach ($this->headers as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    header("{$name}: {$v}", false);
                }
            } else {
                header("{$name}: {$value}");
            }
        }

        // Send cookies
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expires'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );
        }

        // Send body
        echo $this->body;

        $this->sent = true;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Create a JSON error response.
     */
    public function error(string $message, int $code = 400, array $details = []): static
    {
        $data = ['error' => $message];
        if (!empty($details)) {
            $data['details'] = $details;
        }
        
        return $this
            ->setStatusCode($code)
            ->json($data);
    }

    /**
     * Create a JSON success response.
     */
    public function success(mixed $data = null, string $message = 'Success'): static
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $this->json($response);
    }

    /**
     * Set security headers.
     */
    public function withSecurityHeaders(): static
    {
        return $this
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->setHeader('Content-Security-Policy', "default-src 'self'");
    }

    /**
     * Set cache headers.
     */
    public function withCacheHeaders(int $maxAge = 3600, bool $public = true): static
    {
        $cacheControl = $public ? 'public' : 'private';
        $cacheControl .= ", max-age={$maxAge}";
        
        return $this
            ->setHeader('Cache-Control', $cacheControl)
            ->setHeader('Expires', gmdate('D, d M Y H:i:s T', time() + $maxAge));
    }

    /**
     * Disable caching.
     */
    public function withoutCache(): static
    {
        return $this
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0');
    }

    /**
     * Check if response is successful (2xx status code).
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is a redirect (3xx status code).
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if response is a client error (4xx status code).
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is a server error (5xx status code).
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Set status code with method chaining (alias for setStatusCode).
     */
    public function withStatus(int $code): static
    {
        return $this->setStatusCode($code);
    }

    /**
     * Set JSON response with method chaining (alias for json).
     */
    public function withJson(mixed $data, int $options = 0): static
    {
        return $this->json($data, $options);
    }

    /**
     * Set redirect response with method chaining (alias for redirect).
     */
    public function withRedirect(string $url, int $code = 302): static
    {
        return $this->redirect($url, $code);
    }
}