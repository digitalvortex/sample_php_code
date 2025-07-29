<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface RouterInterface
 * 
 * Defines the contract for HTTP request routers.
 * PHP 8.4 compatible with middleware support and strict typing.
 */
interface RouterInterface
{
    /**
     * Add a route to the router.
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $path Route path (supports parameters like /blog/{id})
     * @param string $handler Controller@method format
     * @param array<MiddlewareInterface> $middleware Route-specific middleware
     * @return void
     * @throws \InvalidArgumentException For invalid route parameters
     */
    public function addRoute(string $method, string $path, string $handler, array $middleware = []): void;

    /**
     * Add multiple routes at once.
     * 
     * @param array<array{method: string, path: string, handler: string, middleware?: array}> $routes
     * @return void
     */
    public function addRoutes(array $routes): void;

    /**
     * Dispatch the request to the appropriate controller.
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return void
     * @throws \RuntimeException If route not found or handler invalid
     */
    public function dispatch(string $method, string $uri): void;

    /**
     * Add global middleware that applies to all routes.
     * 
     * @param MiddlewareInterface $middleware The middleware instance
     * @return void
     */
    public function addGlobalMiddleware(MiddlewareInterface $middleware): void;

    /**
     * Add middleware group that can be applied to multiple routes.
     * 
     * @param string $name Group name
     * @param array<MiddlewareInterface> $middleware Array of middleware
     * @return void
     */
    public function addMiddlewareGroup(string $name, array $middleware): void;

    /**
     * Get all registered routes.
     * 
     * @return array<array{method: string, path: string, handler: string, middleware: array}> All routes
     */
    public function getRoutes(): array;

    /**
     * Find a route by method and URI.
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return array{route: array, params: array}|null Route info with parameters or null if not found
     */
    public function findRoute(string $method, string $uri): ?array;

    /**
     * Generate URL for a named route.
     * 
     * @param string $name Route name
     * @param array<string, mixed> $params Route parameters
     * @return string Generated URL
     * @throws \InvalidArgumentException If route not found
     */
    public function generateUrl(string $name, array $params = []): string;
}