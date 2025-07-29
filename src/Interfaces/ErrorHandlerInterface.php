<?php

declare(strict_types=1);

namespace App\Interfaces;

use Throwable;

/**
 * Interface ErrorHandlerInterface
 * 
 * Defines the contract for error handling in the application.
 * PHP 8.4 compatible with comprehensive error management features.
 */
interface ErrorHandlerInterface
{
    /**
     * Handle an exception.
     * 
     * @param Throwable $exception The exception to handle
     * @return ResponseInterface The response to send back
     */
    public function handleException(Throwable $exception): ResponseInterface;

    /**
     * Handle a PHP error.
     * 
     * @param int $severity The error severity level
     * @param string $message The error message
     * @param string $filename The file where the error occurred
     * @param int $lineno The line number where the error occurred
     * @return bool True to prevent the default error handler from running
     */
    public function handleError(int $severity, string $message, string $filename, int $lineno): bool;

    /**
     * Handle fatal errors and shutdown.
     */
    public function handleShutdown(): void;

    /**
     * Register the error handler.
     */
    public function register(): void;

    /**
     * Unregister the error handler.
     */
    public function unregister(): void;

    /**
     * Set whether to show detailed error information.
     * 
     * @param bool $debug True to show debug information
     */
    public function setDebug(bool $debug): void;

    /**
     * Check if debug mode is enabled.
     * 
     * @return bool True if debug mode is enabled
     */
    public function isDebug(): bool;

    /**
     * Log an exception.
     * 
     * @param Throwable $exception The exception to log
     * @param array $context Additional context information
     */
    public function logException(Throwable $exception, array $context = []): void;

    /**
     * Log an error.
     * 
     * @param string $message The error message
     * @param array $context Additional context information
     */
    public function logError(string $message, array $context = []): void;

    /**
     * Create an error response for an exception.
     * 
     * @param Throwable $exception The exception
     * @param int $statusCode The HTTP status code to use
     * @return ResponseInterface The error response
     */
    public function createErrorResponse(Throwable $exception, int $statusCode = 500): ResponseInterface;

    /**
     * Get the appropriate HTTP status code for an exception.
     * 
     * @param Throwable $exception The exception
     * @return int The HTTP status code
     */
    public function getStatusCodeForException(Throwable $exception): int;

    /**
     * Determine if an exception should be reported.
     * 
     * @param Throwable $exception The exception
     * @return bool True if the exception should be reported
     */
    public function shouldReport(Throwable $exception): bool;

    /**
     * Add an exception type that should not be reported.
     * 
     * @param string $exceptionClass The exception class name
     */
    public function dontReport(string $exceptionClass): void;

    /**
     * Set custom error views/templates.
     * 
     * @param array<int, string> $views Map of status codes to view templates
     */
    public function setErrorViews(array $views): void;

    /**
     * Convert an exception to an array for JSON responses.
     * 
     * @param Throwable $exception The exception
     * @return array<string, mixed> The exception data
     */
    public function exceptionToArray(Throwable $exception): array;

    /**
     * Render an exception as HTML.
     * 
     * @param Throwable $exception The exception
     * @param int $statusCode The HTTP status code
     * @return string The rendered HTML
     */
    public function renderExceptionAsHtml(Throwable $exception, int $statusCode): string;

    /**
     * Render an exception as JSON.
     * 
     * @param Throwable $exception The exception
     * @param int $statusCode The HTTP status code
     * @return string The JSON response
     */
    public function renderExceptionAsJson(Throwable $exception, int $statusCode): string;
}