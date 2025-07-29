<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\LocalizationServiceInterface;
use App\Interfaces\TranslationLoaderInterface;
use App\Interfaces\LocaleDetectorInterface;

/**
 * Class LocalizationService
 * 
 * Core localization/internationalization service implementing LocalizationServiceInterface.
 * Provides comprehensive translation, pluralization, and locale management.
 * PHP 8.4 compatible with performance optimization and caching.
 */
class LocalizationService implements LocalizationServiceInterface
{
    private string $currentLocale;
    private string $fallbackLocale;
    private array $supportedLocales;
    private array $loadedTranslations = [];
    private array $cache = [];
    private array $stats = [
        'translations_loaded' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'fallback_used' => 0
    ];

    public function __construct(
        private TranslationLoaderInterface $loader,
        private LocaleDetectorInterface $detector,
        private array $config = []
    ) {
        $this->supportedLocales = $config['supported_locales'] ?? ['en'];
        $this->fallbackLocale = $config['fallback_locale'] ?? 'en';
        $this->currentLocale = $config['default_locale'] ?? $this->fallbackLocale;
        
        // Validate configuration
        $this->validateConfiguration();
    }

    public function initialize(): void
    {
        // Initialize with default locale
        $this->loadTranslations($this->currentLocale, 'common');
    }

    public function getName(): string
    {
        return 'localization';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function isAvailable(): bool
    {
        try {
            // Check if we can load translations for the default locale
            $this->loadTranslations($this->currentLocale, 'common');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function healthCheck(): array
    {
        $health = [
            'service' => $this->getName(),
            'version' => $this->getVersion(),
            'status' => 'healthy',
            'timestamp' => date('c'),
            'details' => []
        ];

        try {
            // Test translation loading
            $testTranslation = $this->translate('common.welcome');
            $health['details']['translation_test'] = 'passed';
            
            // Check supported locales
            $health['details']['supported_locales'] = count($this->supportedLocales);
            $health['details']['current_locale'] = $this->currentLocale;
            $health['details']['fallback_locale'] = $this->fallbackLocale;
            
            // Check cache status
            $health['details']['cached_translations'] = count($this->cache);
            $health['details']['loaded_domains'] = count($this->loadedTranslations);
            
            // Include performance stats
            $health['details']['stats'] = $this->stats;
            
        } catch (\Throwable $e) {
            $health['status'] = 'unhealthy';
            $health['details']['error'] = $e->getMessage();
            $health['details']['error_code'] = $e->getCode();
        }

        return $health;
    }

    public function getConfig(): array
    {
        return [
            'current_locale' => $this->currentLocale,
            'fallback_locale' => $this->fallbackLocale,
            'supported_locales' => $this->supportedLocales,
            'config' => $this->config
        ];
    }

    public function setConfig(array $config): void
    {
        if (isset($config['supported_locales'])) {
            $this->supportedLocales = $config['supported_locales'];
        }
        
        if (isset($config['fallback_locale'])) {
            $this->fallbackLocale = $config['fallback_locale'];
        }
        
        if (isset($config['default_locale'])) {
            $this->currentLocale = $config['default_locale'];
        }
        
        $this->config = array_merge($this->config, $config);
        $this->validateConfiguration();
    }

    public function getDependencies(): array
    {
        return [
            TranslationLoaderInterface::class,
            LocaleDetectorInterface::class
        ];
    }

    public function shutdown(): void
    {
        // Clear any memory-intensive data
        $this->cache = [];
        $this->loadedTranslations = [];
        
        // Log shutdown statistics if needed
        if ($this->config['log_stats'] ?? false) {
            error_log('LocalizationService shutdown - Stats: ' . json_encode($this->stats));
        }
    }

    public function isHealthy(): bool
    {
        try {
            // Check if we can load basic translations
            return $this->loader->exists($this->currentLocale, 'common') ||
                   $this->loader->exists($this->fallbackLocale, 'common');
        } catch (\Throwable) {
            return false;
        }
    }

    public function getHealthDetails(): array
    {
        $details = [
            'current_locale' => $this->currentLocale,
            'fallback_locale' => $this->fallbackLocale,
            'supported_locales' => $this->supportedLocales,
            'loaded_domains' => array_keys($this->loadedTranslations),
            'stats' => $this->stats
        ];

        // Check availability of each supported locale
        foreach ($this->supportedLocales as $locale) {
            $details['locale_availability'][$locale] = $this->loader->exists($locale, 'common');
        }

        return $details;
    }

    public function setLocale(string $locale): void
    {
        if (!$this->isLocaleSupported($locale)) {
            throw new \InvalidArgumentException("Unsupported locale: {$locale}");
        }
        
        $this->currentLocale = $locale;
    }

    public function getLocale(): string
    {
        return $this->currentLocale;
    }

    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }

    public function translate(string $key, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->currentLocale;
        
        // Try to get translation from cache first
        $cacheKey = $this->getCacheKey($locale, $key);
        if (isset($this->cache[$cacheKey])) {
            $this->stats['cache_hits']++;
            return $this->formatMessage($this->cache[$cacheKey], $params, $locale);
        }

        $this->stats['cache_misses']++;
        
        // Parse domain from key (e.g., 'auth.login.title' -> domain: 'auth', key: 'login.title')
        $domain = $this->extractDomain($key);
        $translationKey = $this->extractKey($key);
        
        // Ensure translations are loaded for this domain
        $this->loadTranslations($locale, $domain);
        
        // Try to find translation
        $translation = $this->findTranslation($locale, $domain, $translationKey);
        
        // Fallback to fallback locale if not found
        if ($translation === null && $locale !== $this->fallbackLocale) {
            $this->stats['fallback_used']++;
            $this->loadTranslations($this->fallbackLocale, $domain);
            $translation = $this->findTranslation($this->fallbackLocale, $domain, $translationKey);
            $locale = $this->fallbackLocale; // Use fallback locale for formatting
        }
        
        // Final fallback to the key itself
        if ($translation === null) {
            $translation = $key;
        }
        
        // Cache the result
        $this->cache[$cacheKey] = $translation;
        
        return $this->formatMessage($translation, $params, $locale);
    }

    public function translatePlural(string $key, int $count, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->currentLocale;
        $params['count'] = $count;
        
        // Parse plural key (e.g., 'items.count' -> look for items.count.zero, items.count.one, etc.)
        $pluralKey = $this->getPluralKey($key, $count, $locale);
        
        return $this->translate($pluralKey, $params, $locale);
    }

    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->currentLocale;
        $domain = $this->extractDomain($key);
        $translationKey = $this->extractKey($key);
        
        $this->loadTranslations($locale, $domain);
        
        return $this->findTranslation($locale, $domain, $translationKey) !== null;
    }

    public function loadTranslations(string $locale, ?string $domain = null): void
    {
        if (!$this->isLocaleSupported($locale)) {
            throw new \InvalidArgumentException("Unsupported locale: {$locale}");
        }
        
        $domain = $domain ?? 'common';
        $loadKey = "{$locale}.{$domain}";
        
        // Skip if already loaded
        if (isset($this->loadedTranslations[$loadKey])) {
            return;
        }
        
        if (!$this->loader->exists($locale, $domain)) {
            // Don't throw exception, just mark as loaded with empty array
            $this->loadedTranslations[$loadKey] = [];
            return;
        }
        
        try {
            $translations = $this->loader->load($locale, $domain);
            $this->loadedTranslations[$loadKey] = $translations;
            $this->stats['translations_loaded']++;
        } catch (\RuntimeException $e) {
            $this->loadedTranslations[$loadKey] = [];
            throw $e;
        }
    }

    public function setFallbackLocale(string $locale): void
    {
        if (!$this->isLocaleSupported($locale)) {
            throw new \InvalidArgumentException("Unsupported fallback locale: {$locale}");
        }
        
        $this->fallbackLocale = $locale;
    }

    public function getFallbackLocale(): string
    {
        return $this->fallbackLocale;
    }

    public function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales, true);
    }

    public function getAvailableDomains(?string $locale = null): array
    {
        $locale = $locale ?? $this->currentLocale;
        return $this->loader->getAvailableDomains($locale);
    }

    public function clearCache(?string $locale = null): void
    {
        if ($locale === null) {
            $this->cache = [];
            $this->loadedTranslations = [];
        } else {
            // Clear cache for specific locale
            $this->cache = array_filter(
                $this->cache,
                fn($key) => !str_starts_with($key, $locale . '.'),
                ARRAY_FILTER_USE_KEY
            );
            
            // Clear loaded translations for locale
            $this->loadedTranslations = array_filter(
                $this->loadedTranslations,
                fn($key) => !str_starts_with($key, $locale . '.'),
                ARRAY_FILTER_USE_KEY
            );
        }
    }

    public function getStats(): array
    {
        return [
            ...$this->stats,
            'current_locale' => $this->currentLocale,
            'fallback_locale' => $this->fallbackLocale,
            'cache_size' => count($this->cache),
            'loaded_domains' => count($this->loadedTranslations),
            'memory_usage' => memory_get_usage(),
        ];
    }

    public function formatMessage(string $message, array $params = [], ?string $locale = null): string
    {
        if (empty($params)) {
            return $message;
        }
        
        // Simple parameter replacement
        foreach ($params as $key => $value) {
            $placeholder = ":{$key}";
            $message = str_replace($placeholder, (string)$value, $message);
        }
        
        return $message;
    }

    /**
     * Validate service configuration.
     */
    private function validateConfiguration(): void
    {
        if (empty($this->supportedLocales)) {
            throw new \InvalidArgumentException('At least one supported locale must be configured');
        }
        
        if (!in_array($this->fallbackLocale, $this->supportedLocales, true)) {
            throw new \InvalidArgumentException('Fallback locale must be in supported locales');
        }
        
        if (!in_array($this->currentLocale, $this->supportedLocales, true)) {
            throw new \InvalidArgumentException('Default locale must be in supported locales');
        }
    }

    /**
     * Extract domain from translation key.
     */
    private function extractDomain(string $key): string
    {
        $parts = explode('.', $key, 2);
        return $parts[0];
    }

    /**
     * Extract key from translation key (without domain).
     */
    private function extractKey(string $key): string
    {
        $parts = explode('.', $key, 2);
        return $parts[1] ?? $parts[0];
    }

    /**
     * Generate cache key for translation.
     */
    private function getCacheKey(string $locale, string $key): string
    {
        return "{$locale}.{$key}";
    }

    /**
     * Find translation in loaded translations.
     */
    private function findTranslation(string $locale, string $domain, string $key): ?string
    {
        $loadKey = "{$locale}.{$domain}";
        
        if (!isset($this->loadedTranslations[$loadKey])) {
            return null;
        }
        
        $translations = $this->loadedTranslations[$loadKey];
        
        // Support nested keys with dot notation
        $keys = explode('.', $key);
        $value = $translations;
        
        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        
        return is_string($value) ? $value : null;
    }

    /**
     * Get plural key based on count and locale rules.
     */
    private function getPluralKey(string $key, int $count, string $locale): string
    {
        // Simple pluralization rules (can be extended for complex locale rules)
        if ($count === 0) {
            return "{$key}.zero";
        } elseif ($count === 1) {
            return "{$key}.one";
        } else {
            return "{$key}.other";
        }
    }
}