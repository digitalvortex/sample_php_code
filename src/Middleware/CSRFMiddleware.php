<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;
use App\Security\CSRFToken;

/**
 * CSRF Protection Middleware
 * 
 * Validates CSRF tokens for state-changing requests (POST, PUT, DELETE, PATCH).
 * PHP 8.4 compatible with strict typing.
 */
class CSRFMiddleware implements MiddlewareInterface
{
    private CSRFToken $csrfToken;
    
    /**
     * @var array<string> HTTP methods that require CSRF protection
     */
    private array $protectedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

    public function __construct(CSRFToken $csrfToken)
    {
        $this->csrfToken = $csrfToken;
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Only check CSRF for state-changing requests
        if (in_array($request->getMethod(), $this->protectedMethods, true)) {
            $token = $request->getBodyParam('csrf_token') ?? $request->getHeader('X-CSRF-Token');
            
            if (!$token || !$this->csrfToken->verify($token)) {
                // Create error response for invalid CSRF token
                $response = new \App\Core\Response();
                return $response
                    ->setStatusCode(403)
                    ->json(['error' => 'Invalid CSRF token']);
            }
        }

        return $next($request);
    }

    public function getPriority(): int
    {
        return 100; // High priority - should run early
    }

    public function getName(): string
    {
        return 'csrf';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        // Apply to all requests except API endpoints with API key auth
        $path = $request->getPath();
        return !str_starts_with($path, '/api/') || !$request->getHeader('X-API-Key');
    }
}