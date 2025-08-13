<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\SecurityLoggerService;
use App\Services\EncryptionService;

/**
 * Session Service
 * 
 * Enhanced session management with security features and remember me functionality.
 * PHP 8.4 compatible with strict typing and comprehensive security measures.
 */
class SessionService
{
    private SecurityLoggerService $securityLogger;
    private EncryptionService $encryptionService;
    private int $sessionLifetime;
    private int $rememberLifetime;

    public function __construct(
        SecurityLoggerService $securityLogger,
        EncryptionService $encryptionService
    ) {
        $this->securityLogger = $securityLogger;
        $this->encryptionService = $encryptionService;
        $this->sessionLifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 3600); // 1 hour default
        $this->rememberLifetime = (int)($_ENV['REMEMBER_LIFETIME'] ?? 2592000); // 30 days default
    }

    /**
     * Start a secure session for a user.
     *
     * @param User $user User to start session for
     * @param bool $remember Whether to enable remember me functionality
     * @param string $ipAddress Client IP address
     * @param string $userAgent User agent string
     * @return bool True if session started successfully
     */
    public function startUserSession(User $user, bool $remember = false, string $ipAddress = '', string $userAgent = ''): bool
    {
        // Ensure session is started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!session_start()) {
                return false;
            }
        }

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Set session data
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getAttribute('username');
        $_SESSION['email'] = $user->getAttribute('email');
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['expires_at'] = time() + $this->sessionLifetime;
        $_SESSION['ip_address'] = $ipAddress;
        $_SESSION['user_agent'] = $userAgent;
        $_SESSION['session_token'] = $this->generateSecureToken();

        // Set remember me cookie if requested
        if ($remember) {
            $this->setRememberMeCookie($user, $ipAddress, $userAgent);
        }

        // Log successful session creation
        $this->securityLogger->logSessionEvent(
            'created',
            session_id(),
            $user->getId(),
            $ipAddress,
            [
                'remember_me' => $remember,
                'session_lifetime' => $this->sessionLifetime
            ]
        );

        return true;
    }

    /**
     * Validate and update current session.
     *
     * @param string $ipAddress Current IP address
     * @param string $userAgent Current user agent
     * @return bool True if session is valid and updated
     */
    public function validateSession(string $ipAddress, string $userAgent): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        // Check if user is authenticated
        if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
            return false;
        }

        // Check session expiration
        if (isset($_SESSION['expires_at']) && $_SESSION['expires_at'] < time()) {
            $this->destroySession('expired');
            return false;
        }

        // Validate session token
        if (!isset($_SESSION['session_token']) || empty($_SESSION['session_token'])) {
            $this->destroySession('invalid_token');
            return false;
        }

        // Check for IP address changes (log but allow for mobile users)
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $ipAddress) {
            $this->securityLogger->logSessionEvent(
                'ip_change',
                session_id(),
                $_SESSION['user_id'] ?? null,
                $ipAddress,
                [
                    'old_ip' => $_SESSION['ip_address'],
                    'new_ip' => $ipAddress
                ]
            );
            
            // Update IP address but continue session
            $_SESSION['ip_address'] = $ipAddress;
        }

        // Check for user agent changes (more suspicious)
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $userAgent) {
            $this->securityLogger->logSessionEvent(
                'user_agent_change',
                session_id(),
                $_SESSION['user_id'] ?? null,
                $ipAddress,
                [
                    'old_agent' => substr($_SESSION['user_agent'], 0, 100),
                    'new_agent' => substr($userAgent, 0, 100)
                ]
            );
        }

        // Update session activity
        $_SESSION['last_activity'] = time();
        $_SESSION['user_agent'] = $userAgent;

        // Extend session expiration
        $_SESSION['expires_at'] = time() + $this->sessionLifetime;

        return true;
    }

    /**
     * Destroy current session.
     *
     * @param string $reason Reason for session destruction
     * @return bool True if session destroyed successfully
     */
    public function destroySession(string $reason = 'logout'): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionId = session_id();
            $userId = $_SESSION['user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            // Log session destruction
            $this->securityLogger->logSessionEvent(
                'destroyed',
                $sessionId,
                $userId,
                $ipAddress,
                ['reason' => $reason]
            );

            // Clear session data
            $_SESSION = [];

            // Delete session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // Clear remember me cookie
            $this->clearRememberMeCookie();

            // Destroy session
            session_destroy();

            return true;
        }

        return false;
    }

    /**
     * Check if remember me cookie is valid and restore session.
     *
     * @param string $ipAddress Client IP address
     * @param string $userAgent User agent string
     * @return User|null User if remember me is valid, null otherwise
     */
    public function checkRememberMe(string $ipAddress, string $userAgent): ?User
    {
        if (!isset($_COOKIE['remember_me'])) {
            return null;
        }

        try {
            // Decrypt and decode remember me token
            $tokenData = json_decode($this->encryptionService->decrypt($_COOKIE['remember_me']), true);
            
            if (!$tokenData || !isset($tokenData['user_id'], $tokenData['token'], $tokenData['expires'])) {
                $this->clearRememberMeCookie();
                return null;
            }

            // Check expiration
            if ($tokenData['expires'] < time()) {
                $this->clearRememberMeCookie();
                return null;
            }

            // Find user
            $user = User::find($tokenData['user_id']);
            if (!$user) {
                $this->clearRememberMeCookie();
                return null;
            }

            // Validate token (you might want to store this in database for better security)
            $expectedToken = $this->generateRememberToken($user->getId(), $tokenData['expires']);
            if (!hash_equals($expectedToken, $tokenData['token'])) {
                $this->clearRememberMeCookie();
                $this->securityLogger->logSessionEvent(
                    'remember_me_invalid',
                    '',
                    $user->getId(),
                    $ipAddress,
                    ['reason' => 'invalid_token']
                );
                return null;
            }

            // Log successful remember me authentication
            $this->securityLogger->logSessionEvent(
                'remember_me_success',
                '',
                $user->getId(),
                $ipAddress
            );

            return $user;

        } catch (\Throwable $e) {
            $this->clearRememberMeCookie();
            return null;
        }
    }

    /**
     * Get current authenticated user from session.
     *
     * @return User|null Current user if authenticated, null otherwise
     */
    public function getCurrentUser(): ?User
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['user_id'])) {
            return null;
        }

        return User::find($_SESSION['user_id']);
    }

    /**
     * Check if current session is authenticated.
     *
     * @return bool True if authenticated
     */
    public function isAuthenticated(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE && 
               isset($_SESSION['authenticated']) && 
               $_SESSION['authenticated'] === true;
    }

    /**
     * Get session information.
     *
     * @return array<string, mixed> Session information
     */
    public function getSessionInfo(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }

        return [
            'session_id' => session_id(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'expires_at' => $_SESSION['expires_at'] ?? null,
            'ip_address' => $_SESSION['ip_address'] ?? null,
            'time_remaining' => isset($_SESSION['expires_at']) ? max(0, $_SESSION['expires_at'] - time()) : 0
        ];
    }

    /**
     * Set remember me cookie with encrypted token.
     *
     * @param User $user User to remember
     * @param string $ipAddress Client IP address
     * @param string $userAgent User agent string
     */
    private function setRememberMeCookie(User $user, string $ipAddress, string $userAgent): void
    {
        $expires = time() + $this->rememberLifetime;
        $token = $this->generateRememberToken($user->getId(), $expires);

        $tokenData = [
            'user_id' => $user->getId(),
            'token' => $token,
            'expires' => $expires,
            'ip_address' => $ipAddress // Optional: for additional security
        ];

        $encryptedToken = $this->encryptionService->encrypt(json_encode($tokenData));

        setcookie(
            'remember_me',
            $encryptedToken,
            [
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => true, // HTTPS only
                'httponly' => true, // Prevent XSS
                'samesite' => 'Strict' // CSRF protection
            ]
        );
    }

    /**
     * Clear remember me cookie.
     */
    private function clearRememberMeCookie(): void
    {
        if (isset($_COOKIE['remember_me'])) {
            setcookie(
                'remember_me',
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );
        }
    }

    /**
     * Generate a secure session token.
     *
     * @return string Secure random token
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a remember me token.
     *
     * @param int $userId User ID
     * @param int $expires Expiration timestamp
     * @return string Remember me token
     */
    private function generateRememberToken(int $userId, int $expires): string
    {
        $key = $_ENV['ENCRYPTION_KEY'] ?? $_ENV['KEY'] ?? 'default';
        $key = trim($key, '"'); // Remove quotes if present
        $data = $userId . '|' . $expires . '|' . $key;
        return hash('sha256', $data);
    }

    /**
     * Configure session security settings.
     */
    public static function configureSessionSecurity(): void
    {
        // Prevent session fixation
        ini_set('session.use_strict_mode', '1');
        
        // Use cookies only (no URL sessions)
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        
        // Security settings
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1'); // HTTPS only
        ini_set('session.cookie_samesite', 'Strict');
        
        // Session lifetime
        ini_set('session.gc_maxlifetime', (string)(3600)); // 1 hour
        ini_set('session.cookie_lifetime', '0'); // Session cookies
        
        // Entropy settings
        ini_set('session.entropy_length', '32');
        ini_set('session.hash_function', 'sha256');
        
        // Session name (don't use default PHPSESSID)
        session_name('SECURE_SESSION');
    }
}