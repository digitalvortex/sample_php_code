<?php

declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use App\Config\LoadEnv;
use App\Core\Container;
use App\Definitions\DatabaseDefinitions;
use App\Definitions\RoutingDefinitions;
use App\Definitions\ModelsDefinitions;
use App\Seeders\UserSeeder;
use App\Services\EncryptionService;
use App\Response\ValidationResponse;

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

// Register all models from ModelsDefinitions
use App\Definitions\ModelsDefinitions as MD;
foreach (MD::getDefinitions() as $model => $factory) {
    $container->register($model, $factory, true);
}

foreach (RoutingDefinitions::getDefinitions() as $name => $definition) {
    $container->register($name, $definition, true);
}

$container->register(ValidationResponse::class, function (Container $c) {
    return new ValidationResponse();
}, true);

return $container;