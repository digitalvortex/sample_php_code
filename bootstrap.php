<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use App\Config\LoadEnv;
use App\Core\Container;
use App\Definitions\DatabaseDefinitions;
use App\Models\User;
use App\Services\EncryptionService;

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

// let's add the UserModel to the container
$container->register(User::class, function (Container $c) {
    return new User($c->get(PDO::class), $c->get(EncryptionService::class));
}, true);

return $container;