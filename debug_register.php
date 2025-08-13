<?php

declare(strict_types=1);

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting debug process...\n";

try {
    echo "1. Loading bootstrap...\n";
    $container = require __DIR__ . '/bootstrap.php';
    echo "✓ Bootstrap loaded successfully\n";

    echo "2. Getting router...\n";
    $router = $container->get(App\Core\Router::class);
    echo "✓ Router obtained successfully\n";

    echo "3. Testing RegisterController instantiation...\n";
    $registerController = $container->get(App\Controllers\User\RegisterController::class);
    echo "✓ RegisterController instantiated successfully\n";

    echo "4. Testing router dispatch for GET /register...\n";
    
    // Mock $_SERVER variables for the test
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/register';
    
    ob_start();
    $router->dispatch('GET', '/register');
    $output = ob_get_clean();
    
    echo "✓ Route dispatch completed successfully\n";
    echo "Output length: " . strlen($output) . " characters\n";
    
    if (strlen($output) > 0) {
        echo "First 200 characters of output:\n";
        echo substr($output, 0, 200) . "...\n";
    }

} catch (Throwable $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nDebug process completed.\n";