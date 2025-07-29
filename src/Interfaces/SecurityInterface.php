<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface SecurityInterface
 * 
 * Defines the contract for security-related services.
 * PHP 8.4 compatible with strict typing and comprehensive security features.
 */
interface SecurityInterface
{
    /**
     * Generate a secure token.
     * 
     * @param int $length Token length in bytes
     * @return string Generated token
     */
    public function generateToken(int $length = 32): string;

    /**
     * Generate a CSRF token.
     * 
     * @return string CSRF token
     */
    public function generateCSRFToken(): string;

    /**
     * Verify a CSRF token.
     * 
     * @param string $token Token to verify
     * @param string|null $sessionToken Stored session token
     * @return bool True if token is valid
     */
    public function verifyCSRFToken(string $token, ?string $sessionToken = null): bool;

    /**
     * Hash a password securely.
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public function hashPassword(string $password): string;

    /**
     * Verify a password against its hash.
     * 
     * @param string $password Plain text password
     * @param string $hash Stored password hash
     * @return bool True if password is valid
     */
    public function verifyPassword(string $password, string $hash): bool;

    /**
     * Sanitize input data.
     * 
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    public function sanitizeInput(mixed $data): mixed;

    /**
     * Escape output for HTML context.
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escapeHtml(string $string): string;

    /**
     * Escape output for URL context.
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escapeUrl(string $string): string;

    /**
     * Generate a secure random string.
     * 
     * @param int $length String length
     * @param string $characters Character set to use
     * @return string Random string
     */
    public function randomString(int $length = 16, string $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'): string;

    /**
     * Validate input against common security threats.
     * 
     * @param mixed $input Input to validate
     * @param array<string> $rules Validation rules
     * @return array<string> Validation errors (empty if valid)
     */
    public function validateInput(mixed $input, array $rules = []): array;
}