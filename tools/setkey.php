<?php
declare(strict_types=1);

/**
 * Generates a secret key using the sodium_crypto_secretbox_keygen function.
 * This key is used to encrypt and decrypt data in the application.
 * The key is stored in the .env file.
 * Keep the key safe and do not add it to your repository or share it with anyone.
 *
 * @throws Exception if key generation fails.
 */
try {
    $key = sodium_crypto_secretbox_keygen();    
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

/**
 * Validates that the generated key is a valid sodium key.
 */
if (strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
    echo "Invalid key length: " . strlen($key) . "\n";
    exit(1);
} else {
    /**
     * Outputs the generated key in base64-encoded format.
     */
    echo 'Copy this key to your .env file: ' . base64_encode($key) . "\n";

    /**
     * Outputs a message confirming the key is valid.
     */
    echo "The key has been tested and is a valid sodium key.\n";

    /**
     * Outputs a warning message to keep the key safe.
     */
    echo "WARNING: Keep this key safe and do not add it to your repository or share it with anyone.\n";
}