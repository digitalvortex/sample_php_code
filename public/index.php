<?php
declare(strict_types=1);

use PHPUnit\Event\Runtime\PHP;

$container = require __DIR__ . '/../bootstrap.php';

$pdo = $container->get(PDO::class);
$encryptionService = $container->get(App\Services\EncryptionService::class);

echo "<h1>Notice</h1>";
echo "<b>Note:</b> The data used in this example is synthetic, it's test data!" . PHP_EOL;
echo "<br />";
echo "Always keep real PII data safe from attackers!";

$user = new App\Models\User($pdo, $encryptionService);
$userData = $user->find(1);

$truncatedUsername = substr($userData['username'], 0, 3) . '*******';
$userData['username'] = $truncatedUsername;

$userData['password'] = 'Password overridden!';
echo '<pre>' . print_r($userData, true) . '</pre>';



$clearText = 'Hello, World!';
$encryptedText = $encryptionService->encrypt($clearText);
echo "Encrypted text: " . $encryptedText . "<br />";

echo "Decrypted text: " . $encryptionService->decrypt($encryptedText) . "<br />";