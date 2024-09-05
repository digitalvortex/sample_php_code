<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

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
