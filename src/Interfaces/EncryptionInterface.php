<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface EncryptionInterface
 * 
 * Defines the contract for encryption services.
 * PHP 8.4 compatible with strict typing and modern encryption standards.
 */
interface EncryptionInterface
{
    /**
     * Encrypt a value.
     * 
     * @param string $value The value to encrypt
     * @return string The encrypted value
     * @throws \Exception If encryption fails
     */
    public function encrypt(string $value): string;

    /**
     * Decrypt a value.
     * 
     * @param string $value The encrypted value
     * @return string The decrypted value
     * @throws \Exception If decryption fails
     */
    public function decrypt(string $value): string;

    /**
     * Generate a new encryption key.
     * 
     * @return string Base64 encoded encryption key
     */
    public function generateKey(): string;

    /**
     * Encrypt an array or object.
     * 
     * @param mixed $data Data to encrypt
     * @return string Encrypted and serialized data
     * @throws \Exception If encryption fails
     */
    public function encryptData(mixed $data): string;

    /**
     * Decrypt and unserialize data.
     * 
     * @param string $encryptedData Encrypted data
     * @return mixed Decrypted and unserialized data
     * @throws \Exception If decryption fails
     */
    public function decryptData(string $encryptedData): mixed;

    /**
     * Check if a value can be decrypted (is valid encrypted data).
     * 
     * @param string $value Value to check
     * @return bool True if value can be decrypted
     */
    public function canDecrypt(string $value): bool;

    /**
     * Get the encryption algorithm being used.
     * 
     * @return string Algorithm name
     */
    public function getAlgorithm(): string;

    /**
     * Get the key size in bytes.
     * 
     * @return int Key size
     */
    public function getKeySize(): int;
}