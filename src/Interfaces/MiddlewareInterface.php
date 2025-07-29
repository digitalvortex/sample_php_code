<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface MiddlewareInterface
 * 
 * Defines the contract for HTTP middleware components.
 * PHP 8.4 compatible with strict typing and modern request/response handling.
 */
interface MiddlewareInterface
{
    /**
     * Process an incoming request.
     * 
     * @param RequestInterface $request The incoming request
     * @param callable $next The next middleware or final handler
     * @return ResponseInterface The response
     */
    public function handle(RequestInterface $request, callable $next): ResponseInterface;

    /**
     * Get the middleware priority (lower numbers execute first).
     * 
     * @return int Priority value (0-1000)
     */
    public function getPriority(): int;

    /**
     * Get the middleware name/identifier.
     * 
     * @return string Middleware name
     */
    public function getName(): string;

    /**
     * Check if middleware should be applied to the given request.
     * 
     * @param RequestInterface $request The request to check
     * @return bool True if middleware should be applied
     */
    public function shouldApply(RequestInterface $request): bool;
}