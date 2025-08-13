<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\RateLimitConfig;
use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;
use App\Services\RateLimitService;
use App\Services\SecurityLoggerService;
use App\Services\BruteForceProtectionService;
use App\Services\SecurityMonitoringService;

/**
 * Rate Limiting Middleware
 * 
 * Implements comprehensive rate limiting with multiple algorithms,
 * progressive penalties, and contextual configurations.
 * PHP 8.4 compatible with strict typing.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private RateLimitService $rateLimitService;
    private SecurityLoggerService $logger;
    private BruteForceProtectionService $bruteForceProtection;
    private SecurityMonitoringService $securityMonitoring;
    private ?array $overrideConfig;

    public function __construct(
        RateLimitService $rateLimitService,
        SecurityLoggerService $logger,
        BruteForceProtectionService $bruteForceProtection,
        SecurityMonitoringService $securityMonitoring,
        ?array $overrideConfig = null
    ) {
        $this->rateLimitService = $rateLimitService;
        $this->logger = $logger;
        $this->bruteForceProtection = $bruteForceProtection;
        $this->securityMonitoring = $securityMonitoring;
        $this->overrideConfig = $overrideConfig;
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $ip = $request->getClientIp();
        $userAgent = $request->getHeader('User-Agent') ?? '';

        // Check if IP is blacklisted first
        if ($this->bruteForceProtection->isBlacklisted($ip)) {
            $this->logger->logSecurityEvent('BLACKLISTED_IP_BLOCKED', [
                'ip' => $ip,
                'path' => $path,
                'user_agent' => $userAgent
            ]);

            // Record security metric
            $this->securityMonitoring->recordMetric('blacklist_blocked', 1, [
                'ip' => $ip,
                'path' => $path,
                'user_agent' => $userAgent
            ]);

            return $this->createBlacklistResponse($ip);
        }

        // Analyze attack patterns for suspicious behavior
        $attackAnalysis = $this->bruteForceProtection->analyzeAttackPattern($ip, $path, $userAgent);
        
        // Auto-blacklist if analysis indicates high threat
        if ($attackAnalysis['should_blacklist']) {
            $reasons = implode(', ', $attackAnalysis['reasons']);
            $this->bruteForceProtection->blacklistIp(
                $ip, 
                "Automated blacklist: {$reasons}", 
                $attackAnalysis['blacklist_duration']
            );
            
            // Record IP blacklisting metric
            $this->securityMonitoring->recordMetric('ip_blacklisted', 1, [
                'ip' => $ip,
                'reasons' => $attackAnalysis['reasons'],
                'threat_level' => $attackAnalysis['threat_level'],
                'duration' => $attackAnalysis['blacklist_duration']
            ]);
            
            // Create high-severity alert for automated blacklisting
            $this->securityMonitoring->createAlert(
                'automated_blacklist',
                'high',
                "IP {$ip} automatically blacklisted: {$reasons}",
                [
                    'ip' => $ip,
                    'reasons' => $attackAnalysis['reasons'],
                    'threat_level' => $attackAnalysis['threat_level']
                ]
            );
            
            return $this->createBlacklistResponse($ip);
        }

        // Get contextual configuration
        $config = $this->overrideConfig ?? 
                 RateLimitConfig::getContextualConfig($path, $method, $ip, $userAgent);

        // Skip if disabled
        if (!($config['enabled'] ?? true)) {
            return $next($request);
        }

        $clientId = $this->getClientIdentifier($request);
        
        // Apply progressive penalties for authentication endpoints
        if ($this->isAuthenticationEndpoint($path)) {
            $failedAttempts = $this->rateLimitService->getFailedAttemptCount($clientId);
            $config = RateLimitConfig::getProgressiveConfig($config, $failedAttempts);
        }

        // Check rate limit
        $isAllowed = $this->rateLimitService->isAllowed(
            $clientId,
            $config['max_requests'],
            $config['window_seconds'],
            $config['algorithm']
        );

        if (!$isAllowed) {
            // Apply penalty for repeated violations
            if ($config['progressive'] ?? false) {
                $penaltySeconds = $this->rateLimitService->applyPenalty(
                    $clientId, 
                    $config['window_seconds']
                );
                $config['window_seconds'] = $penaltySeconds;
            }

            // Log rate limit violation
            $this->logger->logRateLimit(
                'EXCEEDED',
                $clientId,
                $config['max_requests'],
                $config['max_requests'],
                $ip
            );

            // Record rate limit violation metric
            $this->securityMonitoring->recordMetric('rate_limit_exceeded', 1, [
                'ip' => $ip,
                'path' => $path,
                'client_id' => $clientId,
                'limit' => $config['max_requests'],
                'window' => $config['window_seconds'],
                'algorithm' => $config['algorithm']
            ]);

            // Return rate limit exceeded response
            return $this->createRateLimitResponse($clientId, $config);
        }

        // Record the request
        $this->rateLimitService->recordRequest(
            $clientId,
            $config['max_requests'],
            $config['window_seconds'],
            $config['algorithm']
        );

        // Continue with the request
        $response = $next($request);

        // Add rate limit headers
        $status = $this->rateLimitService->getStatus(
            $clientId,
            $config['max_requests'],
            $config['window_seconds'],
            $config['algorithm']
        );

        return $this->addRateLimitHeaders($response, $status);
    }

    public function getPriority(): int
    {
        return 250; // High priority - should run early
    }

    public function getName(): string
    {
        return 'rate_limit';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        $path = $request->getPath();
        return !RateLimitConfig::isExcluded($path);
    }

    /**
     * Get a unique identifier for the client.
     */
    private function getClientIdentifier(RequestInterface $request): string
    {
        $ip = $request->getClientIp();
        
        // For authenticated users, use user ID for more granular control
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId && is_numeric($userId)) {
            return "user:{$userId}";
        }
        
        // For unauthenticated users, use IP address
        return "ip:{$ip}";
    }

    /**
     * Check if the path is an authentication endpoint.
     */
    private function isAuthenticationEndpoint(string $path): bool
    {
        $authPaths = ['/login', '/register', '/password', '/reset'];
        
        foreach ($authPaths as $authPath) {
            if (str_contains($path, $authPath)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Create rate limit exceeded response.
     */
    private function createRateLimitResponse(string $clientId, array $config): ResponseInterface
    {
        $status = $this->rateLimitService->getStatus(
            $clientId,
            $config['max_requests'],
            $config['window_seconds'],
            $config['algorithm']
        );

        $response = new \App\Core\Response();
        
        return $response
            ->setStatusCode(429)
            ->setHeader('X-RateLimit-Limit', (string)$status['limit'])
            ->setHeader('X-RateLimit-Remaining', '0')
            ->setHeader('X-RateLimit-Reset', (string)$status['reset_time'])
            ->setHeader('Retry-After', (string)$status['retry_after'])
            ->setHeader('Content-Type', 'application/json')
            ->json([
                'error' => 'Too many requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $status['retry_after'],
                'reset_time' => $status['reset_time']
            ]);
    }

    /**
     * Add rate limit headers to response.
     */
    private function addRateLimitHeaders(ResponseInterface $response, array $status): ResponseInterface
    {
        return $response
            ->setHeader('X-RateLimit-Limit', (string)$status['limit'])
            ->setHeader('X-RateLimit-Remaining', (string)$status['remaining'])
            ->setHeader('X-RateLimit-Reset', (string)$status['reset_time']);
    }

    /**
     * Record failed authentication attempt for progressive rate limiting.
     */
    public function recordFailedAttempt(RequestInterface $request): void
    {
        $clientId = $this->getClientIdentifier($request);
        $ip = $request->getClientIp();
        $path = $request->getPath();
        $userAgent = $request->getHeader('User-Agent') ?? '';
        
        $this->rateLimitService->recordFailedAttempt($clientId);
        
        // Record suspicious activity
        $this->bruteForceProtection->recordSuspiciousActivity(
            $ip, 
            'authentication_failure', 
            [
                'endpoint' => $path,
                'user_agent' => $userAgent,
                'client_id' => $clientId
            ]
        );
        
        // Record failed login metric
        $this->securityMonitoring->recordMetric('failed_login', 1, [
            'ip' => $ip,
            'path' => $path,
            'client_id' => $clientId,
            'user_agent' => $userAgent
        ]);
        
        $this->logger->logSecurityEvent('AUTH_FAILED_ATTEMPT', [
            'client_id' => $clientId,
            'ip' => $ip,
            'path' => $path,
            'user_agent' => $userAgent
        ]);
    }

    /**
     * Create blacklist response for blocked IP.
     */
    private function createBlacklistResponse(string $ip): ResponseInterface
    {
        $response = new \App\Core\Response();
        
        return $response
            ->setStatusCode(403)
            ->setHeader('Content-Type', 'application/json')
            ->json([
                'error' => 'Access Forbidden',
                'message' => 'Your IP address has been temporarily blocked due to suspicious activity.',
                'ip' => $ip,
                'blocked_at' => date('c')
            ]);
    }
}