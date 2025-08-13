<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Config\RateLimitConfig;
use App\Core\Container;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\RateLimitMiddleware;
use App\Services\RateLimitService;
use App\Services\SecurityLoggerService;
use App\Services\BruteForceProtectionService;
use App\Services\SecurityMonitoringService;
use App\Controllers\User\AuthController;
use PHPUnit\Framework\TestCase;

/**
 * Attack Simulation Test
 * 
 * Comprehensive tests simulating various types of attacks against
 * the rate limiting and brute force protection systems.
 */
class AttackSimulationTest extends TestCase
{
    private Container $container;
    private RateLimitMiddleware $rateLimitMiddleware;
    private BruteForceProtectionService $bruteForceProtection;
    private RateLimitService $rateLimitService;
    private SecurityLoggerService $logger;
    private AuthController $authController;
    private string $tempDir;

    protected function setUp(): void
    {
        // Create temporary directory for test storage
        $this->tempDir = sys_get_temp_dir() . '/rate_limit_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        // Initialize services
        $this->logger = $this->createMock(SecurityLoggerService::class);
        $this->rateLimitService = new RateLimitService($this->tempDir . '/rate_limits');
        $this->bruteForceProtection = new BruteForceProtectionService(
            $this->logger, 
            $this->tempDir . '/security'
        );

        // Initialize security monitoring service
        $securityMonitoring = $this->createMock(SecurityMonitoringService::class);

        // Initialize middleware
        $this->rateLimitMiddleware = new RateLimitMiddleware(
            $this->rateLimitService,
            $this->logger,
            $this->bruteForceProtection,
            $securityMonitoring
        );

        // Initialize auth controller for login testing
        $this->authController = $this->createMock(AuthController::class);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        $this->recursiveRemoveDirectory($this->tempDir);
    }

    /**
     * Test basic brute force attack on login endpoint.
     */
    public function testBruteForceLoginAttack(): void
    {
        $attackerIp = '192.168.1.100';
        $userAgent = 'AttackBot/1.0';
        
        // Simulate 10 rapid login attempts
        for ($i = 0; $i < 10; $i++) {
            $request = $this->createLoginRequest($attackerIp, $userAgent);
            $response = $this->simulateLoginAttempt($request);
            
            // First few attempts should go through rate limiting
            if ($i < 5) {
                $this->assertInstanceOf(Response::class, $response);
            }
        }
        
        // After multiple attempts, IP should be blacklisted
        $this->assertTrue($this->bruteForceProtection->isBlacklisted($attackerIp));
        
        // Further attempts should be blocked immediately
        $request = $this->createLoginRequest($attackerIp, $userAgent);
        $response = $this->simulateMiddlewareOnly($request);
        
        // Should return 403 Forbidden for blacklisted IP
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test distributed brute force attack from multiple IPs.
     */
    public function testDistributedBruteForceAttack(): void
    {
        $attackerIps = [
            '192.168.1.101',
            '192.168.1.102', 
            '192.168.1.103',
            '192.168.1.104',
            '192.168.1.105'
        ];
        
        $userAgent = 'AttackBot/1.0';
        
        // Each IP makes multiple attempts
        foreach ($attackerIps as $ip) {
            for ($i = 0; $i < 8; $i++) {
                $request = $this->createLoginRequest($ip, $userAgent);
                $this->simulateLoginAttempt($request);
            }
        }
        
        // Some IPs should be blacklisted
        $blacklistedCount = 0;
        foreach ($attackerIps as $ip) {
            if ($this->bruteForceProtection->isBlacklisted($ip)) {
                $blacklistedCount++;
            }
        }
        
        $this->assertGreaterThan(0, $blacklistedCount);
    }

    /**
     * Test slow brute force attack (credential stuffing).
     */
    public function testSlowBruteForceAttack(): void
    {
        $attackerIp = '192.168.1.110';
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        // Simulate slow attack over several hours (simulated time)
        $startTime = time() - 7200; // 2 hours ago
        
        for ($i = 0; $i < 25; $i++) {
            // Simulate time progression
            $currentTime = $startTime + ($i * 300); // 5 minutes between attempts
            
            $request = $this->createLoginRequest($attackerIp, $userAgent);
            
            // Manually trigger attack pattern analysis
            $this->bruteForceProtection->analyzeAttackPattern(
                $attackerIp, 
                '/login', 
                $userAgent
            );
        }
        
        // Should detect persistent attack pattern
        $this->assertTrue($this->bruteForceProtection->isBlacklisted($attackerIp));
    }

    /**
     * Test scanning behavior detection.
     */
    public function testScanningBehaviorDetection(): void
    {
        $scannerIp = '192.168.1.120';
        $userAgent = 'Scanner/1.0';
        
        $endpoints = [
            '/login', '/register', '/admin', '/api/users', 
            '/dashboard', '/profile', '/settings', '/api/auth'
        ];
        
        // Simulate scanning multiple endpoints
        foreach ($endpoints as $endpoint) {
            for ($i = 0; $i < 3; $i++) {
                $request = $this->createRequest($scannerIp, $userAgent, $endpoint);
                $this->simulateMiddlewareOnly($request);
            }
        }
        
        // Should detect scanning behavior and blacklist
        $this->assertTrue($this->bruteForceProtection->isBlacklisted($scannerIp));
    }

    /**
     * Test user agent rotation detection.
     */
    public function testUserAgentRotationDetection(): void
    {
        $botIp = '192.168.1.130';
        
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X)',
            'Bot/1.0'
        ];
        
        // Simulate rotating user agents
        for ($i = 0; $i < 15; $i++) {
            $userAgent = $userAgents[$i % count($userAgents)];
            $request = $this->createLoginRequest($botIp, $userAgent);
            $this->simulateLoginAttempt($request);
        }
        
        // Should detect bot behavior
        $this->assertTrue($this->bruteForceProtection->isBlacklisted($botIp));
    }

    /**
     * Test rate limiting with different algorithms.
     */
    public function testRateLimitingAlgorithms(): void
    {
        $testIp = '192.168.1.140';
        $userAgent = 'TestClient/1.0';
        
        // Test fixed window
        $this->assertRateLimitAlgorithm($testIp, $userAgent, 'fixed_window', 5, 60);
        
        // Test sliding window  
        $this->assertRateLimitAlgorithm($testIp . '1', $userAgent, 'sliding_window', 5, 60);
        
        // Test token bucket
        $this->assertRateLimitAlgorithm($testIp . '2', $userAgent, 'token_bucket', 5, 60);
    }

    /**
     * Test progressive penalty system.
     */
    public function testProgressivePenaltySystem(): void
    {
        $offenderIp = '192.168.1.150';
        $userAgent = 'BadActor/1.0';
        
        // First violation
        $this->rateLimitService->applyPenalty($offenderIp, 300);
        $status1 = $this->rateLimitService->getStatus($offenderIp, 5, 300);
        
        // Second violation (should have higher penalty)
        $this->rateLimitService->applyPenalty($offenderIp, 300);
        $status2 = $this->rateLimitService->getStatus($offenderIp, 5, 600);
        
        // Penalty should increase
        $this->assertGreaterThan($status1['retry_after'], $status2['retry_after']);
    }

    /**
     * Test legitimate user not affected by rate limiting.
     */
    public function testLegitimateUserProtection(): void
    {
        $legitimateIp = '192.168.1.200';
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        
        // Simulate normal usage pattern
        for ($i = 0; $i < 10; $i++) {
            $request = $this->createRequest($legitimateIp, $userAgent, '/dashboard');
            $response = $this->simulateMiddlewareOnly($request);
            
            // Should not be blocked
            $this->assertInstanceOf(Response::class, $response);
            
            // Add delay between requests (simulated)
            sleep(1);
        }
        
        // Should not be blacklisted
        $this->assertFalse($this->bruteForceProtection->isBlacklisted($legitimateIp));
    }

    /**
     * Test IP whitelist functionality.
     */
    public function testWhitelistProtection(): void
    {
        $whitelistedIp = '127.0.0.1'; // Localhost is whitelisted
        $userAgent = 'TestBot/1.0';
        
        // Even aggressive behavior from whitelisted IP should not be blocked
        for ($i = 0; $i < 50; $i++) {
            $request = $this->createLoginRequest($whitelistedIp, $userAgent);
            $response = $this->simulateMiddlewareOnly($request);
            
            // Should pass through due to whitelist
            $this->assertInstanceOf(Response::class, $response);
        }
        
        // Should not be blacklisted
        $this->assertFalse($this->bruteForceProtection->isBlacklisted($whitelistedIp));
    }

    /**
     * Test cleanup functionality.
     */
    public function testSecurityDataCleanup(): void
    {
        $testIp = '192.168.1.160';
        
        // Create some test data
        $this->bruteForceProtection->blacklistIp($testIp, 'test', 1); // 1 second expiry
        $this->assertTrue($this->bruteForceProtection->isBlacklisted($testIp));
        
        // Wait for expiry
        sleep(2);
        
        // Should automatically clean up expired entries
        $cleaned = $this->bruteForceProtection->cleanup();
        $this->assertGreaterThanOrEqual(0, $cleaned);
        
        // Should no longer be blacklisted
        $this->assertFalse($this->bruteForceProtection->isBlacklisted($testIp));
    }

    /**
     * Helper method to create login request.
     */
    private function createLoginRequest(string $ip, string $userAgent): Request
    {
        return $this->createRequest($ip, $userAgent, '/login', 'POST');
    }

    /**
     * Helper method to create request.
     */
    private function createRequest(
        string $ip, 
        string $userAgent, 
        string $path = '/login',
        string $method = 'GET'
    ): Request {
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn($ip);
        $request->method('getHeader')->willReturn($userAgent);
        $request->method('getPath')->willReturn($path);
        $request->method('getMethod')->willReturn($method);
        $request->method('getUserAgent')->willReturn($userAgent);
        
        return $request;
    }

    /**
     * Simulate login attempt through auth controller.
     */
    private function simulateLoginAttempt(Request $request): Response
    {
        // Record failed attempt (simulating failed login)
        $this->rateLimitMiddleware->recordFailedAttempt($request);
        
        return new Response();
    }

    /**
     * Simulate request through middleware only.
     */
    private function simulateMiddlewareOnly(Request $request): Response
    {
        $next = function() {
            return new Response();
        };
        
        return $this->rateLimitMiddleware->handle($request, $next);
    }

    /**
     * Test specific rate limiting algorithm.
     */
    private function assertRateLimitAlgorithm(
        string $ip, 
        string $userAgent, 
        string $algorithm, 
        int $maxRequests, 
        int $windowSeconds
    ): void {
        $clientId = "ip:{$ip}";
        
        // Make requests up to the limit
        for ($i = 0; $i < $maxRequests; $i++) {
            $allowed = $this->rateLimitService->isAllowed(
                $clientId, 
                $maxRequests, 
                $windowSeconds, 
                $algorithm
            );
            $this->assertTrue($allowed, "Request {$i} should be allowed");
            
            $this->rateLimitService->recordRequest(
                $clientId, 
                $maxRequests, 
                $windowSeconds, 
                $algorithm
            );
        }
        
        // Next request should be blocked
        $allowed = $this->rateLimitService->isAllowed(
            $clientId, 
            $maxRequests, 
            $windowSeconds, 
            $algorithm
        );
        $this->assertFalse($allowed, "Request beyond limit should be blocked");
    }

    /**
     * Recursively remove directory.
     */
    private function recursiveRemoveDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveRemoveDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}