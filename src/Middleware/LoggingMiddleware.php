<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;

/**
 * Logging Middleware
 * 
 * Logs HTTP requests and responses for monitoring and debugging.
 * PHP 8.4 compatible with structured logging.
 */
class LoggingMiddleware implements MiddlewareInterface
{
    private string $logFile;
    private bool $logHeaders;
    private bool $logBody;

    public function __construct(
        ?string $logFile = null,
        bool $logHeaders = false,
        bool $logBody = false
    ) {
        $this->logFile = $logFile ?? sys_get_temp_dir() . '/app.log';
        $this->logHeaders = $logHeaders;
        $this->logBody = $logBody;
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $startTime = microtime(true);
        $requestId = $this->generateRequestId();

        // Log request
        $this->logRequest($request, $requestId);

        // Process request
        $response = $next($request);

        // Log response
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $this->logResponse($response, $requestId, $duration);

        return $response;
    }

    public function getPriority(): int
    {
        return 900; // Very low priority - should run last to capture everything
    }

    public function getName(): string
    {
        return 'logging';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        // Don't log static assets or health checks
        $path = $request->getPath();
        $excludedPaths = ['/css/', '/js/', '/images/', '/favicon.ico', '/health', '/ping'];
        
        foreach ($excludedPaths as $excludedPath) {
            if (str_contains($path, $excludedPath)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Log the incoming request.
     */
    private function logRequest(RequestInterface $request, string $requestId): void
    {
        $logData = [
            'type' => 'request',
            'request_id' => $requestId,
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->getUserAgent(),
        ];

        if ($this->logHeaders) {
            $logData['headers'] = $request->getHeaders();
        }

        if ($this->logBody && in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            $body = $request->getBody();
            // Don't log sensitive data like passwords
            $logData['body'] = $this->sanitizeLogData($body);
        }

        $this->writeLog($logData);
    }

    /**
     * Log the outgoing response.
     */
    private function logResponse(ResponseInterface $response, string $requestId, float $duration): void
    {
        $logData = [
            'type' => 'response',
            'request_id' => $requestId,
            'timestamp' => date('Y-m-d H:i:s'),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ];

        if ($this->logHeaders) {
            $logData['headers'] = $response->getHeaders();
        }

        if ($this->logBody) {
            $body = $response->getBody();
            // Truncate long responses
            if (strlen($body) > 1000) {
                $body = substr($body, 0, 1000) . '... [truncated]';
            }
            $logData['body'] = $body;
        }

        $this->writeLog($logData);
    }

    /**
     * Write log data to file.
     */
    private function writeLog(array $data): void
    {
        $logLine = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Generate a unique request ID.
     */
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }

    /**
     * Remove sensitive data from logs.
     */
    private function sanitizeLogData(array $data): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth', 'csrf_token'];
        
        foreach ($data as $field => $value) {
            $lowerField = strtolower($field);
            foreach ($sensitiveFields as $sensitiveField) {
                if (str_contains($lowerField, $sensitiveField)) {
                    $data[$field] = '[REDACTED]';
                    break;
                }
            }
        }
        
        return $data;
    }

    /**
     * Set custom log file.
     */
    public function setLogFile(string $logFile): void
    {
        $this->logFile = $logFile;
    }

    /**
     * Enable/disable header logging.
     */
    public function setLogHeaders(bool $logHeaders): void
    {
        $this->logHeaders = $logHeaders;
    }

    /**
     * Enable/disable body logging.
     */
    public function setLogBody(bool $logBody): void
    {
        $this->logBody = $logBody;
    }
}