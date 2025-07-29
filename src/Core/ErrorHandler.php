<?php

declare(strict_types=1);

namespace App\Core;

use App\Interfaces\ErrorHandlerInterface;
use App\Interfaces\ResponseInterface;
use App\Core\Response;
use Exception;
use ErrorException;
use Throwable;

/**
 * Class ErrorHandler
 * 
 * Comprehensive error handler implementing ErrorHandlerInterface.
 * PHP 8.4 compatible with middleware support and structured logging.
 */
class ErrorHandler implements ErrorHandlerInterface
{
    private bool $debug = false;
    private array $dontReport = [];
    private array $errorViews = [];
    private string $logFile;
    private bool $registered = false;

    public function __construct(bool $debug = false, ?string $logFile = null)
    {
        $this->debug = $debug;
        $this->logFile = $logFile ?? sys_get_temp_dir() . '/app_errors.log';
        
        // Default error views
        $this->errorViews = [
            400 => 'errors/400',
            401 => 'errors/401',
            403 => 'errors/403',
            404 => 'errors/404',
            422 => 'errors/422',
            429 => 'errors/429',
            500 => 'errors/500',
            503 => 'errors/503',
        ];
        
        // Default exceptions that shouldn't be reported
        $this->dontReport = [
            'App\Exceptions\ValidationException',
            'App\Exceptions\AuthorizationException',
        ];
    }

    public function handleException(Throwable $exception): ResponseInterface
    {
        $statusCode = $this->getStatusCodeForException($exception);
        
        // Log the exception if it should be reported
        if ($this->shouldReport($exception)) {
            $this->logException($exception, [
                'status_code' => $statusCode,
                'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
        }
        
        return $this->createErrorResponse($exception, $statusCode);
    }

    public function handleError(int $severity, string $message, string $filename, int $lineno): bool
    {
        // Don't handle suppressed errors (@operator)
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        // Convert PHP error to exception
        throw new ErrorException($message, 0, $severity, $filename, $lineno);
    }

    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            
            $this->logException($exception, ['fatal' => true]);
            
            // If not in debug mode, show a generic error page
            if (!$this->debug) {
                http_response_code(500);
                echo $this->renderExceptionAsHtml($exception, 500);
            }
        }
    }

    public function register(): void
    {
        if (!$this->registered) {
            set_exception_handler([$this, 'handleException']);
            set_error_handler([$this, 'handleError']);
            register_shutdown_function([$this, 'handleShutdown']);
            $this->registered = true;
        }
    }

    public function unregister(): void
    {
        if ($this->registered) {
            restore_exception_handler();
            restore_error_handler();
            $this->registered = false;
        }
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function logException(Throwable $exception, array $context = []): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ];
        
        $this->writeLog($logData);
    }

    public function logError(string $message, array $context = []): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'error',
            'message' => $message,
            'context' => $context,
        ];
        
        $this->writeLog($logData);
    }

    public function createErrorResponse(Throwable $exception, int $statusCode = 500): ResponseInterface
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
                 && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $acceptsJson = isset($_SERVER['HTTP_ACCEPT']) 
                      && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
        
        if ($isAjax || $acceptsJson) {
            $content = $this->renderExceptionAsJson($exception, $statusCode);
            $headers = ['Content-Type' => 'application/json'];
        } else {
            $content = $this->renderExceptionAsHtml($exception, $statusCode);
            $headers = ['Content-Type' => 'text/html'];
        }
        
        return new Response($content, $statusCode, $headers);
    }

    public function getStatusCodeForException(Throwable $exception): int
    {
        // Check if exception has a getStatusCode method (custom exceptions)
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }
        
        // Map common exception types to status codes
        return match (get_class($exception)) {
            'App\Exceptions\ValidationException' => 422,
            'App\Exceptions\AuthenticationException' => 401,
            'App\Exceptions\AuthorizationException' => 403,
            'App\Exceptions\NotFoundException' => 404,
            'App\Exceptions\ThrottleException' => 429,
            'App\Exceptions\MaintenanceException' => 503,
            default => 500,
        };
    }

    public function shouldReport(Throwable $exception): bool
    {
        $exceptionClass = get_class($exception);
        
        // Don't report if it's in the don't report list
        foreach ($this->dontReport as $dontReportClass) {
            if ($exception instanceof $dontReportClass || $exceptionClass === $dontReportClass) {
                return false;
            }
        }
        
        return true;
    }

    public function dontReport(string $exceptionClass): void
    {
        if (!in_array($exceptionClass, $this->dontReport, true)) {
            $this->dontReport[] = $exceptionClass;
        }
    }

    public function setErrorViews(array $views): void
    {
        $this->errorViews = array_merge($this->errorViews, $views);
    }

    public function exceptionToArray(Throwable $exception): array
    {
        $data = [
            'error' => true,
            'message' => $exception->getMessage(),
            'status_code' => $this->getStatusCodeForException($exception),
        ];
        
        if ($this->debug) {
            $data['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }
        
        return $data;
    }

    public function renderExceptionAsHtml(Throwable $exception, int $statusCode): string
    {
        if ($this->debug) {
            return $this->renderDebugHtml($exception, $statusCode);
        }
        
        return $this->renderProductionHtml($exception, $statusCode);
    }

    public function renderExceptionAsJson(Throwable $exception, int $statusCode): string
    {
        return json_encode($this->exceptionToArray($exception), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Render debug HTML for development.
     */
    private function renderDebugHtml(Throwable $exception, int $statusCode): string
    {
        $trace = $exception->getTraceAsString();
        $message = htmlspecialchars($exception->getMessage());
        $file = htmlspecialchars($exception->getFile());
        $line = $exception->getLine();
        $class = get_class($exception);
        $timestamp = date('Y-m-d H:i:s');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Error {$statusCode}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .error-container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .error-title { color: #d32f2f; margin-bottom: 20px; border-bottom: 2px solid #d32f2f; padding-bottom: 10px; }
        .error-details { background: #fafafa; padding: 15px; border-left: 4px solid #d32f2f; margin: 20px 0; }
        .trace { background: #f8f8f8; padding: 15px; border: 1px solid #ddd; white-space: pre-wrap; font-family: monospace; font-size: 12px; }
        .meta { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-title">Error {$statusCode}: {$class}</h1>
        <div class="error-details">
            <strong>Message:</strong> {$message}<br>
            <strong>File:</strong> {$file}<br>
            <strong>Line:</strong> {$line}
        </div>
        <h3>Stack Trace:</h3>
        <div class="trace">{$trace}</div>
        <div class="meta">
            <p>Timestamp: {$timestamp}</p>
            <p>Request: {$method} {$uri}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render production HTML for users.
     */
    private function renderProductionHtml(Throwable $exception, int $statusCode): string
    {
        $title = $this->getStatusText($statusCode);
        $message = $this->getGenericMessage($statusCode);
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; background: #f5f5f5; }
        .error-container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-code { font-size: 72px; font-weight: bold; color: #d32f2f; margin-bottom: 20px; }
        .error-title { font-size: 24px; color: #333; margin-bottom: 15px; }
        .error-message { color: #666; margin-bottom: 30px; }
        .home-link { color: #1976d2; text-decoration: none; }
        .home-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">{$statusCode}</div>
        <div class="error-title">{$title}</div>
        <div class="error-message">{$message}</div>
        <a href="/" class="home-link">← Back to Home</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get HTTP status text.
     */
    private function getStatusText(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }

    /**
     * Get generic error message for status code.
     */
    private function getGenericMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'The request could not be understood by the server.',
            401 => 'You need to authenticate to access this resource.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The page you are looking for could not be found.',
            422 => 'The request data could not be processed.',
            429 => 'Too many requests. Please try again later.',
            500 => 'Something went wrong on our end. We\'re working to fix it.',
            503 => 'The service is temporarily unavailable.',
            default => 'An error occurred while processing your request.',
        };
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
     * Set log file path.
     */
    public function setLogFile(string $logFile): void
    {
        $this->logFile = $logFile;
    }

    /**
     * Get current log file path.
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * Clear error logs.
     */
    public function clearLogs(): bool
    {
        return file_put_contents($this->logFile, '') !== false;
    }

    /**
     * Get recent error logs.
     */
    public function getRecentLogs(int $lines = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $content = file_get_contents($this->logFile);
        $logLines = array_filter(explode("\n", $content));
        
        // Get last N lines
        $recentLines = array_slice($logLines, -$lines);
        
        $logs = [];
        foreach ($recentLines as $line) {
            $decoded = json_decode($line, true);
            if ($decoded !== null) {
                $logs[] = $decoded;
            }
        }
        
        return $logs;
    }
}