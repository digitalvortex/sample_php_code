<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;
use App\Services\JwtService;
use App\Services\SecurityLoggerService;
use App\Models\ApiKey;
use App\Models\User;

/**
 * Authentication Middleware
 * 
 * Ensures users are authenticated before accessing protected routes.
 * Supports session, JWT token, and API key authentication.
 * PHP 8.4 compatible with strict typing and comprehensive security.
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    private JwtService $jwtService;
    private SecurityLoggerService $securityLogger;

    /**
     * @var array<string> Routes that don't require authentication
     */
    private array $publicRoutes = [
        '/',
        '/about',
        '/contact',
        '/services',
        '/blog',
        '/login',
        '/register',
        '/password-reset'
    ];

    public function __construct(JwtService $jwtService, SecurityLoggerService $securityLogger)
    {
        $this->jwtService = $jwtService;
        $this->securityLogger = $securityLogger;
    }

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        $path = $request->getPath();

        // Skip authentication for public routes
        if ($this->isPublicRoute($path)) {
            return $next($request);
        }

        // Check for authentication (session, JWT token, etc.)
        $isAuthenticated = $this->checkAuthentication($request);

        if (!$isAuthenticated) {
            // Redirect to login for web requests, return 401 for API requests
            $response = new \App\Core\Response();
            
            if ($this->isApiRequest($request)) {
                return $response
                    ->setStatusCode(401)
                    ->json(['error' => 'Authentication required']);
            }

            return $response->redirect('/login');
        }

        return $next($request);
    }

    public function getPriority(): int
    {
        return 200; // Medium priority - after CSRF but before rate limiting
    }

    public function getName(): string
    {
        return 'auth';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        // Apply to all routes except those explicitly excluded
        return !$this->isPublicRoute($request->getPath());
    }

    /**
     * Check if the current route is public (doesn't require authentication).
     */
    private function isPublicRoute(string $path): bool
    {
        foreach ($this->publicRoutes as $publicRoute) {
            if ($path === $publicRoute || str_starts_with($path, $publicRoute . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the user is authenticated.
     */
    private function checkAuthentication(RequestInterface $request): bool
    {
        $ipAddress = $request->getClientIp();
        $userAgent = $request->getUserAgent();

        // Check session-based authentication with security validation
        if ($this->validateSession($request)) {
            $this->securityLogger->logAuthSuccess(
                $_SESSION['user_id'], 
                'session', 
                $ipAddress, 
                $userAgent
            );
            return true;
        }

        // Check JWT token authentication
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $result = $this->validateJwtToken($token);
            
            if ($result) {
                $this->securityLogger->logAuthSuccess(
                    $result['user_id'], 
                    'jwt', 
                    $ipAddress, 
                    $userAgent
                );
                return true;
            } else {
                $this->securityLogger->logAuthFailure(
                    'jwt', 
                    'token', 
                    'invalid_token', 
                    $ipAddress, 
                    $userAgent
                );
            }
        }

        // Check API key authentication
        $apiKey = $request->getHeader('X-API-Key');
        if ($apiKey) {
            $result = $this->validateApiKey($apiKey);
            
            if ($result) {
                $this->securityLogger->logAuthSuccess(
                    $result['user_id'], 
                    'api_key', 
                    $ipAddress, 
                    $userAgent
                );
                return true;
            } else {
                $this->securityLogger->logAuthFailure(
                    'api_key', 
                    'key', 
                    'invalid_key', 
                    $ipAddress, 
                    $userAgent
                );
            }
        }

        return false;
    }

    /**
     * Check if this is an API request.
     */
    private function isApiRequest(RequestInterface $request): bool
    {
        return str_starts_with($request->getPath(), '/api/') ||
               $request->getHeader('Content-Type') === 'application/json' ||
               $request->getHeader('Accept') === 'application/json';
    }

    /**
     * Validate session with security checks.
     *
     * @param RequestInterface $request Request object
     * @return bool True if session is valid and secure
     */
    private function validateSession(RequestInterface $request): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['user_id'])) {
            return false;
        }

        $ipAddress = $request->getClientIp();
        $userAgent = $request->getUserAgent();

        // Validate session IP (if IP validation is enabled)
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $ipAddress) {
            // Allow for mobile/proxy IP changes with warning
            $this->securityLogger->logSuspiciousActivity(
                'session_ip_change',
                [
                    'old_ip' => $_SESSION['ip_address'],
                    'new_ip' => $ipAddress,
                    'session_id' => session_id()
                ],
                $ipAddress,
                $_SESSION['user_id']
            );
            
            // Update IP but continue (for mobile users)
            $_SESSION['ip_address'] = $ipAddress;
        }

        // Validate session user agent (basic check)
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $userAgent) {
            $this->securityLogger->logSuspiciousActivity(
                'session_user_agent_change',
                [
                    'old_agent' => substr($_SESSION['user_agent'], 0, 100),
                    'new_agent' => substr($userAgent, 0, 100),
                    'session_id' => session_id()
                ],
                $ipAddress,
                $_SESSION['user_id']
            );
        }

        // Check session expiration
        if (isset($_SESSION['expires_at']) && $_SESSION['expires_at'] < time()) {
            session_destroy();
            return false;
        }

        // Update last activity
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $ipAddress;
        $_SESSION['user_agent'] = $userAgent;

        return true;
    }

    /**
     * Validate JWT token with comprehensive security checks.
     *
     * @param string $token JWT token
     * @return array<string, mixed>|null Token payload if valid, null if invalid
     */
    private function validateJwtToken(string $token): ?array
    {
        $payload = $this->jwtService->validateToken($token);
        
        if (!$payload) {
            $this->securityLogger->logJwtEvent(
                'invalid',
                'unknown',
                null,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            );
            return null;
        }

        $jti = $payload['jti'] ?? 'unknown';
        $userId = $payload['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Check if token is blacklisted
        if (isset($payload['jti']) && $this->jwtService->isTokenBlacklisted($payload['jti'])) {
            $this->securityLogger->logJwtEvent('blacklisted', $jti, $userId, $ipAddress);
            return null;
        }

        $this->securityLogger->logJwtEvent('validated', $jti, $userId, $ipAddress);
        return $payload;
    }

    /**
     * Validate API key with security checks.
     *
     * @param string $apiKey API key to validate
     * @return array<string, mixed>|null API key data if valid, null if invalid
     */
    private function validateApiKey(string $apiKey): ?array
    {
        $apiKeyModel = ApiKey::validateKey($apiKey);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        if (!$apiKeyModel) {
            $this->securityLogger->logApiKeyEvent('invalid', null, null, $ipAddress);
            return null;
        }

        $keyId = $apiKeyModel->getId();
        $userId = $apiKeyModel->getAttribute('user_id');

        // Check if API key is expired
        if ($apiKeyModel->isExpired()) {
            $this->securityLogger->logApiKeyEvent('expired', $keyId, $userId, $ipAddress);
            return null;
        }

        $this->securityLogger->logApiKeyEvent('validated', $keyId, $userId, $ipAddress, $_SERVER['REQUEST_URI'] ?? null);
        
        return [
            'user_id' => $userId,
            'key_id' => $keyId,
            'permissions' => $apiKeyModel->getPermissions()
        ];
    }
}