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
    return new \App\Services\JwtService($c->get(EncryptionService::class));
}, true);

// Register Security Logger Service
$container->register(\App\Services\SecurityLoggerService::class, function (Container $c) {
    return new \App\Services\SecurityLoggerService();
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

// Make container available globally for helper functions
$GLOBALS['container'] = $container;

return $container;