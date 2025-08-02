<?php
declare(strict_types=1);

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\JwtService;
use App\Services\EncryptionService;
use App\Models\JwtBlacklist;
use App\Models\User;
use PDO;

/**
 * JWT Blacklist Test
 * 
 * Tests JWT blacklist functionality with database persistence.
 * PHP 8.4 compatible with strict typing.
 */
class JwtBlacklistTest extends TestCase
{
    private JwtService $jwtService;
    private PDO $pdo;
    private EncryptionService $encryptionService;

    protected function setUp(): void
    {
        // Set up environment for testing
        // Generate a proper base64 encoded key for sodium
        $rawKey = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $_ENV['KEY'] = base64_encode($rawKey);
        $_ENV['ENCRYPTION_KEY'] = $_ENV['KEY']; // JwtService uses this
        $_ENV['JWT_ISSUER'] = 'test-issuer';
        $_ENV['JWT_EXPIRY'] = '3600';

        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create users table
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL
            )
        ");

        // Create jwt_blacklist table
        $this->pdo->exec("
            CREATE TABLE jwt_blacklist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jti VARCHAR(255) NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                expires_at DATETIME NOT NULL,
                blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                reason VARCHAR(255) NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        // Initialize services
        $this->encryptionService = new EncryptionService();
        $this->jwtService = new JwtService($this->encryptionService, $this->pdo);

        // Initialize models
        User::initialize($this->pdo, $this->encryptionService);
        JwtBlacklist::initialize($this->pdo, $this->encryptionService);

        // Create test user
        $this->pdo->exec("
            INSERT INTO users (username, email, password, first_name, last_name) 
            VALUES ('testuser', 'test@example.com', 'password', 'Test', 'User')
        ");
    }

    public function testCreateAndValidateToken(): void
    {
        $userId = 1;
        $permissions = ['read', 'write'];
        
        $token = $this->jwtService->createToken($userId, $permissions);
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        $payload = $this->jwtService->validateToken($token);
        
        $this->assertIsArray($payload);
        $this->assertEquals($userId, $payload['user_id']);
        $this->assertEquals($permissions, $payload['permissions']);
        $this->assertArrayHasKey('jti', $payload);
    }

    public function testBlacklistToken(): void
    {
        $userId = 1;
        $token = $this->jwtService->createToken($userId);
        
        // Token should be valid initially
        $payload = $this->jwtService->validateToken($token);
        $this->assertIsArray($payload);
        
        $jti = $payload['jti'];
        $exp = $payload['exp'];
        
        // Blacklist the token
        $this->jwtService->blacklistToken($jti, $userId, $exp, 'Test revocation');
        
        // Token should now be invalid
        $this->assertNull($this->jwtService->validateToken($token));
        
        // Check if token is marked as blacklisted
        $this->assertTrue($this->jwtService->isTokenBlacklisted($jti));
    }

    public function testRevokeToken(): void
    {
        $userId = 1;
        $token = $this->jwtService->createToken($userId);
        
        // Token should be valid initially
        $this->assertIsArray($this->jwtService->validateToken($token));
        
        // Revoke the token
        $result = $this->jwtService->revokeToken($token, 'User logout');
        $this->assertTrue($result);
        
        // Token should now be invalid
        $this->assertNull($this->jwtService->validateToken($token));
    }

    public function testRefreshTokenBlacklistsOldToken(): void
    {
        $userId = 1;
        $oldToken = $this->jwtService->createToken($userId);
        
        // Get the old token's JTI
        $oldPayload = $this->jwtService->validateToken($oldToken);
        $oldJti = $oldPayload['jti'];
        
        // Refresh the token
        $newToken = $this->jwtService->refreshToken($oldToken);
        
        $this->assertIsString($newToken);
        $this->assertNotEquals($oldToken, $newToken);
        
        // Old token should be blacklisted
        $this->assertTrue($this->jwtService->isTokenBlacklisted($oldJti));
        $this->assertNull($this->jwtService->validateToken($oldToken));
        
        // New token should be valid
        $this->assertIsArray($this->jwtService->validateToken($newToken));
    }

    public function testJwtBlacklistModel(): void
    {
        $jti = 'test-jti-12345';
        $userId = 1;
        $expiresAt = time() + 3600;
        
        // Create blacklist entry
        $blacklistEntry = JwtBlacklist::blacklistToken($jti, $userId, $expiresAt, 'Test reason');
        
        $this->assertInstanceOf(JwtBlacklist::class, $blacklistEntry);
        $this->assertEquals($jti, $blacklistEntry->getAttribute('jti'));
        $this->assertEquals($userId, $blacklistEntry->getAttribute('user_id'));
        $this->assertEquals('Test reason', $blacklistEntry->getAttribute('reason'));
        
        // Check if token is blacklisted
        $this->assertTrue(JwtBlacklist::isBlacklisted($jti));
        
        // Try to blacklist the same token again (should return existing)
        $duplicate = JwtBlacklist::blacklistToken($jti, $userId, $expiresAt);
        $this->assertEquals($blacklistEntry->getId(), $duplicate->getId());
    }

    public function testCleanupExpiredTokens(): void
    {
        $jti1 = 'expired-token-1';
        $jti2 = 'valid-token-2';
        $userId = 1;
        
        // Create expired token
        JwtBlacklist::blacklistToken($jti1, $userId, time() - 3600, 'Expired token');
        
        // Create valid token
        JwtBlacklist::blacklistToken($jti2, $userId, time() + 3600, 'Valid token');
        
        // Check count before cleanup
        $countBefore = JwtBlacklist::count();
        $this->assertEquals(2, $countBefore);
        
        // Clean up expired tokens
        $cleanedCount = $this->jwtService->cleanupExpiredTokens();
        
        $this->assertEquals(1, $cleanedCount);
        
        // Check count after cleanup
        $countAfter = JwtBlacklist::count();
        $this->assertEquals(1, $countAfter);
        
        // Valid token should remain, expired should be gone
        $this->assertTrue(JwtBlacklist::isBlacklisted($jti2));
        $this->assertFalse(JwtBlacklist::isBlacklisted($jti1));
    }

    public function testGetUserBlacklistedTokens(): void
    {
        $userId1 = 1;
        $userId2 = 2;
        
        // Create another user for testing
        $this->pdo->exec("
            INSERT INTO users (username, email, password, first_name, last_name) 
            VALUES ('testuser2', 'test2@example.com', 'password', 'Test2', 'User2')
        ");
        
        // Create blacklisted tokens for different users
        JwtBlacklist::blacklistToken('token1', $userId1, time() + 3600, 'Reason 1');
        JwtBlacklist::blacklistToken('token2', $userId1, time() + 3600, 'Reason 2');
        JwtBlacklist::blacklistToken('token3', $userId2, time() + 3600, 'Reason 3');
        
        // Get blacklisted tokens for user 1
        $user1Tokens = $this->jwtService->getUserBlacklistedTokens($userId1);
        $this->assertCount(2, $user1Tokens);
        
        // Get blacklisted tokens for user 2
        $user2Tokens = $this->jwtService->getUserBlacklistedTokens($userId2);
        $this->assertCount(1, $user2Tokens);
    }
}