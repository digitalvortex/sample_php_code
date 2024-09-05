<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Container;
use App\Core\View;

class Router
{
    private $routes = [];
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addRoute(string $method, string $path, string $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                [$controller, $action] = explode('@', $route['handler']);
                $controllerInstance = $this->container->get($controller);
                $result = $controllerInstance->$action();

                if (is_array($result)) {
                    echo View::render($result['view'], $result['data'] ?? []);
                } else {
                    echo $result;
                }
                return;
            }
        }

        // If no route matches, return a 404 response
        http_response_code(404);
        echo View::render('error/404', ['message' => 'Page not found']);
    }
}
