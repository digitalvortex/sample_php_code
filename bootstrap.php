<?php

declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use App\Config\LoadEnv;
use App\Core\Container;
use App\Definitions\DatabaseDefinitions;
use App\Definitions\RoutingDefinitions;
use App\Definitions\LocalizationDefinitions;
//use App\Definitions\ModelsDefinitions;
//use App\Seeders\UserSeeder;
use App\Services\EncryptionService;
use App\Response\ValidationResponse;
use App\Security\CSRFToken; 

$env = __DIR__ . '/.env';
if (!file_exists($env)) {
    die("Environment file not found.");
}

LoadEnv::load($env);

$container = new Container();

// Register services from DatabaseDefinitions
foreach (DatabaseDefinitions::getDefinitions() as $name => $definition) {
    $container->register($name, $definition, true);
}

$container->register(EncryptionService::class, function (Container $c) {
    return new EncryptionService();
}, true);

// Register JWT Service
$container->register(\App\Services\JwtService::class, function (Container $c) {
    return new \App\Services\JwtService($c->get(EncryptionService::class), $c->get(PDO::class));
}, true);

// Register Security Logger Service
$container->register(\App\Services\SecurityLoggerService::class, function (Container $c) {
    return new \App\Services\SecurityLoggerService();
}, true);

// Register Rate Limit Service
$container->register(\App\Services\RateLimitService::class, function (Container $c) {
    return new \App\Services\RateLimitService();
}, true);

// Register Brute Force Protection Service
$container->register(\App\Services\BruteForceProtectionService::class, function (Container $c) {
    return new \App\Services\BruteForceProtectionService(
        $c->get(\App\Services\SecurityLoggerService::class)
    );
}, true);

// Register Security Monitoring Service
$container->register(\App\Services\SecurityMonitoringService::class, function (Container $c) {
    return new \App\Services\SecurityMonitoringService(
        $c->get(\App\Services\SecurityLoggerService::class),
        $c->get(\App\Services\BruteForceProtectionService::class)
    );
}, true);

// Register Authentication Middleware
$container->register(\App\Middleware\AuthenticationMiddleware::class, function (Container $c) {
    return new \App\Middleware\AuthenticationMiddleware(
        $c->get(\App\Services\JwtService::class),
        $c->get(\App\Services\SecurityLoggerService::class)
    );
}, true);

// Register CSRF Middleware
$container->register(\App\Middleware\CSRFMiddleware::class, function (Container $c) {
    return new \App\Middleware\CSRFMiddleware(
        $c->get(\App\Security\CSRFToken::class)
    );
}, true);

// Register Rate Limit Middleware
$container->register(\App\Middleware\RateLimitMiddleware::class, function (Container $c) {
    return new \App\Middleware\RateLimitMiddleware(
        $c->get(\App\Services\RateLimitService::class),
        $c->get(\App\Services\SecurityLoggerService::class),
        $c->get(\App\Services\BruteForceProtectionService::class),
        $c->get(\App\Services\SecurityMonitoringService::class)
    );
}, true);

// Register User Validation Service
$container->register(\App\Services\UserValidationService::class, function (Container $c) {
    return new \App\Services\UserValidationService($c->get(\App\Response\ValidationResponse::class));
}, true);

// Register Session Service
$container->register(\App\Services\SessionService::class, function (Container $c) {
    return new \App\Services\SessionService(
        $c->get(\App\Services\SecurityLoggerService::class),
        $c->get(\App\Services\EncryptionService::class)
    );
}, true);

// Register all models from ModelsDefinitions
use App\Definitions\ModelsDefinitions as MD;
foreach (MD::getDefinitions() as $model => $factory) {
    $container->register($model, $factory, true);
}

foreach (RoutingDefinitions::getDefinitions() as $name => $definition) {
    $container->register($name, $definition, true);
}

// Register localization services
foreach (LocalizationDefinitions::getDefinitions() as $name => $definition) {
    $container->register($name, $definition, true);
}

// Load localization helper functions
require_once __DIR__ . '/src/Helpers/LocalizationHelpers.php';
require_once __DIR__ . '/src/Helpers/ConfigHelpers.php';

$container->register(ValidationResponse::class, function (Container $c) {
    return new ValidationResponse();
}, true);

$container->register(CSRFToken::class, function (Container $c) {
    return new CSRFToken();
}, true);

// Register Response Interface implementation
$container->register(\App\Interfaces\ResponseInterface::class, function (Container $c) {
    return new \App\Core\Response();
}, true);

// Make container available globally for helper functions
$GLOBALS['container'] = $container;

return $container;