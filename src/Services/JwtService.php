<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\EncryptionService;
use InvalidArgumentException;
use RuntimeException;

/**
 * JWT Service
 * 
 * Handles JWT token creation, validation, and management.
 * Uses HMAC-SHA256 for signing with secure secret key.
 * PHP 8.4 compatible with strict typing.
 */
class JwtService
{
    private EncryptionService $encryptionService;
    private string $secretKey;
    private string $issuer;
    private int $defaultExpiry;

    /**
     * @var array<string> Blacklisted tokens
     */
    private array $blacklistedTokens = [];

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
        $this->secretKey = $_ENV['ENCRYPTION_KEY'] ?? throw new RuntimeException('JWT secret key not configured');
        $this->issuer = $_ENV['JWT_ISSUER'] ?? 'sample-php-mvc';
        $this->defaultExpiry = (int)($_ENV['JWT_EXPIRY'] ?? 3600); // 1 hour default
    }

    /**
     * Create a JWT token for a user.
     *
     * @param int $userId User ID
     * @param array<string> $permissions User permissions/roles
     * @param int|null $expiry Token expiry in seconds (null for default)
     * @return string JWT token
     */
    public function createToken(int $userId, array $permissions = [], ?int $expiry = null): string
    {
        $now = time();
        $exp = $now + ($expiry ?? $this->defaultExpiry);

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        $payload = [
            'iss' => $this->issuer,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'user_id' => $userId,
            'permissions' => $permissions,
            'jti' => $this->generateTokenId()
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = $this->createSignature($headerEncoded . '.' . $payloadEncoded);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }

    /**
     * Validate and decode a JWT token.
     *
     * @param string $token JWT token
     * @return array<string, mixed>|null Token payload if valid, null if invalid
     */
    public function validateToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return null;
            }

            [$headerEncoded, $payloadEncoded, $signature] = $parts;

            // Verify signature
            $expectedSignature = $this->createSignature($headerEncoded . '.' . $payloadEncoded);
            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            // Decode header and payload
            $header = json_decode($this->base64UrlDecode($headerEncoded), true);
            $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

            if (!$header || !$payload) {
                return null;
            }

            // Validate header
            if (($header['typ'] ?? '') !== 'JWT' || ($header['alg'] ?? '') !== 'HS256') {
                return null;
            }

            // Check if token is blacklisted
            if (isset($payload['jti']) && in_array($payload['jti'], $this->blacklistedTokens, true)) {
                return null;
            }

            // Validate times
            $now = time();
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < $now) {
                return null;
            }

            // Check not before
            if (isset($payload['nbf']) && $payload['nbf'] > $now) {
                return null;
            }

            // Validate issuer
            if (isset($payload['iss']) && $payload['iss'] !== $this->issuer) {
                return null;
            }

            return $payload;

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Blacklist a token by its JTI (JWT ID).
     *
     * @param string $jti JWT ID
     */
    public function blacklistToken(string $jti): void
    {
        if (!in_array($jti, $this->blacklistedTokens, true)) {
            $this->blacklistedTokens[] = $jti;
        }
    }

    /**
     * Check if a token is blacklisted.
     *
     * @param string $jti JWT ID
     * @return bool
     */
    public function isTokenBlacklisted(string $jti): bool
    {
        return in_array($jti, $this->blacklistedTokens, true);
    }

    /**
     * Refresh a token (create new token with extended expiry).
     *
     * @param string $token Current valid token
     * @param int|null $expiry New expiry in seconds
     * @return string|null New token if refresh successful, null if invalid
     */
    public function refreshToken(string $token, ?int $expiry = null): ?string
    {
        $payload = $this->validateToken($token);
        
        if (!$payload || !isset($payload['user_id'])) {
            return null;
        }

        // Blacklist the old token
        if (isset($payload['jti'])) {
            $this->blacklistToken($payload['jti']);
        }

        // Create new token
        return $this->createToken(
            $payload['user_id'],
            $payload['permissions'] ?? [],
            $expiry
        );
    }

    /**
     * Extract user ID from a valid token.
     *
     * @param string $token JWT token
     * @return int|null User ID if token is valid, null otherwise
     */
    public function getUserId(string $token): ?int
    {
        $payload = $this->validateToken($token);
        return $payload['user_id'] ?? null;
    }

    /**
     * Extract permissions from a valid token.
     *
     * @param string $token JWT token
     * @return array<string> User permissions
     */
    public function getPermissions(string $token): array
    {
        $payload = $this->validateToken($token);
        return $payload['permissions'] ?? [];
    }

    /**
     * Create HMAC signature for token.
     *
     * @param string $data Data to sign
     * @return string Base64URL encoded signature
     */
    private function createSignature(string $data): string
    {
        $signature = hash_hmac('sha256', $data, $this->secretKey, true);
        return $this->base64UrlEncode($signature);
    }

    /**
     * Generate unique token ID.
     *
     * @return string Unique token identifier
     */
    private function generateTokenId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Base64URL encode data.
     *
     * @param string $data Data to encode
     * @return string Base64URL encoded string
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL decode data.
     *
     * @param string $data Base64URL encoded data
     * @return string Decoded data
     * @throws InvalidArgumentException If decoding fails
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        
        $decoded = base64_decode(strtr($data, '-_', '+/'));
        
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64url data');
        }
        
        return $decoded;
    }
}