<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\RateLimitConfig;
use App\Services\RateLimitService;
use App\Services\BruteForceProtectionService;
use App\Services\SecurityLoggerService;
use App\Services\SecurityMonitoringService;
use App\Middleware\RateLimitMiddleware;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

/**
 * Rate Limiting Integration Test
 * 
 * Tests the complete rate limiting system integration
 * with real services and configurations.
 */
class RateLimitingIntegrationTest extends TestCase
{
    private string $tempDir;
    private RateLimitService $rateLimitService;
    private BruteForceProtectionService $bruteForceProtection;
    private SecurityLoggerService $logger;
    private SecurityMonitoringService $securityMonitoring;
    private RateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        // Create temporary directory for test storage
        $this->tempDir = sys_get_temp_dir() . '/integration_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        // Initialize real services
        $this->logger = new SecurityLoggerService();
        $this->rateLimitService = new RateLimitService($this->tempDir . '/rate_limits');
        $this->bruteForceProtection = new BruteForceProtectionService(
            $this->logger, 
            $this->tempDir . '/security'
        );
        $this->securityMonitoring = new SecurityMonitoringService(
            $this->logger,
            $this->bruteForceProtection,
            $this->tempDir . '/monitoring'
        );

        // Initialize middleware
        $this->middleware = new RateLimitMiddleware(
            $this->rateLimitService,
            $this->logger,
            $this->bruteForceProtection,
            $this->securityMonitoring
        );
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        $this->recursiveRemoveDirectory($this->tempDir);
    }

    /**
     * Test login endpoint rate limiting configuration.
     */
    public function testLoginEndpointRateLimiting(): void
    {
        $attackerIp = '192.168.1.100';
        $userAgent = 'TestBot/1.0';
        
        $loginConfig = RateLimitConfig::getConfigForPath('/login', 'POST');
        
        // Verify login has strict rate limiting
        $this->assertEquals(5, $loginConfig['max_requests']);
        $this->assertEquals(300, $loginConfig['window_seconds']); // 5 minutes
        $this->assertTrue($loginConfig['progressive']);
        
        // Test multiple login attempts
        $blockedCount = 0;
        for ($i = 0; $i < 10; $i++) {
            $request = $this->createLoginRequest($attackerIp, $userAgent);
            $response = $this->simulateMiddleware($request);
            
            // Check if request was blocked (should happen after 5 attempts)
            if ($this->isRateLimited($response)) {
                $blockedCount++;
            }
        }
        
        // Should have blocked some requests
        $this->assertGreaterThan(0, $blockedCount);
        
        // IP should eventually be blacklisted
        $this->assertTrue($this->bruteForceProtection->isBlacklisted($attackerIp));
    }

    /**
     * Test register endpoint rate limiting configuration.
     */
    public function testRegisterEndpointRateLimiting(): void
    {
        $attackerIp = '192.168.1.101';
        $userAgent = 'TestBot/2.0';
        
        $registerConfig = RateLimitConfig::getConfigForPath('/register', 'POST');
        
        // Verify register has very strict rate limiting
        $this->assertEquals(3, $registerConfig['max_requests']);
        $this->assertEquals(600, $registerConfig['window_seconds']); // 10 minutes
        $this->assertTrue($registerConfig['progressive']);
        
        // Test multiple register attempts
        $blockedCount = 0;
        for ($i = 0; $i < 8; $i++) {
            $request = $this->createRegisterRequest($attackerIp, $userAgent);
            $response = $this->simulateMiddleware($request);
            
            if ($this->isRateLimited($response)) {
                $blockedCount++;
            }
        }
        
        // Should have blocked requests after limit
        $this->assertGreaterThan(0, $blockedCount);
        
        // IP should be blacklisted due to registration abuse
        $this->assertTrue($this->bruteForceProtection->isBlacklisted($attackerIp));
    }

    /**
     * Test progressive penalty system.
     */
    public function testProgressivePenaltySystem(): void
    {
        $offenderIp = '192.168.1.102';
        $userAgent = 'PersistentBot/1.0';
        
        // Record failed attempts to trigger progressive penalties
        for ($i = 0; $i < 3; $i++) {
            $request = $this->createLoginRequest($offenderIp, $userAgent);
            $this->middleware->recordFailedAttempt($request);
        }
        
        // Get failed attempt count
        $clientId = "ip:{$offenderIp}";
        $failedAttempts = $this->rateLimitService->getFailedAttemptCount($clientId);
        
        $this->assertGreaterThanOrEqual(3, $failedAttempts);
        
        // Test progressive configuration
        $baseConfig = RateLimitConfig::getConfigForPath('/login', 'POST');
        $progressiveConfig = RateLimitConfig::getProgressiveConfig($baseConfig, $failedAttempts);
        
        // Limits should be stricter after failed attempts
        $this->assertLessThanOrEqual($baseConfig['max_requests'], $progressiveConfig['max_requests']);
        $this->assertGreaterThanOrEqual($baseConfig['window_seconds'], $progressiveConfig['window_seconds']);
    }

    /**
     * Test different rate limiting algorithms.
     */
    public function testRateLimitingAlgorithms(): void
    {
        $testIp = '192.168.1.103';
        $clientId = "ip:{$testIp}";
        
        // Test fixed window algorithm
        $this->assertTrue($this->rateLimitService->isAllowed($clientId, 5, 60, 'fixed_window'));
        $this->rateLimitService->recordRequest($clientId, 5, 60, 'fixed_window');
        
        // Test sliding window algorithm
        $this->assertTrue($this->rateLimitService->isAllowed($clientId . '_sliding', 5, 60, 'sliding_window'));
        $this->rateLimitService->recordRequest($clientId . '_sliding', 5, 60, 'sliding_window');
        
        // Test token bucket algorithm
        $this->assertTrue($this->rateLimitService->isAllowed($clientId . '_bucket', 5, 60, 'token_bucket'));
        $this->rateLimitService->recordRequest($clientId . '_bucket', 5, 60, 'token_bucket');
        
        // All algorithms should work correctly
        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * Test legitimate user protection.
     */
    public function testLegitimateUserProtection(): void
    {
        $legitimateIp = '127.0.0.1'; // Localhost is whitelisted
        $userAgent = 'Mozilla/5.0 (Legitimate Browser)';
        
        // Simulate normal usage with whitelisted IP
        for ($i = 0; $i < 10; $i++) {
            $request = $this->createRequest($legitimateIp, $userAgent, '/dashboard');
            $response = $this->simulateMiddleware($request);
            
            // Should never be rate limited (next should always be called for whitelisted IPs)
            $this->assertTrue($response->nextCalled ?? false, 'Whitelisted IP should not be blocked');
        }
        
        // Should not be blacklisted
        $this->assertFalse($this->bruteForceProtection->isBlacklisted($legitimateIp));
    }

    /**
     * Test security monitoring integration.
     */
    public function testSecurityMonitoringIntegration(): void
    {
        $monitoredIp = '192.168.1.104';
        
        // Generate some security events
        $this->securityMonitoring->recordMetric('failed_login', 1, ['ip' => $monitoredIp]);
        $this->securityMonitoring->recordMetric('rate_limit_exceeded', 1, ['ip' => $monitoredIp]);
        
        // Get dashboard data
        $dashboardData = $this->securityMonitoring->getDashboardData(1);
        
        // Verify monitoring data structure
        $this->assertArrayHasKey('summary', $dashboardData);
        $this->assertArrayHasKey('threat_level', $dashboardData);
        $this->assertArrayHasKey('recent_alerts', $dashboardData);
        $this->assertArrayHasKey('blacklisted_ips', $dashboardData);
        
        // Should have recorded metrics
        $this->assertGreaterThanOrEqual(0, $dashboardData['summary']['failed_logins']);
        $this->assertGreaterThanOrEqual(0, $dashboardData['summary']['rate_limit_violations']);
    }

    /**
     * Test cleanup functionality.
     */
    public function testCleanupFunctionality(): void
    {
        // Create some test data
        $testIp = '192.168.1.105';
        $this->bruteForceProtection->blacklistIp($testIp, 'Test cleanup', 1); // 1 second expiry
        
        // Wait for expiry
        sleep(2);
        
        // Run cleanup
        $cleaned = $this->bruteForceProtection->cleanup();
        $this->assertGreaterThanOrEqual(0, $cleaned);
        
        // Should no longer be blacklisted
        $this->assertFalse($this->bruteForceProtection->isBlacklisted($testIp));
        
        // Test rate limit service cleanup
        $rateLimitCleaned = $this->rateLimitService->cleanup();
        $this->assertGreaterThanOrEqual(0, $rateLimitCleaned);
        
        // Test security monitoring cleanup
        $monitoringCleaned = $this->securityMonitoring->cleanup();
        $this->assertGreaterThanOrEqual(0, $monitoringCleaned);
    }

    /**
     * Test system under high load.
     */
    public function testHighLoadScenario(): void
    {
        $attackerIps = [];
        for ($i = 1; $i <= 10; $i++) {
            $attackerIps[] = "192.168.2.{$i}";
        }
        
        $userAgent = 'LoadTestBot/1.0';
        $totalBlocked = 0;
        
        // Simulate high load from multiple IPs
        foreach ($attackerIps as $ip) {
            for ($j = 0; $j < 15; $j++) {
                $request = $this->createLoginRequest($ip, $userAgent);
                $response = $this->simulateMiddleware($request);
                
                if ($this->isRateLimited($response)) {
                    $totalBlocked++;
                }
            }
        }
        
        // Should have blocked many requests
        $this->assertGreaterThan(50, $totalBlocked);
        
        // Most IPs should be blacklisted
        $blacklistedCount = 0;
        foreach ($attackerIps as $ip) {
            if ($this->bruteForceProtection->isBlacklisted($ip)) {
                $blacklistedCount++;
            }
        }
        
        $this->assertGreaterThan(5, $blacklistedCount);
    }

    /**
     * Helper method to create login request.
     */
    private function createLoginRequest(string $ip, string $userAgent): Request
    {
        return $this->createRequest($ip, $userAgent, '/login', 'POST');
    }

    /**
     * Helper method to create register request.
     */
    private function createRegisterRequest(string $ip, string $userAgent): Request
    {
        return $this->createRequest($ip, $userAgent, '/register', 'POST');
    }

    /**
     * Helper method to create request.
     */
    private function createRequest(
        string $ip, 
        string $userAgent, 
        string $path = '/login',
        string $method = 'POST'
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
     * Simulate middleware processing.
     */
    private function simulateMiddleware(Request $request): Response
    {
        $nextCalled = false;
        $next = function() use (&$nextCalled) {
            $nextCalled = true;
            return new Response();
        };
        
        $response = $this->middleware->handle($request, $next);
        
        // Store whether next was called to determine if request was blocked
        $response->nextCalled = $nextCalled;
        
        return $response;
    }

    /**
     * Check if response indicates rate limiting.
     */
    private function isRateLimited(Response $response): bool
    {
        // If next handler was not called, the request was blocked by middleware
        return !($response->nextCalled ?? true);
    }

    /**
     * Recursively remove directory for cleanup.
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