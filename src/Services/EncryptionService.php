<?php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\EncryptionInterface;

/**
 * Class EncryptionService
 *
 * This class provides methods to encrypt and decrypt data using the Sodium library.
 * PHP 8.4 compatible with comprehensive EncryptionInterface implementation.
 *
 * @package App\Services
 */
class EncryptionService implements EncryptionInterface
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

    /**
     * Generate a new encryption key.
     */
    public function generateKey(): string
    {
        return base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    /**
     * Encrypt an array or object.
     */
    public function encryptData(mixed $data): string
    {
        $serialized = serialize($data);
        return $this->encrypt($serialized);
    }

    /**
     * Decrypt and unserialize data.
     */
    public function decryptData(string $encryptedData): mixed
    {
        $decrypted = $this->decrypt($encryptedData);
        return unserialize($decrypted);
    }

    /**
     * Check if a value can be decrypted (is valid encrypted data).
     */
    public function canDecrypt(string $value): bool
    {
        try {
            $this->decrypt($value);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get the encryption algorithm being used.
     */
    public function getAlgorithm(): string
    {
        return 'XSalsa20-Poly1305';
    }

    /**
     * Get the key size in bytes.
     */
    public function getKeySize(): int
    {
        return SODIUM_CRYPTO_SECRETBOX_KEYBYTES;
    }

    /**
     * Encrypt a file.
     */
    public function encryptFile(string $inputPath, string $outputPath): bool
    {
        if (!file_exists($inputPath)) {
            throw new \Exception('Input file does not exist');
        }

        $content = file_get_contents($inputPath);
        if ($content === false) {
            throw new \Exception('Cannot read input file');
        }

        $encrypted = $this->encrypt($content);
        return file_put_contents($outputPath, $encrypted) !== false;
    }

    /**
     * Decrypt a file.
     */
    public function decryptFile(string $inputPath, string $outputPath): bool
    {
        if (!file_exists($inputPath)) {
            throw new \Exception('Input file does not exist');
        }

        $content = file_get_contents($inputPath);
        if ($content === false) {
            throw new \Exception('Cannot read input file');
        }

        $decrypted = $this->decrypt($content);
        return file_put_contents($outputPath, $decrypted) !== false;
    }

    /**
     * Generate a secure hash of the encryption key for verification.
     */
    public function getKeyHash(): string
    {
        return hash('sha256', $this->key);
    }
}