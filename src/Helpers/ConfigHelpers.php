<?php

declare(strict_types=1);

/**
 * Configuration Helper Functions
 * 
 * Helper functions for configuration management and environment variables.
 * PHP 8.4 compatible with strict typing.
 */

if (!function_exists('env')) {
    /**
     * Get an environment variable with an optional default value.
     * 
     * @param string $key Environment variable key
     * @param mixed $default Default value if not found
     * @return mixed Environment variable value or default
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string representations of boolean values
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off', ''], true)) {
                return false;
            }
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }
        }
        
        return $value;
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation.
     * 
     * @param string $key Configuration key (e.g., 'localization.default_locale')
     * @param mixed $default Default value if not found
     * @return mixed Configuration value or default
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];
        
        // Parse the key
        $parts = explode('.', $key);
        $filename = array_shift($parts);
        
        // Load config file if not cached
        if (!isset($cache[$filename])) {
            $configPath = __DIR__ . "/../../config/{$filename}.php";
            
            if (!file_exists($configPath)) {
                return $default;
            }
            
            $config = include $configPath;
            if (!is_array($config)) {
                return $default;
            }
            
            $cache[$filename] = $config;
        }
        
        // Navigate through the nested array
        $value = $cache[$filename];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        
        return $value;
    }
}