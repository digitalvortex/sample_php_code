<?php

declare(strict_types=1);

namespace Tests\Config;

use App\Config\RateLimitConfig;
use PHPUnit\Framework\TestCase;

/**
 * Rate Limit Configuration Test
 * 
 * Tests for rate limit configuration functionality.
 */
class RateLimitConfigTest extends TestCase
{
    public function testGetDefaultConfig(): void
    {
        $config = RateLimitConfig::getConfig('default');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('max_requests', $config);
        $this->assertArrayHasKey('window_seconds', $config);
        $this->assertArrayHasKey('algorithm', $config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertTrue($config['enabled']);
    }

    public function testGetSpecificConfig(): void
    {
        $config = RateLimitConfig::getConfig('auth_login');

        $this->assertIsArray($config);
        $this->assertEquals(5, $config['max_requests']);
        $this->assertEquals(300, $config['window_seconds']);
        $this->assertEquals('sliding_window', $config['algorithm']);
        $this->assertTrue($config['enabled']);
        $this->assertTrue($config['progressive']);
    }

    public function testGetNonExistentConfig(): void
    {
        $config = RateLimitConfig::getConfig('non_existent');

        // Should return default config
        $defaultConfig = RateLimitConfig::getConfig('default');
        $this->assertEquals($defaultConfig, $config);
    }

    public function testGetConfigForAuthPaths(): void
    {
        // Test login path
        $loginConfig = RateLimitConfig::getConfigForPath('/login', 'POST');
        $this->assertEquals(5, $loginConfig['max_requests']);
        $this->assertEquals(300, $loginConfig['window_seconds']);

        // Test register path
        $registerConfig = RateLimitConfig::getConfigForPath('/register', 'POST');
        $this->assertEquals(3, $registerConfig['max_requests']);
        $this->assertEquals(600, $registerConfig['window_seconds']);

        // Test password reset path
        $resetConfig = RateLimitConfig::getConfigForPath('/password/reset', 'POST');
        $this->assertEquals(2, $resetConfig['max_requests']);
        $this->assertEquals(3600, $resetConfig['window_seconds']);
    }

    public function testGetConfigForContactForm(): void
    {
        $config = RateLimitConfig::getConfigForPath('/contact/submit', 'POST');

        $this->assertEquals(2, $config['max_requests']);
        $this->assertEquals(300, $config['window_seconds']);
        $this->assertEquals('fixed_window', $config['algorithm']);
        $this->assertTrue($config['progressive']);
    }

    public function testGetConfigForApiEndpoints(): void
    {
        // Simulate unauthenticated user
        unset($_SESSION['user_id']);
        $config = RateLimitConfig::getConfigForPath('/api/test', 'GET');
        $this->assertEquals(100, $config['max_requests']);

        // Simulate authenticated user
        $_SESSION['user_id'] = 123;
        $configAuth = RateLimitConfig::getConfigForPath('/api/test', 'GET');
        $this->assertEquals(200, $configAuth['max_requests']);

        // Clean up
        unset($_SESSION['user_id']);
    }

    public function testGetConfigForSearchEndpoints(): void
    {
        $config = RateLimitConfig::getConfigForPath('/search', 'GET');

        $this->assertEquals(20, $config['max_requests']);
        $this->assertEquals(60, $config['window_seconds']);
        $this->assertEquals('sliding_window', $config['algorithm']);
    }

    public function testIsExcluded(): void
    {
        // Test excluded paths
        $this->assertTrue(RateLimitConfig::isExcluded('/css/main.css'));
        $this->assertTrue(RateLimitConfig::isExcluded('/js/script.js'));
        $this->assertTrue(RateLimitConfig::isExcluded('/images/logo.png'));
        $this->assertTrue(RateLimitConfig::isExcluded('/favicon.ico'));

        // Test non-excluded paths
        $this->assertFalse(RateLimitConfig::isExcluded('/login'));
        $this->assertFalse(RateLimitConfig::isExcluded('/api/test'));
        $this->assertFalse(RateLimitConfig::isExcluded('/contact'));
    }

    public function testIsWhitelisted(): void
    {
        // Test whitelisted IPs
        $this->assertTrue(RateLimitConfig::isWhitelisted('127.0.0.1'));
        $this->assertTrue(RateLimitConfig::isWhitelisted('::1'));

        // Test non-whitelisted IPs
        $this->assertFalse(RateLimitConfig::isWhitelisted('192.168.1.1'));
        $this->assertFalse(RateLimitConfig::isWhitelisted('10.0.0.1'));
    }

    public function testIsBot(): void
    {
        // Test bot user agents
        $this->assertTrue(RateLimitConfig::isBot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
        $this->assertTrue(RateLimitConfig::isBot('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'));
        $this->assertTrue(RateLimitConfig::isBot('facebookexternalhit/1.1'));

        // Test regular user agents
        $this->assertFalse(RateLimitConfig::isBot('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'));
        $this->assertFalse(RateLimitConfig::isBot('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'));
    }

    public function testGetContextualConfigExcluded(): void
    {
        $config = RateLimitConfig::getContextualConfig('/css/main.css', 'GET', '192.168.1.1', 'Mozilla/5.0');

        $this->assertFalse($config['enabled']);
    }

    public function testGetContextualConfigWhitelisted(): void
    {
        $config = RateLimitConfig::getContextualConfig('/login', 'POST', '127.0.0.1', 'Mozilla/5.0');

        $this->assertFalse($config['enabled']);
    }

    public function testGetContextualConfigBot(): void
    {
        $config = RateLimitConfig::getContextualConfig('/api/test', 'GET', '192.168.1.1', 'Googlebot/2.1');

        $this->assertEquals(30, $config['max_requests']);
        $this->assertEquals(60, $config['window_seconds']);
        $this->assertEquals('fixed_window', $config['algorithm']);
    }

    public function testGetProgressiveConfig(): void
    {
        $baseConfig = [
            'max_requests' => 5,
            'window_seconds' => 300,
            'progressive' => true,
            'penalty_multiplier' => 2
        ];

        // No penalty for first failed attempt
        $config1 = RateLimitConfig::getProgressiveConfig($baseConfig, 1);
        $this->assertEquals(5, $config1['max_requests']);
        $this->assertEquals(300, $config1['window_seconds']);

        // Penalty starts after 2 attempts
        $config3 = RateLimitConfig::getProgressiveConfig($baseConfig, 3);
        $this->assertLessThan(5, $config3['max_requests']);
        $this->assertGreaterThan(300, $config3['window_seconds']);

        // Progressive increase
        $config4 = RateLimitConfig::getProgressiveConfig($baseConfig, 4);
        $this->assertLessThan($config3['max_requests'], $config4['max_requests']);
        $this->assertGreaterThan($config3['window_seconds'], $config4['window_seconds']);
    }

    public function testGetProgressiveConfigNonProgressive(): void
    {
        $baseConfig = [
            'max_requests' => 5,
            'window_seconds' => 300,
            'progressive' => false
        ];

        $config = RateLimitConfig::getProgressiveConfig($baseConfig, 5);

        // Should return unchanged config
        $this->assertEquals($baseConfig, $config);
    }

    public function testValidateConfigValid(): void
    {
        $validConfig = [
            'max_requests' => 10,
            'window_seconds' => 60,
            'algorithm' => 'sliding_window',
            'enabled' => true
        ];

        $this->assertTrue(RateLimitConfig::validateConfig($validConfig));
    }

    public function testValidateConfigMissingFields(): void
    {
        $invalidConfig = [
            'max_requests' => 10,
            'window_seconds' => 60
            // Missing algorithm and enabled
        ];

        $this->assertFalse(RateLimitConfig::validateConfig($invalidConfig));
    }

    public function testValidateConfigInvalidValues(): void
    {
        // Invalid max_requests
        $config1 = [
            'max_requests' => 0,
            'window_seconds' => 60,
            'algorithm' => 'sliding_window',
            'enabled' => true
        ];
        $this->assertFalse(RateLimitConfig::validateConfig($config1));

        // Invalid window_seconds
        $config2 = [
            'max_requests' => 10,
            'window_seconds' => -1,
            'algorithm' => 'sliding_window',
            'enabled' => true
        ];
        $this->assertFalse(RateLimitConfig::validateConfig($config2));

        // Invalid algorithm
        $config3 = [
            'max_requests' => 10,
            'window_seconds' => 60,
            'algorithm' => 'invalid_algorithm',
            'enabled' => true
        ];
        $this->assertFalse(RateLimitConfig::validateConfig($config3));

        // Invalid enabled
        $config4 = [
            'max_requests' => 10,
            'window_seconds' => 60,
            'algorithm' => 'sliding_window',
            'enabled' => 'yes'
        ];
        $this->assertFalse(RateLimitConfig::validateConfig($config4));
    }
}