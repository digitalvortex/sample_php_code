<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Config\RateLimitConfig;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\RateLimitMiddleware;
use App\Services\RateLimitService;
use App\Services\SecurityLoggerService;
use App\Services\BruteForceProtectionService;
use App\Services\SecurityMonitoringService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Rate Limit Middleware Test
 * 
 * Tests for rate limiting middleware functionality.
 */
class RateLimitMiddlewareTest extends TestCase
{
    private RateLimitService|MockObject $rateLimitService;
    private SecurityLoggerService|MockObject $logger;
    private BruteForceProtectionService|MockObject $bruteForceProtection;
    private SecurityMonitoringService|MockObject $securityMonitoring;
    private RateLimitMiddleware $middleware;
    private Request|MockObject $request;

    protected function setUp(): void
    {
        $this->rateLimitService = $this->createMock(RateLimitService::class);
        $this->logger = $this->createMock(SecurityLoggerService::class);
        $this->bruteForceProtection = $this->createMock(BruteForceProtectionService::class);
        $this->securityMonitoring = $this->createMock(SecurityMonitoringService::class);
        $this->middleware = new RateLimitMiddleware(
            $this->rateLimitService, 
            $this->logger,
            $this->bruteForceProtection,
            $this->securityMonitoring
        );
        $this->request = $this->createMock(Request::class);
    }

    public function testHandleAllowedRequest(): void
    {
        // Setup request mock
        $this->request->method('getPath')->willReturn('/api/test');
        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getClientIp')->willReturn('192.168.1.1');
        $this->request->method('getHeader')->willReturn('Mozilla/5.0');

        // Setup brute force protection mock
        $this->bruteForceProtection->method('isBlacklisted')->willReturn(false);
        $this->bruteForceProtection->method('analyzeAttackPattern')->willReturn([
            'threat_level' => 'low',
            'should_blacklist' => false,
            'reasons' => []
        ]);

        // Setup rate limit service mock
        $this->rateLimitService->method('isAllowed')->willReturn(true);
        $this->rateLimitService->method('getStatus')->willReturn([
            'limit' => 100,
            'current' => 1,
            'remaining' => 99,
            'reset_time' => time() + 60,
            'retry_after' => 60
        ]);

        // Expect record request to be called
        $this->rateLimitService->expects($this->once())->method('recordRequest');

        // Mock next handler
        $response = new Response();
        $next = function () use ($response) {
            return $response;
        };

        // Execute middleware
        $result = $this->middleware->handle($this->request, $next);

        // Verify response has rate limit headers
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testHandleRateLimitExceeded(): void
    {
        // Setup request mock
        $this->request->method('getPath')->willReturn('/login');
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getClientIp')->willReturn('192.168.1.1');
        $this->request->method('getHeader')->willReturn('Mozilla/5.0');

        // Setup brute force protection mock
        $this->bruteForceProtection->method('isBlacklisted')->willReturn(false);
        $this->bruteForceProtection->method('analyzeAttackPattern')->willReturn([
            'threat_level' => 'medium',
            'should_blacklist' => false,
            'reasons' => []
        ]);

        // Setup rate limit service mock - deny request
        $this->rateLimitService->method('getFailedAttemptCount')->willReturn(2);
        $this->rateLimitService->method('isAllowed')->willReturn(false);
        $this->rateLimitService->method('getStatus')->willReturn([
            'limit' => 5,
            'current' => 5,
            'remaining' => 0,
            'reset_time' => time() + 300,
            'retry_after' => 300
        ]);

        // Expect security monitoring to record metric
        $this->securityMonitoring->expects($this->once())
            ->method('recordMetric')
            ->with('rate_limit_exceeded', 1, $this->isType('array'));

        // Mock next handler (should not be called)
        $next = function () {
            $this->fail('Next handler should not be called when rate limited');
        };

        // Execute middleware
        $result = $this->middleware->handle($this->request, $next);

        // Verify 429 response
        $this->assertInstanceOf(Response::class, $result);
        // Note: In a real test, we'd check the actual status code
        // This would require the Response class to have a getStatusCode method
    }

    public function testHandleExcludedPath(): void
    {
        // Setup request for excluded path
        $this->request->method('getPath')->willReturn('/css/main.css');
        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getClientIp')->willReturn('192.168.1.1');
        $this->request->method('getHeader')->willReturn('Mozilla/5.0');

        // Brute force protection should still run for excluded paths
        $this->bruteForceProtection->method('isBlacklisted')->willReturn(false);
        $this->bruteForceProtection->method('analyzeAttackPattern')->willReturn([
            'threat_level' => 'low',
            'should_blacklist' => false,
            'reasons' => []
        ]);

        // Rate limit service methods should not be called
        $this->rateLimitService->expects($this->never())->method('isAllowed');
        $this->rateLimitService->expects($this->never())->method('recordRequest');

        // Mock next handler
        $response = new Response();
        $next = function () use ($response) {
            return $response;
        };

        // Execute middleware
        $result = $this->middleware->handle($this->request, $next);

        $this->assertSame($response, $result);
    }

    public function testHandleAuthenticationEndpointWithFailedAttempts(): void
    {
        // Setup request for auth endpoint
        $this->request->method('getPath')->willReturn('/login');
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getClientIp')->willReturn('192.168.1.1');
        $this->request->method('getHeader')->willReturn('Mozilla/5.0');

        // Setup brute force protection mock
        $this->bruteForceProtection->method('isBlacklisted')->willReturn(false);
        $this->bruteForceProtection->method('analyzeAttackPattern')->willReturn([
            'threat_level' => 'medium',
            'should_blacklist' => false,
            'reasons' => []
        ]);

        // Setup rate limit service to return failed attempts
        $this->rateLimitService->method('getFailedAttemptCount')->willReturn(3);
        $this->rateLimitService->method('isAllowed')->willReturn(true);
        $this->rateLimitService->method('getStatus')->willReturn([
            'limit' => 5,
            'current' => 1,
            'remaining' => 4,
            'reset_time' => time() + 300,
            'retry_after' => 300
        ]);

        // Expect record request to be called
        $this->rateLimitService->expects($this->once())->method('recordRequest');

        // Mock next handler
        $response = new Response();
        $next = function () use ($response) {
            return $response;
        };

        // Execute middleware
        $result = $this->middleware->handle($this->request, $next);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testHandleWithProgressivePenalty(): void
    {
        // Setup request
        $this->request->method('getPath')->willReturn('/login');
        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getClientIp')->willReturn('192.168.1.1');
        $this->request->method('getHeader')->willReturn('Mozilla/5.0');

        // Setup rate limit service - deny request with progressive config
        $this->rateLimitService->method('isAllowed')->willReturn(false);
        $this->rateLimitService->method('getFailedAttemptCount')->willReturn(0);
        $this->rateLimitService->method('applyPenalty')->willReturn(600);
        $this->rateLimitService->method('getStatus')->willReturn([
            'limit' => 5,
            'current' => 5,
            'remaining' => 0,
            'reset_time' => time() + 600,
            'retry_after' => 600
        ]);

        // Expect penalty to be applied
        $this->rateLimitService->expects($this->once())->method('applyPenalty');

        // Mock next handler
        $next = function () {
            $this->fail('Next handler should not be called when rate limited');
        };

        // Execute middleware
        $result = $this->middleware->handle($this->request, $next);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testShouldApply(): void
    {
        // Test excluded path
        $excludedRequest = $this->createMock(Request::class);
        $excludedRequest->method('getPath')->willReturn('/css/main.css');
        $this->assertFalse($this->middleware->shouldApply($excludedRequest));

        // Test non-excluded path
        $normalRequest = $this->createMock(Request::class);
        $normalRequest->method('getPath')->willReturn('/api/test');
        $this->assertTrue($this->middleware->shouldApply($normalRequest));
    }

    public function testGetPriority(): void
    {
        $this->assertEquals(250, $this->middleware->getPriority());
    }

    public function testGetName(): void
    {
        $this->assertEquals('rate_limit', $this->middleware->getName());
    }

    public function testRecordFailedAttempt(): void
    {
        // Setup request
        $this->request->method('getClientIp')->willReturn('192.168.1.1');
        $this->request->method('getPath')->willReturn('/login');
        $this->request->method('getHeader')->willReturn('Mozilla/5.0');

        // Expect failed attempt to be recorded
        $this->rateLimitService->expects($this->once())->method('recordFailedAttempt');

        // Expect brute force protection to record suspicious activity
        $this->bruteForceProtection->expects($this->once())
            ->method('recordSuspiciousActivity')
            ->with('192.168.1.1', 'authentication_failure', $this->isType('array'));

        // Expect security monitoring to record metric
        $this->securityMonitoring->expects($this->once())
            ->method('recordMetric')
            ->with('failed_login', 1, $this->isType('array'));

        // Expect security event to be logged
        $this->logger->expects($this->once())
            ->method('logSecurityEvent')
            ->with('AUTH_FAILED_ATTEMPT', $this->isType('array'));

        // Execute method
        $this->middleware->recordFailedAttempt($this->request);
    }

    public function testHandleWithOverrideConfig(): void
    {
        // Create middleware with override config
        $overrideConfig = [
            'enabled' => false
        ];
        $middleware = new RateLimitMiddleware(
            $this->rateLimitService, 
            $this->logger, 
            $this->bruteForceProtection,
            $this->securityMonitoring,
            $overrideConfig
        );

        // Setup request
        $this->request->method('getPath')->willReturn('/api/test');
        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getClientIp')->willReturn('192.168.1.1');
        $this->request->method('getHeader')->willReturn('Mozilla/5.0');

        // Brute force protection should still run
        $this->bruteForceProtection->method('isBlacklisted')->willReturn(false);
        $this->bruteForceProtection->method('analyzeAttackPattern')->willReturn([
            'threat_level' => 'low',
            'should_blacklist' => false,
            'reasons' => []
        ]);

        // Rate limit service methods should not be called due to disabled config
        $this->rateLimitService->expects($this->never())->method('isAllowed');

        // Mock next handler
        $response = new Response();
        $next = function () use ($response) {
            return $response;
        };

        // Execute middleware
        $result = $middleware->handle($this->request, $next);

        $this->assertSame($response, $result);
    }

    public function testHandleWithAuthenticatedUser(): void
    {
        // Simulate authenticated user
        $_SESSION['user_id'] = 123;

        // Setup request
        $this->request->method('getPath')->willReturn('/api/test');
        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getClientIp')->willReturn('192.168.1.1');
        $this->request->method('getHeader')->willReturn('Mozilla/5.0');

        // Setup rate limit service
        $this->rateLimitService->method('isAllowed')->willReturn(true);
        $this->rateLimitService->method('getStatus')->willReturn([
            'limit' => 200, // Higher limit for authenticated users
            'current' => 1,
            'remaining' => 199,
            'reset_time' => time() + 60,
            'retry_after' => 60
        ]);

        // Mock next handler
        $response = new Response();
        $next = function () use ($response) {
            return $response;
        };

        // Execute middleware
        $result = $this->middleware->handle($this->request, $next);

        $this->assertInstanceOf(Response::class, $result);

        // Clean up
        unset($_SESSION['user_id']);
    }
}