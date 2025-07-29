<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;

/**
 * Caching Middleware
 * 
 * Provides HTTP response caching with flexible cache strategies.
 * PHP 8.4 compatible with memory and file-based caching.
 */
class CachingMiddleware implements MiddlewareInterface
{
    private string $cacheDir;
    private int $defaultTtl;
    private array $cacheableStatuses;
    private array $cacheableMethods;
    private array $memoryCache = [];
    private int $maxMemoryItems;

    public function __construct(
        ?string $cacheDir = null,
        int $defaultTtl = 3600,
        array $cacheableStatuses = [200, 301, 302, 404],
        array $cacheableMethods = ['GET', 'HEAD'],
        int $maxMemoryItems = 100
    ) {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/http_cache';
        $this->defaultTtl = $defaultTtl;
        $this->cacheableStatuses = $cacheableStatuses;
        $this->cacheableMethods = $cacheableMethods;
        $this->maxMemoryItems = $maxMemoryItems;

        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Only cache GET and HEAD requests by default
        if (!in_array($request->getMethod(), $this->cacheableMethods, true)) {
            return $next($request);
        }

        $cacheKey = $this->generateCacheKey($request);
        
        // Try to get from cache
        $cachedResponse = $this->getFromCache($cacheKey);
        if ($cachedResponse !== null) {
            return $this->createCachedResponse($cachedResponse);
        }

        // Process request
        $response = $next($request);

        // Cache the response if applicable
        if ($this->shouldCacheResponse($response)) {
            $this->storeInCache($cacheKey, $response);
        }

        return $response;
    }

    public function getPriority(): int
    {
        return 800; // High priority - should run early to serve cached responses
    }

    public function getName(): string
    {
        return 'caching';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        // Don't cache requests with certain headers
        $noCacheHeaders = ['authorization', 'cookie'];
        
        foreach ($noCacheHeaders as $header) {
            if ($request->hasHeader($header)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a cache key for the request.
     */
    private function generateCacheKey(RequestInterface $request): string
    {
        $components = [
            $request->getMethod(),
            $request->getUri(),
            $request->getQueryString(),
            // Include relevant headers that affect response
            $request->getHeader('Accept'),
            $request->getHeader('Accept-Language'),
            $request->getHeader('Accept-Encoding'),
        ];

        return md5(implode('|', array_filter($components)));
    }

    /**
     * Get response from cache.
     */
    private function getFromCache(string $key): ?array
    {
        // Try memory cache first
        if (isset($this->memoryCache[$key])) {
            $cached = $this->memoryCache[$key];
            if ($cached['expires'] > time()) {
                return $cached;
            }
            unset($this->memoryCache[$key]);
        }

        // Try file cache
        $filePath = $this->getCacheFilePath($key);
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                $cached = json_decode($content, true);
                if ($cached && $cached['expires'] > time()) {
                    // Store in memory cache for faster access
                    $this->addToMemoryCache($key, $cached);
                    return $cached;
                }
            }
            // Remove expired file
            unlink($filePath);
        }

        return null;
    }

    /**
     * Store response in cache.
     */
    private function storeInCache(string $key, ResponseInterface $response): void
    {
        $ttl = $this->getCacheTtl($response);
        if ($ttl <= 0) {
            return;
        }

        $cached = [
            'status_code' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => $response->getBody(),
            'created' => time(),
            'expires' => time() + $ttl,
            'ttl' => $ttl
        ];

        // Store in memory cache
        $this->addToMemoryCache($key, $cached);

        // Store in file cache
        $filePath = $this->getCacheFilePath($key);
        file_put_contents($filePath, json_encode($cached), LOCK_EX);
    }

    /**
     * Add item to memory cache with LRU eviction.
     */
    private function addToMemoryCache(string $key, array $cached): void
    {
        // Remove if already exists (for LRU)
        if (isset($this->memoryCache[$key])) {
            unset($this->memoryCache[$key]);
        }

        // Add to end
        $this->memoryCache[$key] = $cached;

        // Evict oldest if over limit
        if (count($this->memoryCache) > $this->maxMemoryItems) {
            $this->memoryCache = array_slice($this->memoryCache, -$this->maxMemoryItems, null, true);
        }
    }

    /**
     * Check if response should be cached.
     */
    private function shouldCacheResponse(ResponseInterface $response): bool
    {
        // Don't cache if status code is not cacheable
        if (!in_array($response->getStatusCode(), $this->cacheableStatuses, true)) {
            return false;
        }

        // Don't cache if Cache-Control says no-cache
        $cacheControl = $response->getHeader('Cache-Control');
        if ($cacheControl && (str_contains($cacheControl, 'no-cache') || str_contains($cacheControl, 'no-store'))) {
            return false;
        }

        // Don't cache if response has Set-Cookie
        if ($response->hasHeader('Set-Cookie')) {
            return false;
        }

        return true;
    }

    /**
     * Determine cache TTL for response.
     */
    private function getCacheTtl(ResponseInterface $response): int
    {
        // Check Cache-Control max-age
        $cacheControl = $response->getHeader('Cache-Control');
        if ($cacheControl && preg_match('/max-age=(\d+)/', $cacheControl, $matches)) {
            return (int)$matches[1];
        }

        // Check Expires header
        $expires = $response->getHeader('Expires');
        if ($expires) {
            $expiresTime = strtotime($expires);
            if ($expiresTime !== false) {
                return max(0, $expiresTime - time());
            }
        }

        return $this->defaultTtl;
    }

    /**
     * Create a response from cached data.
     */
    private function createCachedResponse(array $cached): ResponseInterface
    {
        // This is a simplified approach - in a real implementation,
        // you'd need to create a proper Response object
        return new class($cached) implements ResponseInterface {
            private array $cached;

            public function __construct(array $cached)
            {
                $this->cached = $cached;
            }

            public function getStatusCode(): int
            {
                return $this->cached['status_code'];
            }

            public function setStatusCode(int $statusCode): ResponseInterface
            {
                $this->cached['status_code'] = $statusCode;
                return $this;
            }

            public function getHeaders(): array
            {
                $headers = $this->cached['headers'];
                // Add cache headers
                $headers['X-Cache'] = 'HIT';
                $headers['X-Cache-Created'] = date('Y-m-d H:i:s', $this->cached['created']);
                return $headers;
            }

            public function getHeader(string $name): ?string
            {
                return $this->cached['headers'][$name] ?? null;
            }

            public function hasHeader(string $name): bool
            {
                return isset($this->cached['headers'][$name]);
            }

            public function setHeader(string $name, string $value): ResponseInterface
            {
                $this->cached['headers'][$name] = $value;
                return $this;
            }

            public function removeHeader(string $name): ResponseInterface
            {
                unset($this->cached['headers'][$name]);
                return $this;
            }

            public function getBody(): string
            {
                return $this->cached['body'];
            }

            public function setBody(string $body): ResponseInterface
            {
                $this->cached['body'] = $body;
                return $this;
            }

            public function send(): void
            {
                http_response_code($this->getStatusCode());
                foreach ($this->getHeaders() as $name => $value) {
                    header("{$name}: {$value}");
                }
                echo $this->getBody();
            }

            public function isSuccessful(): bool
            {
                return $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
            }

            public function isRedirect(): bool
            {
                return in_array($this->getStatusCode(), [301, 302, 303, 307, 308], true);
            }

            public function isClientError(): bool
            {
                return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
            }

            public function isServerError(): bool
            {
                return $this->getStatusCode() >= 500;
            }
        };
    }

    /**
     * Get file path for cache key.
     */
    private function getCacheFilePath(string $key): string
    {
        return $this->cacheDir . '/' . $key . '.cache';
    }

    /**
     * Clear all cached items.
     */
    public function clearCache(): void
    {
        $this->memoryCache = [];
        
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Clear expired cache items.
     */
    public function clearExpired(): int
    {
        $cleared = 0;
        
        // Clear from memory
        foreach ($this->memoryCache as $key => $cached) {
            if ($cached['expires'] <= time()) {
                unset($this->memoryCache[$key]);
                $cleared++;
            }
        }
        
        // Clear from files
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $cached = json_decode($content, true);
                if ($cached && $cached['expires'] <= time()) {
                    unlink($file);
                    $cleared++;
                }
            }
        }
        
        return $cleared;
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        $fileCount = count(glob($this->cacheDir . '/*.cache'));
        $memoryCount = count($this->memoryCache);
        
        return [
            'memory_items' => $memoryCount,
            'file_items' => $fileCount,
            'cache_dir' => $this->cacheDir,
            'default_ttl' => $this->defaultTtl,
            'max_memory_items' => $this->maxMemoryItems
        ];
    }
}