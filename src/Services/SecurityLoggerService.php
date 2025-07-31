<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Security Logger Service
 * 
 * Handles logging of security-related events for audit and monitoring.
 * PHP 8.4 compatible with strict typing.
 */
class SecurityLoggerService
{
    private string $logPath;
    private bool $enabled;

    public function __construct()
    {
        $this->logPath = $_ENV['SECURITY_LOG_PATH'] ?? '/var/log/php-mvc-security.log';
        $this->enabled = filter_var($_ENV['SECURITY_LOGGING_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Log authentication success.
     *
     * @param int $userId User ID
     * @param string $method Authentication method (session, jwt, api_key)
     * @param string $ipAddress Client IP address
     * @param string $userAgent User agent string
     */
    public function logAuthSuccess(int $userId, string $method, string $ipAddress, string $userAgent): void
    {
        $this->log('AUTH_SUCCESS', [
            'user_id' => $userId,
            'method' => $method,
            'ip_address' => $ipAddress,
            'user_agent' => $this->sanitizeUserAgent($userAgent)
        ]);
    }

    /**
     * Log authentication failure.
     *
     * @param string $method Authentication method attempted
     * @param string $identifier User identifier (email, username, etc.)
     * @param string $reason Failure reason
     * @param string $ipAddress Client IP address
     * @param string $userAgent User agent string
     */
    public function logAuthFailure(string $method, string $identifier, string $reason, string $ipAddress, string $userAgent): void
    {
        $this->log('AUTH_FAILURE', [
            'method' => $method,
            'identifier' => $this->sanitizeIdentifier($identifier),
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $this->sanitizeUserAgent($userAgent)
        ]);
    }

    /**
     * Log JWT token validation events.
     *
     * @param string $event Event type (validated, expired, invalid, blacklisted)
     * @param string $tokenId Token JTI if available
     * @param int|null $userId User ID if token is valid
     * @param string $ipAddress Client IP address
     */
    public function logJwtEvent(string $event, string $tokenId, ?int $userId, string $ipAddress): void
    {
        $this->log('JWT_' . strtoupper($event), [
            'token_id' => $tokenId,
            'user_id' => $userId,
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Log API key usage events.
     *
     * @param string $event Event type (validated, expired, invalid, rate_limited)
     * @param int|null $keyId API key ID
     * @param int|null $userId User ID
     * @param string $ipAddress Client IP address
     * @param string|null $endpoint Endpoint accessed
     */
    public function logApiKeyEvent(string $event, ?int $keyId, ?int $userId, string $ipAddress, ?string $endpoint = null): void
    {
        $this->log('API_KEY_' . strtoupper($event), [
            'key_id' => $keyId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'endpoint' => $endpoint
        ]);
    }

    /**
     * Log suspicious activity.
     *
     * @param string $activity Type of suspicious activity
     * @param array<string, mixed> $details Activity details
     * @param string $ipAddress Client IP address
     * @param int|null $userId User ID if known
     */
    public function logSuspiciousActivity(string $activity, array $details, string $ipAddress, ?int $userId = null): void
    {
        $this->log('SUSPICIOUS_ACTIVITY', [
            'activity' => $activity,
            'details' => $details,
            'ip_address' => $ipAddress,
            'user_id' => $userId
        ]);
    }

    /**
     * Log rate limiting events.
     *
     * @param string $type Rate limit type (global, user, ip, api_key)
     * @param string $identifier Rate limit identifier
     * @param int $attempts Number of attempts
     * @param int $limit Rate limit threshold
     * @param string $ipAddress Client IP address
     */
    public function logRateLimit(string $type, string $identifier, int $attempts, int $limit, string $ipAddress): void
    {
        $this->log('RATE_LIMIT_EXCEEDED', [
            'type' => $type,
            'identifier' => $identifier,
            'attempts' => $attempts,
            'limit' => $limit,
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Log security configuration changes.
     *
     * @param string $change Type of change
     * @param array<string, mixed> $details Change details
     * @param int $adminUserId Admin user making the change
     * @param string $ipAddress Client IP address
     */
    public function logSecurityConfigChange(string $change, array $details, int $adminUserId, string $ipAddress): void
    {
        $this->log('SECURITY_CONFIG_CHANGE', [
            'change' => $change,
            'details' => $details,
            'admin_user_id' => $adminUserId,
            'ip_address' => $ipAddress
        ]);
    }

    /**
     * Log session security events.
     *
     * @param string $event Event type (created, destroyed, hijack_detected, etc.)
     * @param string $sessionId Session ID (first 8 chars for privacy)
     * @param int|null $userId User ID
     * @param string $ipAddress Client IP address
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function logSessionEvent(string $event, string $sessionId, ?int $userId, string $ipAddress, array $metadata = []): void
    {
        $this->log('SESSION_' . strtoupper($event), [
            'session_id' => substr($sessionId, 0, 8) . '...',
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'metadata' => $metadata
        ]);
    }

    /**
     * Write log entry to file.
     *
     * @param string $event Event type
     * @param array<string, mixed> $data Event data
     */
    private function log(string $event, array $data): void
    {
        if (!$this->enabled) {
            return;
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'data' => $data
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES) . "\n";

        // Ensure log directory exists
        $logDir = dirname($this->logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Write to log file (append mode)
        file_put_contents($this->logPath, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Sanitize user agent string for logging.
     *
     * @param string $userAgent Raw user agent
     * @return string Sanitized user agent
     */
    private function sanitizeUserAgent(string $userAgent): string
    {
        // Truncate long user agents and remove potentially sensitive data
        $sanitized = substr($userAgent, 0, 200);
        
        // Remove potentially sensitive tokens or session data
        $sanitized = preg_replace('/[a-f0-9]{32,}/i', '[TOKEN]', $sanitized);
        
        return $sanitized ?: 'Unknown';
    }

    /**
     * Sanitize user identifier for logging.
     *
     * @param string $identifier User identifier
     * @return string Sanitized identifier
     */
    private function sanitizeIdentifier(string $identifier): string
    {
        // For email addresses, show only domain for privacy
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $identifier);
            return '***@' . ($parts[1] ?? 'unknown');
        }

        // For other identifiers, show only first 3 characters
        return strlen($identifier) > 3 ? substr($identifier, 0, 3) . '***' : '***';
    }

    /**
     * Get recent security events from log file.
     *
     * @param int $limit Number of events to retrieve
     * @param string|null $eventType Filter by event type
     * @return array<array<string, mixed>> Recent events
     */
    public function getRecentEvents(int $limit = 100, ?string $eventType = null): array
    {
        if (!file_exists($this->logPath)) {
            return [];
        }

        $events = [];
        $handle = fopen($this->logPath, 'r');
        
        if (!$handle) {
            return [];
        }

        // Read file in reverse order for recent events
        $lines = [];
        while (($line = fgets($handle)) !== false) {
            $lines[] = trim($line);
        }
        fclose($handle);

        $lines = array_reverse($lines);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $event = json_decode($line, true);
            if (!$event) {
                continue;
            }

            // Filter by event type if specified
            if ($eventType && ($event['event'] ?? '') !== $eventType) {
                continue;
            }

            $events[] = $event;

            if (count($events) >= $limit) {
                break;
            }
        }

        return $events;
    }

    /**
     * Check if logging is enabled.
     *
     * @return bool True if logging is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get current log file path.
     *
     * @return string Log file path
     */
    public function getLogPath(): string
    {
        return $this->logPath;
    }
}