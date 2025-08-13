<?php
declare(strict_types=1);

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simulate the exact request processing from index.php
try {
    echo "Loading bootstrap...\n";
    $container = require __DIR__ . '/bootstrap.php';
    echo "Bootstrap loaded successfully.\n";
    
    echo "Getting router from container...\n";
    $router = $container->get(App\Core\Router::class);
    echo "Router retrieved successfully.\n";
    
    // Simulate GET /register request
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/register';
    
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = $_SERVER['REQUEST_URI'];
    
    echo "Simulating dispatch for: $method $uri\n";
    
    // Remove query string from URI if present
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);
    
    echo "Processing URI: $uri\n";
    
    // Call dispatch - this is where the error likely occurs
    $router->dispatch($method, $uri);
    
} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}