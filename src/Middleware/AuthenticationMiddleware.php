<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;

/**
 * Authentication Middleware
 * 
 * Ensures users are authenticated before accessing protected routes.
 * PHP 8.4 compatible with strict typing.
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var array<string> Routes that don't require authentication
     */
    private array $publicRoutes = [
        '/',
        '/about',
        '/contact',
        '/services',
        '/blog',
        '/login',
        '/register',
        '/password-reset'
    ];

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $path = $request->getPath();

        // Skip authentication for public routes
        if ($this->isPublicRoute($path)) {
            return $next($request);
        }

        // Check for authentication (session, JWT token, etc.)
        $isAuthenticated = $this->checkAuthentication($request);

        if (!$isAuthenticated) {
            // Redirect to login for web requests, return 401 for API requests
            $response = new \App\Core\Response();
            
            if ($this->isApiRequest($request)) {
                return $response
                    ->setStatusCode(401)
                    ->json(['error' => 'Authentication required']);
            }

            return $response->redirect('/login');
        }

        return $next($request);
    }

    public function getPriority(): int
    {
        return 200; // Medium priority - after CSRF but before rate limiting
    }

    public function getName(): string
    {
        return 'auth';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        // Apply to all routes except those explicitly excluded
        return !$this->isPublicRoute($request->getPath());
    }

    /**
     * Check if the current route is public (doesn't require authentication).
     */
    private function isPublicRoute(string $path): bool
    {
        foreach ($this->publicRoutes as $publicRoute) {
            if ($path === $publicRoute || str_starts_with($path, $publicRoute . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the user is authenticated.
     */
    private function checkAuthentication(RequestInterface $request): bool
    {
        // Check session-based authentication
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            return true;
        }

        // Check JWT token authentication
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            return $this->validateJwtToken($token);
        }

        // Check API key authentication
        $apiKey = $request->getHeader('X-API-Key');
        if ($apiKey) {
            return $this->validateApiKey($apiKey);
        }

        return false;
    }

    /**
     * Check if this is an API request.
     */
    private function isApiRequest(RequestInterface $request): bool
    {
        return str_starts_with($request->getPath(), '/api/') ||
               $request->getHeader('Content-Type') === 'application/json' ||
               $request->getHeader('Accept') === 'application/json';
    }

    /**
     * Validate JWT token (placeholder implementation).
     */
    private function validateJwtToken(string $token): bool
    {
        // TODO: Implement JWT validation
        return false;
    }

    /**
     * Validate API key (placeholder implementation).
     */
    private function validateApiKey(string $apiKey): bool
    {
        // TODO: Implement API key validation
        return false;
    }
}