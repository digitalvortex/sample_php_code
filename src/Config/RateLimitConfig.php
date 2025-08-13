<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Rate Limit Configuration
 * 
 * Centralized configuration for rate limiting rules and settings.
 * PHP 8.4 compatible with strict typing.
 */
class RateLimitConfig
{
    /**
     * Default rate limiting configurations by endpoint type.
     */
    public const CONFIGS = [
        'default' => [
            'max_requests' => 60,
            'window_seconds' => 60,
            'algorithm' => 'sliding_window',
            'enabled' => true
        ],
        'auth_login' => [
            'max_requests' => 5,
            'window_seconds' => 300, // 5 minutes
            'algorithm' => 'sliding_window',
            'enabled' => true,
            'progressive' => true,
            'penalty_multiplier' => 2
        ],
        'auth_register' => [
            'max_requests' => 3,
            'window_seconds' => 600, // 10 minutes
            'algorithm' => 'sliding_window',
            'enabled' => true,
            'progressive' => true,
            'penalty_multiplier' => 3
        ],
        'auth_password_reset' => [
            'max_requests' => 2,
            'window_seconds' => 3600, // 1 hour
            'algorithm' => 'fixed_window',
            'enabled' => true,
            'progressive' => true,
            'penalty_multiplier' => 4
        ],
        'api_general' => [
            'max_requests' => 100,
            'window_seconds' => 60,
            'algorithm' => 'token_bucket',
            'enabled' => true
        ],
        'api_authenticated' => [
            'max_requests' => 200,
            'window_seconds' => 60,
            'algorithm' => 'token_bucket',
            'enabled' => true
        ],
        'contact_form' => [
            'max_requests' => 2,
            'window_seconds' => 300, // 5 minutes
            'algorithm' => 'fixed_window',
            'enabled' => true,
            'progressive' => true,
            'penalty_multiplier' => 2
        ],
        'search' => [
            'max_requests' => 20,
            'window_seconds' => 60,
            'algorithm' => 'sliding_window',
            'enabled' => true
        ]
    ];

    /**
     * Paths that should be excluded from rate limiting.
     */
    public const EXCLUDED_PATHS = [
        '/css/',
        '/js/',
        '/images/',
        '/favicon.ico',
        '/robots.txt',
        '/sitemap.xml'
    ];

    /**
     * IP addresses that should be whitelisted from rate limiting.
     */
    public const WHITELISTED_IPS = [
        '127.0.0.1',
        '::1'
    ];

    /**
     * User agents that should be treated specially.
     */
    public const BOT_USER_AGENTS = [
        'Googlebot',
        'Bingbot',
        'Slurp',
        'facebookexternalhit'
    ];

    /**
     * Rate limits for bot traffic.
     */
    public const BOT_LIMITS = [
        'max_requests' => 30,
        'window_seconds' => 60,
        'algorithm' => 'fixed_window',
        'enabled' => true
    ];

    /**
     * Get configuration for a specific type.
     */
    public static function getConfig(string $type): array {
        return self::CONFIGS[$type] ?? self::CONFIGS['default'];
    }

    /**
     * Get configuration for a specific route/path.
     */
    public static function getConfigForPath(string $path, string $method = 'GET'): array {
        // Authentication endpoints
        if (str_contains($path, '/login')) {
            return self::getConfig('auth_login');
        }
        
        if (str_contains($path, '/register')) {
            return self::getConfig('auth_register');
        }
        
        if (str_contains($path, '/password') || str_contains($path, '/reset')) {
            return self::getConfig('auth_password_reset');
        }

        // Contact form
        if (str_contains($path, '/contact') && $method === 'POST') {
            return self::getConfig('contact_form');
        }

        // API endpoints
        if (str_starts_with($path, '/api/')) {
            // Check if user is authenticated for higher limits
            $isAuthenticated = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
            return $isAuthenticated ? 
                self::getConfig('api_authenticated') : 
                self::getConfig('api_general');
        }

        // Search endpoints
        if (str_contains($path, '/search')) {
            return self::getConfig('search');
        }

        // Default configuration
        return self::getConfig('default');
    }

    /**
     * Check if a path should be excluded from rate limiting.
     */
    public static function isExcluded(string $path): bool {
        foreach (self::EXCLUDED_PATHS as $excludedPath) {
            if (str_contains($path, $excludedPath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if an IP address is whitelisted.
     */
    public static function isWhitelisted(string $ip): bool {
        return in_array($ip, self::WHITELISTED_IPS, true);
    }

    /**
     * Check if user agent is a bot.
     */
    public static function isBot(string $userAgent): bool {
        foreach (self::BOT_USER_AGENTS as $botAgent) {
            if (str_contains(strtolower($userAgent), strtolower($botAgent))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get rate limit configuration based on request context.
     */
    public static function getContextualConfig(
        string $path, 
        string $method, 
        string $ip, 
        string $userAgent
    ): array {
        // Check if excluded
        if (self::isExcluded($path)) {
            return ['enabled' => false];
        }

        // Check if whitelisted
        if (self::isWhitelisted($ip)) {
            return ['enabled' => false];
        }

        // Check if bot traffic
        if (self::isBot($userAgent)) {
            return self::BOT_LIMITS;
        }

        // Get path-specific configuration
        return self::getConfigForPath($path, $method);
    }

    /**
     * Get progressive penalty configuration.
     */
    public static function getProgressiveConfig(array $baseConfig, int $failedAttempts): array {
        if (!($baseConfig['progressive'] ?? false)) {
            return $baseConfig;
        }

        $multiplier = $baseConfig['penalty_multiplier'] ?? 2;
        $penaltyFactor = min(8, pow($multiplier, max(0, $failedAttempts - 2))); // Start penalty after 2 attempts

        return array_merge($baseConfig, [
            'max_requests' => max(1, (int)($baseConfig['max_requests'] / $penaltyFactor)),
            'window_seconds' => (int)($baseConfig['window_seconds'] * $penaltyFactor)
        ]);
    }

    /**
     * Validate rate limit configuration.
     */
    public static function validateConfig(array $config): bool {
        $required = ['max_requests', 'window_seconds', 'algorithm', 'enabled'];
        
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                return false;
            }
        }

        if (!is_int($config['max_requests']) || $config['max_requests'] < 1) {
            return false;
        }

        if (!is_int($config['window_seconds']) || $config['window_seconds'] < 1) {
            return false;
        }

        $validAlgorithms = ['fixed_window', 'sliding_window', 'token_bucket'];
        if (!in_array($config['algorithm'], $validAlgorithms, true)) {
            return false;
        }

        if (!is_bool($config['enabled'])) {
            return false;
        }

        return true;
    }
}