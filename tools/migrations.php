<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use PDO;
use DirectoryIterator;

// Retrieve the PDO instance from the container
$container = require __DIR__ . '/../bootstrap.php';
$pdo = $container->get(PDO::class);

// Directory containing migration files
$migrationDir = __DIR__ . '/../src/Migration';

// Iterate over each file in the migration directory
foreach (new DirectoryIterator($migrationDir) as $fileInfo) {
    if ($fileInfo->isDot() || $fileInfo->getExtension() !== 'php') {
        continue;
    }

    // Get the class name from the file name
    $className = 'App\\Migration\\' . $fileInfo->getBasename('.php');

    // Check if the class exists
    if (class_exists($className)) {
        // Instantiate the migration class
        $migration = new $className($pdo);

        // Run the migration
        if (method_exists($migration, 'up')) {
            $migration->up();
            echo "Migration {$className} ran successfully.\n";
        } else {
            echo "Migration {$className} does not have an up method.\n";
        }
    } else {
        echo "Migration class {$className} not found.\n";
    }
}

echo "All migrations completed successfully.\n";