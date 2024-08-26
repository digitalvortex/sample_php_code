<?php
declare(strict_types=1);

use App\Migrations\{UserMigration};
//use App\Migrations\SessionsMigration;

// Retrieve the PDO instance from the container
$container = require __DIR__ . '/../bootstrap.php';
$pdo = $container->get(PDO::class);

// List of migrations
$migrations = [
    new UserMigration($pdo),
    //new SessionsMigration($pdo),
];

foreach ($migrations as $migration) {
    echo "Running migration: " . get_class($migration) . "\n";
    $migration->up();
    echo "Migration completed: " . get_class($migration) . "\n";
}

echo "All migrations have been executed successfully.\n";