<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Container;
use App\Core\View;
use App\Interfaces\RouterInterface;
use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Router Class
 * 
 * Handles HTTP request routing with support for route parameters and middleware.
 * PHP 8.4 compatible with comprehensive middleware support.
 */
class Router implements RouterInterface
{
    /**
     * @var array<int, array{method: string, path: string, handler: string, middleware: array, pattern?: string, name?: string}>
     */
    private array $routes = [];
    
    /**
     * @var array<MiddlewareInterface> Global middleware applied to all routes
     */
    private array $globalMiddleware = [];
    
    /**
     * @var array<string, array<MiddlewareInterface>> Named middleware groups
     */
    private array $middlewareGroups = [];
    
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
     * @param array<MiddlewareInterface> $middleware Route-specific middleware
     * @throws InvalidArgumentException
     */
    public function addRoute(string $method, string $path, string $handler, array $middleware = []): void
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
            'handler' => $handler,
            'middleware' => $middleware
        ];

        // Convert route path to regex pattern if it contains parameters
        if (str_contains($path, '{') && str_contains($path, '}')) {
            $route['pattern'] = $this->convertPathToPattern($path);
        }

        $this->routes[] = $route;
    }

    public function addRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            $this->addRoute(
                $route['method'],
                $route['path'],
                $route['handler'],
                $route['middleware'] ?? []
            );
        }
    }

    public function addGlobalMiddleware(MiddlewareInterface $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
        
        // Sort by priority (lower numbers first)
        usort($this->globalMiddleware, fn($a, $b) => $a->getPriority() <=> $b->getPriority());
    }

    public function addMiddlewareGroup(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
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
        $request = new Request();
        $response = new Response();
        
        // Extract locale from URI and get the locale-stripped URI for route matching
        $localeInfo = $this->extractLocaleFromUri($uri);
        $locale = $localeInfo['locale'];
        $strippedUri = $localeInfo['uri'];
        
        // Set detected locale in request
        if ($locale) {
            $request->setLocale($locale);
            
            // Set locale in localization service if available
            try {
                $localizationService = $this->container->get(\App\Interfaces\LocalizationServiceInterface::class);
                $localizationService->setLocale($locale);
            } catch (\Throwable $e) {
                // Localization service not available, continue without it
            }
        }
        
        $routeInfo = $this->findRoute($method, $strippedUri);
        
        if (!$routeInfo) {
            $this->handleError(404, 'Page not found');
            return;
        }

        $route = $routeInfo['route'];
        $params = $routeInfo['params'];
        
        // Set route parameters in the request
        $request->setRouteParams($params);

        try {
            // Build middleware stack (global + route-specific)
            $middleware = array_merge($this->globalMiddleware, $route['middleware']);
            
            // Filter middleware that should apply to this request
            $applicableMiddleware = array_filter(
                $middleware,
                fn(MiddlewareInterface $m) => $m->shouldApply($request)
            );
            
            // Sort by priority
            usort($applicableMiddleware, fn($a, $b) => $a->getPriority() <=> $b->getPriority());

            // Execute middleware stack
            $finalHandler = fn() => $this->executeRoute($route['handler'], $params, $request);
            $response = $this->runMiddleware($request, $applicableMiddleware, $finalHandler);
            
            // Send the response
            $response->send();
            
        } catch (\Throwable $e) {
            $this->handleError(500, 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function findRoute(string $method, string $uri): ?array
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
                elseif (isset($route['pattern']) && preg_match($route['pattern'], $uri, $matchResults)) {
                    $matches = true;
                    $params = $this->extractParameters($route['path'], $uri);
                }
            }

            if ($matches) {
                return ['route' => $route, 'params' => $params];
            }
        }

        return null;
    }

    public function generateUrl(string $name, array $params = []): string
    {
        foreach ($this->routes as $route) {
            if (isset($route['name']) && $route['name'] === $name) {
                $url = $route['path'];
                
                // Replace parameters in the URL
                foreach ($params as $key => $value) {
                    $url = str_replace('{' . $key . '}', (string)$value, $url);
                }
                
                return $url;
            }
        }
        
        throw new InvalidArgumentException("Route '{$name}' not found");
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
     * Run middleware stack
     */
    private function runMiddleware(RequestInterface $request, array $middleware, callable $finalHandler): ResponseInterface
    {
        $stack = array_reduce(
            array_reverse($middleware),
            function ($next, $middleware) {
                return function ($request) use ($middleware, $next) {
                    return $middleware->handle($request, $next);
                };
            },
            $finalHandler
        );

        return $stack($request);
    }

    /**
     * Execute a route handler
     * 
     * @param string $handler Controller@method format
     * @param array<string, string> $params Route parameters
     * @param RequestInterface $request The request object
     * @throws RuntimeException
     */
    private function executeRoute(string $handler, array $params = [], ?RequestInterface $request = null): ResponseInterface
    {
        [$controller, $action] = explode('@', $handler);
        
        if (!class_exists($controller)) {
            throw new RuntimeException("Controller class {$controller} not found");
        }

        $controllerInstance = $this->container->get($controller);
        
        if (!method_exists($controllerInstance, $action)) {
            throw new RuntimeException("Method {$action} not found in controller {$controller}");
        }

        // Create response object for the controller
        $response = new Response();
        
        // Get method parameters using reflection to determine what to pass
        $reflection = new \ReflectionMethod($controllerInstance, $action);
        $methodParams = $reflection->getParameters();
        $args = [];
        
        foreach ($methodParams as $param) {
            $paramType = $param->getType();
            
            if ($paramType && !$paramType->isBuiltin()) {
                $typeName = $paramType->getName();
                
                // Pass Request object if method expects it
                if ($typeName === RequestInterface::class || is_subclass_of($typeName, RequestInterface::class)) {
                    $args[] = $request ?? new Request();
                }
                // Pass Response object if method expects it
                elseif ($typeName === ResponseInterface::class || is_subclass_of($typeName, ResponseInterface::class)) {
                    $args[] = $response;
                }
                else {
                    // Try to resolve from container
                    try {
                        $args[] = $this->container->get($typeName);
                    } catch (\Exception $e) {
                        throw new RuntimeException("Cannot resolve parameter {$param->getName()} of type {$typeName}");
                    }
                }
            }
            // Handle route parameters array
            elseif ($param->getName() === 'params' && is_array($params)) {
                $args[] = $params;
            }
            // Handle individual route parameters
            elseif (isset($params[$param->getName()])) {
                $args[] = $params[$param->getName()];
            }
            // Use default value if available
            elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            }
            else {
                throw new RuntimeException("Cannot resolve required parameter {$param->getName()} for {$controller}@{$action}");
            }
        }

        $result = $controllerInstance->$action(...$args);

        return $this->handleResponse($result);
    }

    /**
     * Handle the response from a controller action
     * 
     * @param mixed $result Controller action result
     */
    private function handleResponse(mixed $result): ResponseInterface
    {
        $response = new Response();
        
        if ($result instanceof ResponseInterface) {
            return $result;
        } elseif (is_array($result)) {
            $html = View::render($result['view'], $result['data'] ?? []);
            return $response->html($html);
        } elseif (is_string($result)) {
            return $response->html($result);
        } else {
            throw new RuntimeException('Controller must return ResponseInterface, string, or array with view and data keys');
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
     * @return array<array{method: string, path: string, handler: string, middleware: array}>
     */
    public function getRoutes(): array
    {
        return array_map(function ($route) {
            return [
                'method' => $route['method'],
                'path' => $route['path'],
                'handler' => $route['handler'],
                'middleware' => $route['middleware']
            ];
        }, $this->routes);
    }

    /**
     * Extract locale from URI and return both locale and locale-stripped URI.
     * 
     * @param string $uri Original URI
     * @return array{locale: ?string, uri: string} Locale and stripped URI
     */
    private function extractLocaleFromUri(string $uri): array
    {
        // Get supported locales - try from container or use defaults
        $supportedLocales = ['en', 'fr', 'es', 'de', 'it'];
        
        try {
            $localizationService = $this->container->get(\App\Interfaces\LocalizationServiceInterface::class);
            $supportedLocales = $localizationService->getSupportedLocales();
        } catch (\Throwable $e) {
            // Use default locales if service not available
        }
        
        // Parse URI path
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        
        // Check if URI starts with a supported locale
        if ($path !== '/') {
            $segments = explode('/', trim($path, '/'));
            $firstSegment = $segments[0] ?? '';
            
            if (in_array($firstSegment, $supportedLocales, true)) {
                // Remove locale from path
                array_shift($segments);
                $strippedPath = '/' . implode('/', $segments);
                
                // Handle case where only locale was in path (e.g., /fr -> /)
                if ($strippedPath === '/') {
                    $strippedPath = '/';
                }
                
                // Rebuild URI with query string if present
                $queryString = parse_url($uri, PHP_URL_QUERY);
                $strippedUri = $strippedPath;
                if ($queryString) {
                    $strippedUri .= '?' . $queryString;
                }
                
                return [
                    'locale' => $firstSegment,
                    'uri' => $strippedUri
                ];
            }
        }
        
        // No locale found in URI, return original
        return [
            'locale' => null,
            'uri' => $uri
        ];
    }
}
