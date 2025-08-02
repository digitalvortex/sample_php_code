<?php
declare(strict_types=1);

namespace App\Models;

/**
 * JWT Blacklist Model
 * 
 * Manages blacklisted JWT tokens in the database.
 * PHP 8.4 compatible with strict typing.
 */
class JwtBlacklist extends Base
{
    protected static string $table = 'jwt_blacklist';
    
    /** @var array<string> */
    protected static array $fillable = [
        'jti',
        'user_id',
        'expires_at',
        'reason'
    ];
    
    /** @var array<string> */
    protected static array $hidden = [];
    
    /**
     * Check if a JWT token is blacklisted.
     * 
     * @param string $jti JWT ID
     * @return bool True if token is blacklisted
     */
    public static function isBlacklisted(string $jti): bool
    {
        $record = static::findOneBy(['jti' => $jti]);
        
        if (!$record) {
            return false;
        }
        
        // Check if token has expired (no need to keep expired blacklisted tokens)
        $expiresAt = $record->getAttribute('expires_at');
        if ($expiresAt && strtotime($expiresAt) < time()) {
            // Token has expired, remove from blacklist
            $record->delete();
            return false;
        }
        
        return true;
    }
    
    /**
     * Add a JWT token to the blacklist.
     * 
     * @param string $jti JWT ID
     * @param int $userId User ID who owns the token
     * @param int $expiresAt Token expiration timestamp
     * @param string|null $reason Reason for blacklisting
     * @return static
     */
    public static function blacklistToken(string $jti, int $userId, int $expiresAt, ?string $reason = null): static
    {
        // Check if already blacklisted
        $existing = static::findOneBy(['jti' => $jti]);
        if ($existing) {
            return $existing;
        }
        
        return static::create([
            'jti' => $jti,
            'user_id' => $userId,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'reason' => $reason
        ]);
    }
    
    /**
     * Clean up expired blacklisted tokens.
     * This should be run periodically to maintain database performance.
     * 
     * @return int Number of tokens removed
     */
    public static function cleanupExpired(): int
    {
        $tableName = static::escapeIdentifier(static::getTableName());
        $currentTime = date('Y-m-d H:i:s');
        $sql = "DELETE FROM {$tableName} WHERE expires_at < ?";
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute([$currentTime]);
        
        return $stmt->rowCount();
    }
    
    /**
     * Get all blacklisted tokens for a specific user.
     * 
     * @param int $userId User ID
     * @return array<static>
     */
    public static function getByUserId(int $userId): array
    {
        return static::findBy(['user_id' => $userId]);
    }
    
    /**
     * Remove a token from the blacklist.
     * 
     * @param string $jti JWT ID
     * @return bool True if token was removed
     */
    public static function removeFromBlacklist(string $jti): bool
    {
        $record = static::findOneBy(['jti' => $jti]);
        
        if (!$record) {
            return false;
        }
        
        return $record->delete();
    }
}