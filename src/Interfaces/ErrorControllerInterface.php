<?php

declare(strict_types=1);

namespace App\Interfaces;

interface ErrorControllerInterface
{
    /**
     * Handle 404 Not Found errors.
     *
     * @return string
     */
    public function notFound(): string;

    /**
     * Handle 500 Internal Server Error.
     *
     * @return string
     */
    public function internalServerError(): string;
}
