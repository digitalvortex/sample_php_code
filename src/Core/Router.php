<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Container;
use App\Core\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * Router Class
 * 
 * Handles HTTP request routing with support for route parameters and middleware.
 */
class Router
{
    /**
     * @var array<int, array{method: string, path: string, handler: string, pattern?: string}>
     */
    private array $routes = [];
    
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add a route to the router
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $path Route path (supports parameters like /blog/{id})
     * @param string $handler Controller@method format
     * @throws InvalidArgumentException
     */
    public function addRoute(string $method, string $path, string $handler): void
    {
        if (empty($method) || empty($path) || empty($handler)) {
            throw new InvalidArgumentException('Method, path, and handler cannot be empty');
        }

        if (!str_contains($handler, '@')) {
            throw new InvalidArgumentException('Handler must be in format Controller@method');
        }

        $route = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];

        // Convert route path to regex pattern if it contains parameters
        if (str_contains($path, '{') && str_contains($path, '}')) {
            $route['pattern'] = $this->convertPathToPattern($path);
        }

        $this->routes[] = $route;
    }

    /**
     * Dispatch the request to the appropriate controller
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @throws RuntimeException
     */
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        
        foreach ($this->routes as $route) {
            $params = [];
            $matches = false;

            if ($route['method'] === $method) {
                // Check for exact match first
                if ($route['path'] === $uri) {
                    $matches = true;
                }
                // Check for pattern match with parameters
                elseif (isset($route['pattern']) && preg_match($route['pattern'], $uri, $matches)) {
                    $matches = true;
                    $params = $this->extractParameters($route['path'], $uri);
                }
            }

            if ($matches) {
                try {
                    $this->executeRoute($route['handler'], $params);
                    return;
                } catch (\Throwable $e) {
                    $this->handleError(500, 'Internal Server Error: ' . $e->getMessage());
                    return;
                }
            }
        }

        // If no route matches, return a 404 response
        $this->handleError(404, 'Page not found');
    }

    /**
     * Convert a route path with parameters to a regex pattern
     * 
     * @param string $path Route path with {param} syntax
     * @return string Regex pattern
     */
    private function convertPathToPattern(string $path): string
    {
        // Escape forward slashes and convert {param} to named capture groups
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . str_replace('/', '\/', $pattern) . '$#';
    }

    /**
     * Extract parameters from URI based on route path
     * 
     * @param string $routePath Route path with {param} syntax
     * @param string $uri Actual URI
     * @return array<string, string> Parameter values
     */
    private function extractParameters(string $routePath, string $uri): array
    {
        $params = [];
        $routeParts = explode('/', trim($routePath, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        for ($i = 0; $i < count($routeParts); $i++) {
            if (isset($routeParts[$i]) && str_starts_with($routeParts[$i], '{') && str_ends_with($routeParts[$i], '}')) {
                $paramName = trim($routeParts[$i], '{}');
                $params[$paramName] = $uriParts[$i] ?? '';
            }
        }

        return $params;
    }

    /**
     * Execute a route handler
     * 
     * @param string $handler Controller@method format
     * @param array<string, string> $params Route parameters
     * @throws RuntimeException
     */
    private function executeRoute(string $handler, array $params = []): void
    {
        [$controller, $action] = explode('@', $handler);
        
        if (!class_exists($controller)) {
            throw new RuntimeException("Controller class {$controller} not found");
        }

        $controllerInstance = $this->container->get($controller);
        
        if (!method_exists($controllerInstance, $action)) {
            throw new RuntimeException("Method {$action} not found in controller {$controller}");
        }

        // Pass parameters to the action method if it accepts them
        $reflection = new \ReflectionMethod($controllerInstance, $action);
        $paramCount = $reflection->getNumberOfParameters();
        
        if ($paramCount > 0 && !empty($params)) {
            $result = $controllerInstance->$action($params);
        } else {
            $result = $controllerInstance->$action();
        }

        $this->handleResponse($result);
    }

    /**
     * Handle the response from a controller action
     * 
     * @param mixed $result Controller action result
     */
    private function handleResponse(mixed $result): void
    {
        if (is_array($result)) {
            echo View::render($result['view'], $result['data'] ?? []);
        } elseif (is_string($result)) {
            echo $result;
        } else {
            throw new RuntimeException('Controller must return string or array with view and data keys');
        }
    }

    /**
     * Handle HTTP errors
     * 
     * @param int $code HTTP status code
     * @param string $message Error message
     */
    private function handleError(int $code, string $message): void
    {
        http_response_code($code);
        
        $errorView = match($code) {
            404 => 'errors/404',
            500 => 'errors/500',
            default => 'errors/500'
        };

        echo View::render($errorView, ['message' => $message]);
    }

    /**
     * Get all registered routes (for testing/debugging)
     * 
     * @return array<int, array{method: string, path: string, handler: string, pattern?: string}>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
