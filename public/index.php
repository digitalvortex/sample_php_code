<?php

declare(strict_types=1);

// Security: Only enable debug mode in development environment
$isDevelopment = ($_ENV['APP_ENV'] ?? 'production') === 'development';

if ($isDevelopment) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Production settings - hide errors from users
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    
    // Log errors instead of displaying them
    ini_set('log_errors', 1);
    ini_set('error_log', '/var/log/php-mvc-errors.log');
}

$container = require __DIR__ . '/../bootstrap.php';

$router = $container->get(App\Core\Router::class);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string from URI if present
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$router->dispatch($method, $uri);
