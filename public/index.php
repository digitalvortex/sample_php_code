<?php
declare(strict_types=1);

use PHPUnit\Event\Runtime\PHP;

$container = require __DIR__ . '/../bootstrap.php';

$pdo = $container->get(PDO::class);
$encryptionService = $container->get(App\Services\EncryptionService::class);

// let's start playing with the URLs

// get the headers

$headers = getallheaders();
var_dump($headers);

// get the user agent
echo "<br />";
$userAgent = $_SERVER['HTTP_USER_AGENT'];
var_dump($userAgent);

$requestUri = $_SERVER['REQUEST_URI']; // '/about'
var_dump($requestUri);
$uriParts = explode('/', trim($requestUri, '/')); // ['about']
echo "<br />";
var_dump($uriParts);

if ($uriParts[0] === 'about') {
    echo "You are on the about page!";
} else {
    echo "You are on the home page!";
}

// redirect to the about page


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