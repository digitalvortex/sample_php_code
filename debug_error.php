<?php
declare(strict_types=1);

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    echo "Loading bootstrap...\n";
    $container = require __DIR__ . '/bootstrap.php';
    echo "Bootstrap loaded successfully.\n";
    
    echo "Testing Router instantiation...\n";
    $router = $container->get(App\Core\Router::class);
    echo "Router instantiated successfully.\n";
    
    echo "Testing middleware resolution...\n";
    try {
        $localeMiddleware = $container->get(App\Middleware\LocaleMiddleware::class);
        echo "LocaleMiddleware resolved successfully.\n";
    } catch (Exception $e) {
        echo "ERROR: LocaleMiddleware failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $csrfMiddleware = $container->get(App\Middleware\CSRFMiddleware::class);
        echo "CSRFMiddleware resolved successfully.\n";
    } catch (Exception $e) {
        echo "ERROR: CSRFMiddleware failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $authMiddleware = $container->get(App\Middleware\AuthenticationMiddleware::class);
        echo "AuthenticationMiddleware resolved successfully.\n";
    } catch (Exception $e) {
        echo "ERROR: AuthenticationMiddleware failed: " . $e->getMessage() . "\n";
    }
    
    echo "Testing RegisterController instantiation...\n";
    try {
        $registerController = $container->get(App\Controllers\User\RegisterController::class);
        echo "RegisterController instantiated successfully.\n";
    } catch (Exception $e) {
        echo "ERROR: RegisterController failed: " . $e->getMessage() . "\n";
    }
    
} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}