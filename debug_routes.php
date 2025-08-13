<?php
declare(strict_types=1);

// Debug script to check registered routes
require_once __DIR__ . '/bootstrap.php';

$container = require __DIR__ . '/bootstrap.php';

// Get the router
$router = $container->get(App\Core\Router::class);

// Use reflection to access private routes property
$reflection = new ReflectionClass($router);
$routesProperty = $reflection->getProperty('routes');
$routesProperty->setAccessible(true);
$routes = $routesProperty->getValue($router);

echo "Registered Routes:\n";
echo "==================\n\n";

// Group routes by method
$groupedRoutes = [];
foreach ($routes as $route) {
    $method = $route['method'];
    if (!isset($groupedRoutes[$method])) {
        $groupedRoutes[$method] = [];
    }
    $groupedRoutes[$method][] = $route;
}

// Display routes
foreach ($groupedRoutes as $method => $methodRoutes) {
    echo "$method Routes:\n";
    foreach ($methodRoutes as $route) {
        echo "  " . $route['path'] . " -> " . $route['handler'] . "\n";
    }
    echo "\n";
}

// Check for authentication routes specifically
echo "Authentication Routes Check:\n";
echo "===========================\n";
$authPaths = ['/register', '/login', '/logout', '/dashboard'];
foreach ($authPaths as $path) {
    $found = false;
    foreach ($routes as $route) {
        if ($route['path'] === $path) {
            $found = true;
            echo "✓ $path found -> " . $route['handler'] . "\n";
            break;
        }
    }
    if (!$found) {
        echo "✗ $path NOT FOUND\n";
    }
}

// Check if controllers exist
echo "\nController Classes Check:\n";
echo "========================\n";
$controllers = [
    'App\Controllers\User\AuthController',
    'App\Controllers\User\RegisterController',
];

foreach ($controllers as $controller) {
    if (class_exists($controller)) {
        echo "✓ $controller exists\n";
        $methods = get_class_methods($controller);
        echo "  Methods: " . implode(', ', $methods) . "\n";
    } else {
        echo "✗ $controller NOT FOUND\n";
    }
}