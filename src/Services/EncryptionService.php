<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Class Encryption
 *
 * This class provides methods to encrypt and decrypt data using the Sodium library.
 *
 * @package App\Services
 */
class EncryptionService
{
    /**
     * @var string The encryption key.
     */
    private $key;

    /**
     * Encryption constructor.
     *
     * Initializes the encryption key from the environment variable.
     *
     * @throws \Exception If the key is not set or the key length is invalid.
     */
    public function __construct()
    {
        if (!isset($_ENV['KEY'])) {
            throw new \Exception('Key not set');
        }
        $this->key = base64_decode($_ENV['KEY']);
    
        if (strlen($this->key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \Exception('Key length must be ' . SODIUM_CRYPTO_SECRETBOX_KEYBYTES . ' bytes');
        }
    }

    /**
     * Encrypt a value.
     *
     * @param string $value The value to encrypt.
     * @return string The encrypted value, encoded in base64.
     */
    public function encrypt(string $value): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = sodium_crypto_secretbox($value, $nonce, $this->key);
        return base64_encode($nonce . $cipherText);
    }

    /**
     * Decrypt a value.
     *
     * @param string $value The encrypted value, encoded in base64.
     * @return string The decrypted value.
     * @throws \Exception If the MAC is invalid.
     */
    public function decrypt(string $value): string
    {
        $value = base64_decode($value);
        $nonce = mb_substr($value, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $value = mb_substr($value, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        $plainText = sodium_crypto_secretbox_open($value, $nonce, $this->key);

        if ($plainText === false) {
            throw new \Exception('Invalid MAC');
        }
        
        return $plainText;
    }
}