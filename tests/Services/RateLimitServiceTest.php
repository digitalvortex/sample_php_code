<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Services\RateLimitService;
use PHPUnit\Framework\TestCase;

/**
 * Rate Limit Service Test
 * 
 * Comprehensive tests for rate limiting functionality.
 */
class RateLimitServiceTest extends TestCase
{
    private RateLimitService $service;
    private string $testStorageDir;

    protected function setUp(): void
    {
        $this->testStorageDir = sys_get_temp_dir() . '/rate_limit_test_' . uniqid();
        $this->service = new RateLimitService($this->testStorageDir);
    }

    protected function tearDown(): void
    {
        // Clean up test storage directory
        if (is_dir($this->testStorageDir)) {
            $files = glob($this->testStorageDir . '/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->testStorageDir);
        }
    }

    public function testIsAllowedWithinLimit(): void
    {
        $clientId = 'test_client';
        $maxRequests = 5;
        $windowSeconds = 60;

        // Should be allowed initially
        $this->assertTrue($this->service->isAllowed($clientId, $maxRequests, $windowSeconds));
    }

    public function testRecordRequestAndGetStatus(): void
    {
        $clientId = 'test_client';
        $maxRequests = 5;
        $windowSeconds = 60;

        // Record a request
        $this->service->recordRequest($clientId, $maxRequests, $windowSeconds);

        // Get status
        $status = $this->service->getStatus($clientId, $maxRequests, $windowSeconds);

        $this->assertEquals($maxRequests, $status['limit']);
        $this->assertEquals(1, $status['current']);
        $this->assertEquals(4, $status['remaining']);
        $this->assertGreaterThan(time(), $status['reset_time']);
    }

    public function testRateLimitExceeded(): void
    {
        $clientId = 'test_client';
        $maxRequests = 3;
        $windowSeconds = 60;

        // Record maximum allowed requests
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->assertTrue($this->service->isAllowed($clientId, $maxRequests, $windowSeconds));
            $this->service->recordRequest($clientId, $maxRequests, $windowSeconds);
        }

        // Next request should be denied
        $this->assertFalse($this->service->isAllowed($clientId, $maxRequests, $windowSeconds));
    }

    public function testFixedWindowAlgorithm(): void
    {
        $clientId = 'test_client_fixed';
        $maxRequests = 2;
        $windowSeconds = 60;
        $algorithm = 'fixed_window';

        // Use all requests in current window
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->assertTrue($this->service->isAllowed($clientId, $maxRequests, $windowSeconds, $algorithm));
            $this->service->recordRequest($clientId, $maxRequests, $windowSeconds, $algorithm);
        }

        // Should be denied
        $this->assertFalse($this->service->isAllowed($clientId, $maxRequests, $windowSeconds, $algorithm));
    }

    public function testSlidingWindowAlgorithm(): void
    {
        $clientId = 'test_client_sliding';
        $maxRequests = 2;
        $windowSeconds = 60;
        $algorithm = 'sliding_window';

        // Use all requests
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->assertTrue($this->service->isAllowed($clientId, $maxRequests, $windowSeconds, $algorithm));
            $this->service->recordRequest($clientId, $maxRequests, $windowSeconds, $algorithm);
        }

        // Should be denied
        $this->assertFalse($this->service->isAllowed($clientId, $maxRequests, $windowSeconds, $algorithm));
    }

    public function testTokenBucketAlgorithm(): void
    {
        $clientId = 'test_client_bucket';
        $maxRequests = 3;
        $windowSeconds = 60;
        $algorithm = 'token_bucket';

        // Use all tokens
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->assertTrue($this->service->isAllowed($clientId, $maxRequests, $windowSeconds, $algorithm));
            $this->service->recordRequest($clientId, $maxRequests, $windowSeconds, $algorithm);
        }

        // Should be denied when no tokens left
        $this->assertFalse($this->service->isAllowed($clientId, $maxRequests, $windowSeconds, $algorithm));
    }

    public function testProgressivePenalty(): void
    {
        $clientId = 'test_penalty_client';
        $baseWindowSeconds = 60;

        // Apply penalty
        $penaltySeconds = $this->service->applyPenalty($clientId, $baseWindowSeconds);

        // Penalty should be greater than base window
        $this->assertGreaterThanOrEqual($baseWindowSeconds, $penaltySeconds);

        // Apply another penalty
        $secondPenalty = $this->service->applyPenalty($clientId, $baseWindowSeconds);

        // Second penalty should be larger
        $this->assertGreaterThanOrEqual($penaltySeconds, $secondPenalty);
    }

    public function testFailedAttemptRecording(): void
    {
        $clientId = 'test_attempt_client';

        // Record failed attempts
        $this->service->recordFailedAttempt($clientId);
        $this->service->recordFailedAttempt($clientId);

        // Get count
        $count = $this->service->getFailedAttemptCount($clientId);

        $this->assertEquals(2, $count);
    }

    public function testClearLimits(): void
    {
        $clientId = 'test_clear_client';
        $maxRequests = 5;
        $windowSeconds = 60;

        // Record some requests
        $this->service->recordRequest($clientId, $maxRequests, $windowSeconds);
        $this->service->recordFailedAttempt($clientId);

        // Verify data exists
        $status = $this->service->getStatus($clientId, $maxRequests, $windowSeconds);
        $this->assertEquals(1, $status['current']);
        $this->assertEquals(1, $this->service->getFailedAttemptCount($clientId));

        // Clear limits
        $this->service->clearLimits($clientId);

        // Verify data is cleared
        $status = $this->service->getStatus($clientId, $maxRequests, $windowSeconds);
        $this->assertEquals(0, $status['current']);
        $this->assertEquals(0, $this->service->getFailedAttemptCount($clientId));
    }

    public function testCleanup(): void
    {
        $clientId = 'test_cleanup_client';
        $maxRequests = 5;
        $windowSeconds = 60;

        // Record a request to create files
        $this->service->recordRequest($clientId, $maxRequests, $windowSeconds);

        // Verify file exists
        $files = glob($this->testStorageDir . '/*.json');
        $this->assertGreaterThan(0, count($files));

        // Run cleanup (won't delete recent files)
        $cleaned = $this->service->cleanup();
        $this->assertEquals(0, $cleaned);

        // Manually set file modification time to past
        foreach ($files as $file) {
            touch($file, time() - 86401); // 24+ hours ago
        }

        // Run cleanup again
        $cleaned = $this->service->cleanup();
        $this->assertGreaterThan(0, $cleaned);
    }

    public function testInvalidAlgorithm(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown algorithm: invalid');

        $this->service->isAllowed('test', 5, 60, 'invalid');
    }

    public function testMultipleClients(): void
    {
        $client1 = 'client_1';
        $client2 = 'client_2';
        $maxRequests = 2;
        $windowSeconds = 60;

        // Client 1 uses all requests
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->assertTrue($this->service->isAllowed($client1, $maxRequests, $windowSeconds));
            $this->service->recordRequest($client1, $maxRequests, $windowSeconds);
        }

        // Client 1 should be denied
        $this->assertFalse($this->service->isAllowed($client1, $maxRequests, $windowSeconds));

        // Client 2 should still be allowed
        $this->assertTrue($this->service->isAllowed($client2, $maxRequests, $windowSeconds));
    }

    public function testStatusResetTime(): void
    {
        $clientId = 'test_reset_client';
        $maxRequests = 5;
        $windowSeconds = 60;

        $status = $this->service->getStatus($clientId, $maxRequests, $windowSeconds);

        // Reset time should be in the future
        $this->assertGreaterThan(time(), $status['reset_time']);
        $this->assertLessThanOrEqual(time() + $windowSeconds, $status['reset_time']);

        // Retry after should be reasonable
        $this->assertGreaterThanOrEqual(0, $status['retry_after']);
        $this->assertLessThanOrEqual($windowSeconds, $status['retry_after']);
    }
}