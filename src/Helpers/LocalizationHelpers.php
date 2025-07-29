<?php

declare(strict_types=1);

use App\Interfaces\LocalizationServiceInterface;

/**
 * Localization Helper Functions
 * 
 * Global helper functions for easy translation access throughout the application.
 * These functions provide a convenient interface to the localization service.
 * PHP 8.4 compatible with strict typing.
 */

if (!function_exists('trans')) {
    /**
     * Translate a key with optional parameters.
     * 
     * @param string $key Translation key (e.g., 'auth.login.title')
     * @param array<string, mixed> $params Parameters for interpolation
     * @param string|null $locale Override locale for this translation
     * @return string The translated string
     */
    function trans(string $key, array $params = [], ?string $locale = null): string
    {
        static $localizationService = null;
        
        if ($localizationService === null) {
            $localizationService = getLocalizationService();
        }
        
        return $localizationService->translate($key, $params, $locale);
    }
}

if (!function_exists('__')) {
    /**
     * Shorthand alias for trans() function.
     * 
     * @param string $key Translation key
     * @param array<string, mixed> $params Parameters for interpolation
     * @param string|null $locale Override locale for this translation
     * @return string The translated string
     */
    function __(string $key, array $params = [], ?string $locale = null): string
    {
        return trans($key, $params, $locale);
    }
}

if (!function_exists('trans_choice')) {
    /**
     * Translate with pluralization support.
     * 
     * @param string $key Translation key with plural variants
     * @param int $count The count for plural selection
     * @param array<string, mixed> $params Parameters for interpolation
     * @param string|null $locale Override locale for this translation
     * @return string The translated string with correct plural form
     */
    function trans_choice(string $key, int $count, array $params = [], ?string $locale = null): string
    {
        static $localizationService = null;
        
        if ($localizationService === null) {
            $localizationService = getLocalizationService();
        }
        
        return $localizationService->translatePlural($key, $count, $params, $locale);
    }
}

if (!function_exists('get_locale')) {
    /**
     * Get the current application locale.
     * 
     * @return string Current locale code
     */
    function get_locale(): string
    {
        static $localizationService = null;
        
        if ($localizationService === null) {
            $localizationService = getLocalizationService();
        }
        
        return $localizationService->getLocale();
    }
}

if (!function_exists('set_locale')) {
    /**
     * Set the application locale.
     * 
     * @param string $locale Locale code to set
     * @throws \InvalidArgumentException If locale is not supported
     */
    function set_locale(string $locale): void
    {
        static $localizationService = null;
        
        if ($localizationService === null) {
            $localizationService = getLocalizationService();
        }
        
        $localizationService->setLocale($locale);
    }
}

if (!function_exists('is_locale_supported')) {
    /**
     * Check if a locale is supported.
     * 
     * @param string $locale Locale code to check
     * @return bool True if locale is supported
     */
    function is_locale_supported(string $locale): bool
    {
        static $localizationService = null;
        
        if ($localizationService === null) {
            $localizationService = getLocalizationService();
        }
        
        return $localizationService->isLocaleSupported($locale);
    }
}

if (!function_exists('get_supported_locales')) {
    /**
     * Get all supported locales.
     * 
     * @return array<string> Array of supported locale codes
     */
    function get_supported_locales(): array
    {
        static $localizationService = null;
        
        if ($localizationService === null) {
            $localizationService = getLocalizationService();
        }
        
        return $localizationService->getSupportedLocales();
    }
}

if (!function_exists('has_translation')) {
    /**
     * Check if a translation exists.
     * 
     * @param string $key Translation key to check
     * @param string|null $locale Locale to check (current if null)
     * @return bool True if translation exists
     */
    function has_translation(string $key, ?string $locale = null): bool
    {
        static $localizationService = null;
        
        if ($localizationService === null) {
            $localizationService = getLocalizationService();
        }
        
        return $localizationService->hasTranslation($key, $locale);
    }
}

if (!function_exists('locale_url')) {
    /**
     * Generate a URL with locale prefix.
     * 
     * @param string $path URL path without locale
     * @param string|null $locale Locale to use (current if null)
     * @return string URL with locale prefix
     */
    function locale_url(string $path, ?string $locale = null): string
    {
        $locale = $locale ?? get_locale();
        $path = ltrim($path, '/');
        
        // Don't add locale prefix for default locale if configured
        $defaultLocale = $_ENV['LOCALIZATION_DEFAULT_LOCALE'] ?? 'en';
        $hideDefaultLocale = $_ENV['LOCALIZATION_HIDE_DEFAULT_LOCALE'] ?? false;
        
        if ($hideDefaultLocale && $locale === $defaultLocale) {
            return '/' . $path;
        }
        
        return '/' . $locale . '/' . $path;
    }
}

if (!function_exists('getLocalizationService')) {
    /**
     * Get the localization service instance.
     * This is a helper function to access the service from the container.
     * 
     * @return LocalizationServiceInterface
     * @throws \RuntimeException If service cannot be resolved
     */
    function getLocalizationService(): LocalizationServiceInterface
    {
        static $service = null;
        
        if ($service === null) {
            // Try to get container from GLOBALS
            if (!isset($GLOBALS['container'])) {
                throw new \RuntimeException('Container not available. Ensure the application is properly bootstrapped.');
            }
            
            $container = $GLOBALS['container'];
            
            try {
                $service = $container->get(LocalizationServiceInterface::class);
            } catch (\Throwable $e) {
                throw new \RuntimeException('LocalizationService not available: ' . $e->getMessage());
            }
        }
        
        return $service;
    }
}