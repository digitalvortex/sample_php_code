<?php
declare(strict_types=1);

use App\Migrations\{UserMigration, SessionsMigration, ApiKeyMigration, UserTokenMigration, ContactMigration, LevelMigration, BlogMigration, JwtBlacklistMigration};

// Retrieve the PDO instance from the container
$container = require __DIR__ . '/../bootstrap.php';
$pdo = $container->get(PDO::class);

// List of migration classes - order matters for foreign key dependencies
$migrationClasses = [
    UserMigration::class,      // Must be first - referenced by api_keys and user_tokens
    LevelMigration::class,     // Must be before users if users references levels
    SessionsMigration::class,
    BlogMigration::class,
    ContactMigration::class,
    ApiKeyMigration::class,    // Requires users table
    UserTokenMigration::class, // Requires users table
    JwtBlacklistMigration::class, // Requires users table
];

foreach ($migrationClasses as $migrationClass) {
    echo "Running migration: " . $migrationClass . "\n";
    
    $migration = new $migrationClass($pdo);
    $migration->up();
    
    echo "Migration completed: " . $migrationClass . "\n";
}

echo "All migrations have been executed successfully.\n";