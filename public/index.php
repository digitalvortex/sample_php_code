<?php
declare(strict_types=1);

use PHPUnit\Event\Runtime\PHP;

$container = require __DIR__ . '/../bootstrap.php';

try {
    // Retrieve the DatabaseService from the container
    $databaseService = $container->resolve(App\Service\DatabaseService::class);
    $encryptionService = $container->resolve(App\Service\EncryptionService::class);

    // Get the PDO connection from the DatabaseService
    $pdo = $databaseService->getConnection();

    // Use the PDO connection to perform a query
    $stmt = $pdo->query('SELECT DATABASE()');
    $databaseName = $stmt->fetchColumn();

    echo "Connection test database established <br />";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$clearText = 'Hello, World!';
$encryptedText = $encryptionService->encrypt($clearText);
echo "Encrypted text: " . $encryptedText . "<br />";

echo "Decrypted text: " . $encryptionService->decrypt($encryptedText) . "<br />";