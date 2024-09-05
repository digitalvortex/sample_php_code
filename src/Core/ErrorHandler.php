<?php

declare(strict_types=1);

namespace App\Core;

use Exception, ErrorException, Throwable;



class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleException(Throwable $exception): void
    {
        http_response_code(500);
        echo "An error occurred: " . $exception->getMessage();
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}
