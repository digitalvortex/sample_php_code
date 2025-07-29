<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;

/**
 * Rate Limiting Middleware
 * 
 * Implements rate limiting to prevent abuse and ensure fair usage.
 * PHP 8.4 compatible with strict typing.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storagePrefix = 'rate_limit:';

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $clientId = $this->getClientIdentifier($request);
        $key = $this->storagePrefix . $clientId;
        
        // Get current request count for this client
        $current = $this->getCurrentCount($key);
        
        if ($current >= $this->maxRequests) {
            $response = new \App\Core\Response();
            return $response
                ->setStatusCode(429)
                ->setHeader('X-RateLimit-Limit', (string)$this->maxRequests)
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setHeader('X-RateLimit-Reset', (string)(time() + $this->windowSeconds))
                ->setHeader('Retry-After', (string)$this->windowSeconds)
                ->json(['error' => 'Too many requests']);
        }

        // Increment request count
        $this->incrementCount($key);
        
        // Continue with the request
        $response = $next($request);
        
        // Add rate limit headers to the response
        $remaining = max(0, $this->maxRequests - $current - 1);
        $response
            ->setHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->setHeader('X-RateLimit-Remaining', (string)$remaining)
            ->setHeader('X-RateLimit-Reset', (string)(time() + $this->windowSeconds));

        return $response;
    }

    public function getPriority(): int
    {
        return 300; // Lower priority - should run after auth
    }

    public function getName(): string
    {
        return 'rate_limit';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        // Apply rate limiting to all requests except static assets
        $path = $request->getPath();
        $excludedPaths = ['/css/', '/js/', '/images/', '/favicon.ico'];
        
        foreach ($excludedPaths as $excludedPath) {
            if (str_contains($path, $excludedPath)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get a unique identifier for the client.
     */
    private function getClientIdentifier(RequestInterface $request): string
    {
        // Use IP address as the primary identifier
        $ip = $request->getClientIp();
        
        // For authenticated users, also consider user ID
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            return "user:{$userId}";
        }
        
        return "ip:{$ip}";
    }

    /**
     * Get current request count from storage.
     */
    private function getCurrentCount(string $key): int
    {
        // Simple file-based storage (in production, use Redis/Memcached)
        $file = sys_get_temp_dir() . '/' . md5($key) . '.txt';
        
        if (!file_exists($file)) {
            return 0;
        }
        
        $data = file_get_contents($file);
        if (!$data) {
            return 0;
        }
        
        [$count, $timestamp] = explode('|', $data);
        
        // Reset count if window has expired
        if (time() - (int)$timestamp > $this->windowSeconds) {
            unlink($file);
            return 0;
        }
        
        return (int)$count;
    }

    /**
     * Increment request count in storage.
     */
    private function incrementCount(string $key): void
    {
        $file = sys_get_temp_dir() . '/' . md5($key) . '.txt';
        $current = $this->getCurrentCount($key);
        
        // If this is a new window, start from 1
        $count = $current === 0 ? 1 : $current + 1;
        $timestamp = time();
        
        file_put_contents($file, "{$count}|{$timestamp}");
    }
}