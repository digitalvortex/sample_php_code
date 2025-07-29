<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;

/**
 * CORS (Cross-Origin Resource Sharing) Middleware
 * 
 * Handles CORS headers for cross-origin requests.
 * PHP 8.4 compatible with strict typing.
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @var array<string> Allowed origins
     */
    private array $allowedOrigins;
    
    /**
     * @var array<string> Allowed HTTP methods
     */
    private array $allowedMethods;
    
    /**
     * @var array<string> Allowed headers
     */
    private array $allowedHeaders;
    
    private bool $allowCredentials;
    private int $maxAge;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'],
        bool $allowCredentials = true,
        int $maxAge = 3600
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = $maxAge;
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $origin = $request->getHeader('Origin');
        
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \App\Core\Response();
            return $this->addCorsHeaders($response, $origin)
                ->setStatusCode(200)
                ->setBody('');
        }

        // Process the actual request
        $response = $next($request);
        
        // Add CORS headers to the response
        return $this->addCorsHeaders($response, $origin);
    }

    public function getPriority(): int
    {
        return 50; // Very high priority - should run early
    }

    public function getName(): string
    {
        return 'cors';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        // Apply CORS middleware to all requests
        return true;
    }

    /**
     * Add CORS headers to the response.
     */
    private function addCorsHeaders(ResponseInterface $response, ?string $origin): ResponseInterface
    {
        // Check if origin is allowed
        if ($origin && $this->isOriginAllowed($origin)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        } elseif (in_array('*', $this->allowedOrigins, true)) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        }

        // Set allowed methods
        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));

        // Set allowed headers
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));

        // Set credentials
        if ($this->allowCredentials) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        // Set max age for preflight requests
        $response->setHeader('Access-Control-Max-Age', (string)$this->maxAge);

        // Expose common headers
        $response->setHeader('Access-Control-Expose-Headers', 'X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset');

        return $response;
    }

    /**
     * Check if the origin is allowed.
     */
    private function isOriginAllowed(string $origin): bool
    {
        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }
}