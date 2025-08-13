<?php
declare(strict_types=1);

// Test specific route resolution
require_once __DIR__ . '/bootstrap.php';

$container = require __DIR__ . '/bootstrap.php';
$router = $container->get(App\Core\Router::class);

// Test URI
$testUri = '/register';
$method = 'GET';

echo "Testing route resolution for: $method $testUri\n";
echo "==========================================\n\n";

// Use reflection to call private methods
$reflection = new ReflectionClass($router);

// Test extractLocaleFromUri
$extractMethod = $reflection->getMethod('extractLocaleFromUri');
$extractMethod->setAccessible(true);
$localeInfo = $extractMethod->invoke($router, $testUri);

echo "1. Locale extraction result:\n";
echo "   Original URI: $testUri\n";
echo "   Detected locale: " . ($localeInfo['locale'] ?? 'none') . "\n";
echo "   Stripped URI: " . $localeInfo['uri'] . "\n\n";

// Test findRoute
$findMethod = $reflection->getMethod('findRoute');
$findMethod->setAccessible(true);
$routeInfo = $findMethod->invoke($router, $method, $localeInfo['uri']);

echo "2. Route finding result:\n";
if ($routeInfo) {
    echo "   ✓ Route found!\n";
    echo "   Handler: " . $routeInfo['route']['handler'] . "\n";
    echo "   Pattern: " . ($routeInfo['route']['pattern'] ?? 'n/a') . "\n";
} else {
    echo "   ✗ Route NOT found\n";
    
    // Let's check what's happening with the routes
    $routesProperty = $reflection->getProperty('routes');
    $routesProperty->setAccessible(true);
    $routes = $routesProperty->getValue($router);
    
    echo "\n3. Debug - Looking for exact matches:\n";
    foreach ($routes as $route) {
        if ($route['method'] === $method && $route['path'] === $testUri) {
            echo "   Found exact match in routes array!\n";
            echo "   Route: " . json_encode($route, JSON_PRETTY_PRINT) . "\n";
        }
    }
}