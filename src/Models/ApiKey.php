<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base;

/**
 * API Key Model
 * 
 * Handles API key storage, validation, and management.
 * Supports rate limiting, expiration, and permission scoping.
 * PHP 8.4 compatible with strict typing.
 */
class ApiKey extends Base
{
    protected static string $table = 'api_keys';
    
    /** @var array<string> */
    protected static array $fillable = [
        'user_id',
        'name',
        'key_hash',
        'permissions',
        'last_used_at',
        'expires_at',
        'created_at',
        'updated_at'
    ];
    
    /** @var array<string> */
    protected static array $encrypted = [];
    
    /** @var array<string> */
    protected static array $hidden = [
        'key_hash'
    ];

    /**
     * Generate a new API key for a user.
     *
     * @param int $userId User ID
     * @param string $name Descriptive name for the API key
     * @param array<string> $permissions List of permissions
     * @param string|null $expiresAt Expiration date (Y-m-d H:i:s format)
     * @return array{key: string, model: static} API key and model instance
     */
    public static function generateKey(int $userId, string $name, array $permissions = [], ?string $expiresAt = null): array
    {
        // Generate cryptographically secure API key
        $apiKey = 'sk_' . bin2hex(random_bytes(32));
        
        // Hash the key for storage (using ARGON2ID for consistency)
        $keyHash = password_hash($apiKey, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);

        $data = [
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => $keyHash,
            'permissions' => json_encode($permissions),
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $model = static::create($data);
        
        return [
            'key' => $apiKey,
            'model' => $model
        ];
    }

    /**
     * Validate an API key and return the associated data.
     *
     * @param string $apiKey The API key to validate
     * @return static|null API key model if valid, null if invalid
     */
    public static function validateKey(string $apiKey): ?static
    {
        // Get all non-expired API keys
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE (expires_at IS NULL OR expires_at > NOW())";
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $result) {
            if (password_verify($apiKey, $result['key_hash'])) {
                $model = static::newFromAttributes($result);
                
                // Update last used timestamp
                $model->updateLastUsed();
                
                return $model;
            }
        }

        return null;
    }

    /**
     * Get API keys for a specific user.
     *
     * @param int $userId User ID
     * @param bool $includeExpired Include expired keys
     * @return array<static>
     */
    public static function getKeysForUser(int $userId, bool $includeExpired = false): array
    {
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE user_id = :user_id";
        
        if (!$includeExpired) {
            $sql .= " AND (expires_at IS NULL OR expires_at > NOW())";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $models = [];
        foreach ($results as $result) {
            $models[] = static::newFromAttributes($result);
        }

        return $models;
    }

    /**
     * Revoke an API key (soft delete by setting expiration).
     *
     * @return bool Success status
     */
    public function revoke(): bool
    {
        return $this->update([
            'expires_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update the last used timestamp.
     *
     * @return bool Success status
     */
    public function updateLastUsed(): bool
    {
        return $this->update([
            'last_used_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Check if the API key has a specific permission.
     *
     * @param string $permission Permission to check
     * @return bool True if permission exists
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getPermissions();
        return in_array($permission, $permissions, true) || in_array('*', $permissions, true);
    }

    /**
     * Get permissions array from JSON string.
     *
     * @return array<string> List of permissions
     */
    public function getPermissions(): array
    {
        $permissionsJson = $this->getAttribute('permissions');
        
        if (!$permissionsJson) {
            return [];
        }

        $permissions = json_decode($permissionsJson, true);
        return is_array($permissions) ? $permissions : [];
    }

    /**
     * Check if the API key is expired.
     *
     * @return bool True if expired
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->getAttribute('expires_at');
        
        if (!$expiresAt) {
            return false; // No expiration set
        }

        return strtotime($expiresAt) < time();
    }

    /**
     * Get the user associated with this API key.
     *
     * @return User|null User model or null if not found
     */
    public function getUser(): ?User
    {
        $userId = $this->getAttribute('user_id');
        return $userId ? User::find($userId) : null;
    }

    /**
     * Get API key usage statistics.
     *
     * @return array<string, mixed> Usage statistics
     */
    public function getUsageStats(): array
    {
        $createdAt = $this->getAttribute('created_at');
        $lastUsedAt = $this->getAttribute('last_used_at');
        
        return [
            'created_at' => $createdAt,
            'last_used_at' => $lastUsedAt,
            'days_since_creation' => $createdAt ? floor((time() - strtotime($createdAt)) / 86400) : 0,
            'days_since_last_use' => $lastUsedAt ? floor((time() - strtotime($lastUsedAt)) / 86400) : null,
            'is_expired' => $this->isExpired(),
            'permissions_count' => count($this->getPermissions())
        ];
    }

    /**
     * Find API keys that haven't been used recently.
     *
     * @param int $days Number of days of inactivity
     * @return array<static> Inactive API keys
     */
    public static function findInactive(int $days = 30): array
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $sql = "SELECT * FROM " . static::getTableName() . " 
                WHERE (last_used_at IS NULL OR last_used_at < :cutoff_date)
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY created_at ASC";
        
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute(['cutoff_date' => $cutoffDate]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $models = [];
        foreach ($results as $result) {
            $models[] = static::newFromAttributes($result);
        }

        return $models;
    }

    /**
     * Clean up expired API keys.
     *
     * @param int $olderThanDays Remove expired keys older than X days
     * @return int Number of keys removed
     */
    public static function cleanupExpired(int $olderThanDays = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$olderThanDays} days"));
        
        $sql = "DELETE FROM " . static::getTableName() . " 
                WHERE expires_at IS NOT NULL 
                AND expires_at < :cutoff_date";
        
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute(['cutoff_date' => $cutoffDate]);
        
        return $stmt->rowCount();
    }
}