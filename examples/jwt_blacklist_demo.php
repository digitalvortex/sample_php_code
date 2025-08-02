<?php
declare(strict_types=1);

/**
 * JWT Blacklist Demonstration
 * 
 * This script demonstrates the JWT blacklist functionality
 * including token creation, validation, revocation, and cleanup.
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Services\JwtService;
use App\Models\JwtBlacklist;
use App\Models\User;

echo "=== JWT Blacklist Demonstration ===\n\n";

try {
    // Get services from container
    $jwtService = $container->get(JwtService::class);
    
    // Create a test user first
    echo "1. Creating a test user...\n";
    $user = User::create([
        'username' => 'testuser_jwt',
        'email' => 'jwt_test@example.com',
        'password' => password_hash('password123', PASSWORD_ARGON2ID),
        'first_name' => 'JWT',
        'last_name' => 'Test',
        'level_id' => 1
    ]);
    $userId = $user->getId();
    echo "   ✓ Test user created with ID: $userId\n";
    
    echo "\n2. Creating a JWT token for user ID $userId...\n";
    $permissions = ['read_posts', 'write_posts'];
    $token = $jwtService->createToken($userId, $permissions);
    
    echo "   Token created: " . substr($token, 0, 50) . "...\n";
    
    // Validate the token
    echo "\n3. Validating the token...\n";
    $payload = $jwtService->validateToken($token);
    
    if ($payload) {
        echo "   ✓ Token is valid\n";
        echo "   User ID: " . $payload['user_id'] . "\n";
        echo "   JTI: " . $payload['jti'] . "\n";
        echo "   Expires: " . date('Y-m-d H:i:s', $payload['exp']) . "\n";
        $jti = $payload['jti'];
        $exp = $payload['exp'];
    } else {
        echo "   ✗ Token validation failed\n";
        
        // Debug token parts
        $parts = explode('.', $token);
        echo "   Token parts count: " . count($parts) . "\n";
        
        if (count($parts) === 3) {
            $header = json_decode(base64_decode(str_pad(strtr($parts[0], '-_', '+/'), strlen($parts[0]) % 4, '=', STR_PAD_RIGHT)), true);
            $payload_debug = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT)), true);
            
            echo "   Header: " . json_encode($header) . "\n";
            echo "   Payload preview: " . json_encode(array_intersect_key($payload_debug ?: [], array_flip(['iss', 'user_id', 'exp']))) . "\n";
        }
        
        exit(1);
    }
    
    // Check if token is blacklisted (should be false)
    echo "\n4. Checking if token is blacklisted...\n";
    $isBlacklisted = $jwtService->isTokenBlacklisted($jti);
    echo "   Token blacklisted: " . ($isBlacklisted ? "Yes" : "No") . "\n";
    
    // Revoke the token
    echo "\n5. Revoking the token...\n";
    $revoked = $jwtService->revokeToken($token, "User logout");
    
    if ($revoked) {
        echo "   ✓ Token successfully revoked\n";
    } else {
        echo "   ✗ Failed to revoke token\n";
    }
    
    // Try to validate the revoked token
    echo "\n6. Trying to validate the revoked token...\n";
    $payload = $jwtService->validateToken($token);
    
    if ($payload) {
        echo "   ✗ Token is still valid (this shouldn't happen!)\n";
    } else {
        echo "   ✓ Token is now invalid (correctly blacklisted)\n";
    }
    
    // Check if token is now blacklisted
    echo "\n7. Verifying token is blacklisted...\n";
    $isBlacklisted = $jwtService->isTokenBlacklisted($jti);
    echo "   Token blacklisted: " . ($isBlacklisted ? "Yes" : "No") . "\n";
    
    // Demonstrate token refresh with blacklisting
    echo "\n8. Creating a new token for refresh demonstration...\n";
    $newToken = $jwtService->createToken($userId, $permissions);
    $newPayload = $jwtService->validateToken($newToken);
    $newJti = $newPayload['jti'];
    
    echo "   New token JTI: " . $newJti . "\n";
    
    echo "\n9. Refreshing the new token...\n";
    $refreshedToken = $jwtService->refreshToken($newToken);
    
    if ($refreshedToken) {
        echo "   ✓ Token refreshed successfully\n";
        $refreshedPayload = $jwtService->validateToken($refreshedToken);
        echo "   New token JTI: " . $refreshedPayload['jti'] . "\n";
        
        // Check if old token is blacklisted
        echo "   Old token blacklisted: " . ($jwtService->isTokenBlacklisted($newJti) ? "Yes" : "No") . "\n";
        echo "   New token valid: " . ($jwtService->validateToken($refreshedToken) ? "Yes" : "No") . "\n";
    } else {
        echo "   ✗ Failed to refresh token\n";
    }
    
    // Show current blacklist count
    echo "\n10. Current blacklist statistics...\n";
    $blacklistCount = JwtBlacklist::count();
    echo "   Total blacklisted tokens: " . $blacklistCount . "\n";
    
    // Cleanup expired tokens
    echo "\n11. Cleaning up expired tokens...\n";
    $cleanedCount = $jwtService->cleanupExpiredTokens();
    echo "    Cleaned up " . $cleanedCount . " expired tokens\n";
    
    $remainingCount = JwtBlacklist::count();
    echo "    Remaining blacklisted tokens: " . $remainingCount . "\n";
    
    echo "\n=== JWT Blacklist Demonstration Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}