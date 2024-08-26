<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Seeders\UserSeeder;
use Exception;

/** @var \App\Core\Container $container */
$container = require __DIR__ . '/bootstrap.php';

/** @var UserSeeder $userSeeder */
$userSeeder = $container->get(UserSeeder::class);

try {
    $userSeeder->seed();
    echo "User seeding completed successfully.";
} catch (Exception $e) {
    echo "Error during user seeding: " . $e->getMessage();
}