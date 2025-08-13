<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\BruteForceProtectionService;
use App\Services\SecurityLoggerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Brute Force Protection Service Test
 * 
 * Tests for the brute force protection service functionality.
 */
class BruteForceProtectionServiceTest extends TestCase
{
    private BruteForceProtectionService $service;
    private SecurityLoggerService|MockObject $logger;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/bf_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        
        $this->logger = $this->createMock(SecurityLoggerService::class);
        $this->service = new BruteForceProtectionService($this->logger, $this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->recursiveRemoveDirectory($this->tempDir);
    }

    public function testBlacklistIp(): void
    {
        $ip = '192.168.1.100';
        $reason = 'Brute force attack detected';
        $duration = 3600;

        // Initially not blacklisted
        $this->assertFalse($this->service->isBlacklisted($ip));

        // Expect logger to be called when blacklisting IP
        $this->logger->expects($this->once())
            ->method('logSecurityEvent')
            ->with('IP_BLACKLISTED', $this->isType('array'));

        // Blacklist IP
        $this->service->blacklistIp($ip, $reason, $duration);

        // Should now be blacklisted
        $this->assertTrue($this->service->isBlacklisted($ip));
    }

    public function testBlacklistExpiry(): void
    {
        $ip = '192.168.1.101';
        $reason = 'Test blacklist';
        $duration = 1; // 1 second

        $this->service->blacklistIp($ip, $reason, $duration);
        $this->assertTrue($this->service->isBlacklisted($ip));

        // Wait for expiry
        sleep(2);

        // Should no longer be blacklisted
        $this->assertFalse($this->service->isBlacklisted($ip));
    }

    public function testPermanentBlacklist(): void
    {
        $ip = '192.168.1.102';
        $reason = 'Permanent ban';
        $duration = 0; // Permanent

        $this->service->blacklistIp($ip, $reason, $duration);
        $this->assertTrue($this->service->isBlacklisted($ip));

        // Should remain blacklisted even after cleanup
        $this->service->cleanup();
        $this->assertTrue($this->service->isBlacklisted($ip));
    }

    public function testRemoveFromBlacklist(): void
    {
        $ip = '192.168.1.103';
        $reason = 'Test removal';

        $this->service->blacklistIp($ip, $reason, 3600);
        $this->assertTrue($this->service->isBlacklisted($ip));

        $result = $this->service->removeFromBlacklist($ip);
        $this->assertTrue($result);
        $this->assertFalse($this->service->isBlacklisted($ip));

        // Removing non-existent blacklist should return false
        $result = $this->service->removeFromBlacklist($ip);
        $this->assertFalse($result);
    }

    public function testAttackPatternAnalysis(): void
    {
        $ip = '192.168.1.104';
        $endpoint = '/login';
        $userAgent = 'AttackBot/1.0';

        // Simulate multiple attempts
        for ($i = 0; $i < 15; $i++) {
            $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
        }

        // Should detect attack pattern
        $this->assertArrayHasKey('threat_level', $analysis);
        $this->assertArrayHasKey('should_blacklist', $analysis);
        $this->assertArrayHasKey('reasons', $analysis);
    }

    public function testHighFrequencyAttackDetection(): void
    {
        $ip = '192.168.1.105';
        $endpoint = '/login';
        $userAgent = 'RapidBot/1.0';

        // Simulate rapid attacks (25 attempts)
        for ($i = 0; $i < 25; $i++) {
            $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
        }

        // Should recommend blacklisting for high frequency
        $this->assertEquals('critical', $analysis['threat_level']);
        $this->assertTrue($analysis['should_blacklist']);
        $this->assertContains('High frequency attack detected', $analysis['reasons']);
    }

    public function testScanningBehaviorDetection(): void
    {
        $ip = '192.168.1.106';
        $userAgent = 'Scanner/1.0';

        $endpoints = ['/login', '/register', '/admin', '/api/users', '/dashboard', '/profile'];

        // Simulate scanning multiple endpoints
        foreach ($endpoints as $endpoint) {
            for ($i = 0; $i < 3; $i++) {
                $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
            }
        }

        // Should detect scanning behavior
        $this->assertContains('Scanning behavior detected', $analysis['reasons']);
        $this->assertTrue($analysis['should_blacklist']);
    }

    public function testUserAgentRotationDetection(): void
    {
        $ip = '192.168.1.107';
        $endpoint = '/login';

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
            'Mozilla/5.0 (X11; Linux x86_64)',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1)',
        ];

        // Simulate user agent rotation
        for ($i = 0; $i < 12; $i++) {
            $userAgent = $userAgents[$i % count($userAgents)];
            $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
        }

        // Should detect multiple user agents
        $this->assertContains('Multiple user agents detected', $analysis['reasons']);
    }

    public function testAuthenticationEndpointTargeting(): void
    {
        $ip = '192.168.1.108';
        $userAgent = 'AuthBot/1.0';

        // Target authentication endpoints
        $authEndpoints = ['/login', '/register'];
        
        foreach ($authEndpoints as $endpoint) {
            for ($i = 0; $i < 8; $i++) {
                $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
            }
        }

        // Should detect authentication targeting
        $this->assertContains('Authentication endpoint targeting', $analysis['reasons']);
        $this->assertTrue($analysis['should_blacklist']);
    }

    public function testSuspiciousActivityRecording(): void
    {
        $ip = '192.168.1.109';
        $activity = 'Multiple failed logins';
        $context = ['endpoint' => '/login', 'count' => 5];

        // Expect logger to be called when recording suspicious activity
        $this->logger->expects($this->once())
            ->method('logSecurityEvent')
            ->with('SUSPICIOUS_ACTIVITY', $this->isType('array'));

        $this->service->recordSuspiciousActivity($ip, $activity, $context);
    }

    public function testGetBlacklistedIps(): void
    {
        // Blacklist multiple IPs
        $ips = ['192.168.1.110', '192.168.1.111', '192.168.1.112'];
        
        foreach ($ips as $ip) {
            $this->service->blacklistIp($ip, 'Test blacklist', 3600);
        }

        $blacklisted = $this->service->getBlacklistedIps();
        
        $this->assertCount(3, $blacklisted);
        
        $blacklistedIps = array_column($blacklisted, 'ip');
        foreach ($ips as $ip) {
            $this->assertContains($ip, $blacklistedIps);
        }
    }

    public function testCleanupExpiredEntries(): void
    {
        // Create expired blacklist entry
        $expiredIp = '192.168.1.113';
        $this->service->blacklistIp($expiredIp, 'Expired test', 1);
        
        // Create permanent blacklist entry
        $permanentIp = '192.168.1.114';
        $this->service->blacklistIp($permanentIp, 'Permanent test', 0);
        
        $this->assertTrue($this->service->isBlacklisted($expiredIp));
        $this->assertTrue($this->service->isBlacklisted($permanentIp));
        
        // Wait for expiry
        sleep(2);
        
        // Cleanup
        $cleaned = $this->service->cleanup();
        $this->assertGreaterThanOrEqual(1, $cleaned);
        
        // Expired should be cleaned, permanent should remain
        $this->assertFalse($this->service->isBlacklisted($expiredIp));
        $this->assertTrue($this->service->isBlacklisted($permanentIp));
    }

    public function testPersistentAttackDetection(): void
    {
        $ip = '192.168.1.115';
        $endpoint = '/login';
        $userAgent = 'PersistentBot/1.0';

        // Simulate persistent attack over multiple hours (35 attempts)
        for ($i = 0; $i < 35; $i++) {
            $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
        }

        // Should detect persistent attack
        $this->assertContains('Persistent attack over multiple hours', $analysis['reasons']);
        $this->assertEquals('high', $analysis['threat_level']);
        $this->assertTrue($analysis['should_blacklist']);
    }

    public function testThreatLevelProgression(): void
    {
        $ip = '192.168.1.116';
        $endpoint = '/login';
        $userAgent = 'ProgressiveBot/1.0';

        // Start with low activity
        for ($i = 0; $i < 3; $i++) {
            $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
        }
        $initialLevel = $analysis['threat_level'];

        // Increase activity
        for ($i = 0; $i < 10; $i++) {
            $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
        }
        $mediumLevel = $analysis['threat_level'];

        // High activity
        for ($i = 0; $i < 15; $i++) {
            $analysis = $this->service->analyzeAttackPattern($ip, $endpoint, $userAgent);
        }
        $highLevel = $analysis['threat_level'];

        // Threat level should escalate
        $this->assertEquals('low', $initialLevel);
        $this->assertNotEquals($initialLevel, $mediumLevel);
        $this->assertNotEquals($mediumLevel, $highLevel);
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