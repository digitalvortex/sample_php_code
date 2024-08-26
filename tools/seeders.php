<?php
declare(strict_types=1);

use App\Seeders\UserSeeder;
use App\Models\User;

/** @var \App\Core\Container $container */
$container = require __DIR__ . '/../bootstrap.php'; // Adjust the path as needed
$userModel = $container->get(User::class);

$seeders = [
    new UserSeeder($userModel),
];

foreach ($seeders as $seeder) {
    echo "Running seeder: " . get_class($seeder) . "\n";
    $seeder->seed();
    echo "Seeder completed: " . get_class($seeder) . "\n";
}