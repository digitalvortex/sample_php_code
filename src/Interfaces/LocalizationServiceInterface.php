<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface LocalizationServiceInterface
 * 
 * Defines the contract for localization/internationalization services.
 * Extends ServiceInterface to integrate with the existing service architecture.
 * PHP 8.4 compatible with strict typing and comprehensive i18n features.
 */
interface LocalizationServiceInterface extends ServiceInterface
{
    /**
     * Set the current application locale.
     * 
     * @param string $locale The locale code (e.g., 'en', 'fr', 'es')
     * @throws \InvalidArgumentException If locale is not supported
     */
    public function setLocale(string $locale): void;

    /**
     * Get the current application locale.
     * 
     * @return string The current locale code
     */
    public function getLocale(): string;

    /**
     * Get all supported locales.
     * 
     * @return array<string> Array of supported locale codes
     */
    public function getSupportedLocales(): array;

    /**
     * Translate a key with optional parameters.
     * 
     * @param string $key Translation key (e.g., 'auth.login.title')
     * @param array<string, mixed> $params Parameters for interpolation
     * @param string|null $locale Override locale for this translation
     * @return string The translated string
     */
    public function translate(string $key, array $params = [], ?string $locale = null): string;

    /**
     * Translate with pluralization support.
     * 
     * @param string $key Translation key with plural variants
     * @param int $count The count for plural selection
     * @param array<string, mixed> $params Parameters for interpolation
     * @param string|null $locale Override locale for this translation
     * @return string The translated string with correct plural form
     */
    public function translatePlural(string $key, int $count, array $params = [], ?string $locale = null): string;

    /**
     * Check if a translation exists for a key.
     * 
     * @param string $key Translation key to check
     * @param string|null $locale Locale to check (current if null)
     * @return bool True if translation exists
     */
    public function hasTranslation(string $key, ?string $locale = null): bool;

    /**
     * Load translations for a specific locale and domain.
     * 
     * @param string $locale Locale to load
     * @param string|null $domain Translation domain (e.g., 'common', 'auth')
     * @throws \RuntimeException If translations cannot be loaded
     */
    public function loadTranslations(string $locale, ?string $domain = null): void;

    /**
     * Set the fallback locale for missing translations.
     * 
     * @param string $locale Fallback locale code
     * @throws \InvalidArgumentException If locale is not supported
     */
    public function setFallbackLocale(string $locale): void;

    /**
     * Get the fallback locale.
     * 
     * @return string The fallback locale code
     */
    public function getFallbackLocale(): string;

    /**
     * Check if a locale is supported.
     * 
     * @param string $locale Locale code to check
     * @return bool True if locale is supported
     */
    public function isLocaleSupported(string $locale): bool;

    /**
     * Get available translation domains for a locale.
     * 
     * @param string|null $locale Locale to check (current if null)
     * @return array<string> Array of available domains
     */
    public function getAvailableDomains(?string $locale = null): array;

    /**
     * Clear translation cache for locale(s).
     * 
     * @param string|null $locale Specific locale or null for all
     */
    public function clearCache(?string $locale = null): void;

    /**
     * Get translation statistics for debugging.
     * 
     * @return array<string, mixed> Statistics about loaded translations
     */
    public function getStats(): array;

    /**
     * Format a message with locale-specific formatting.
     * 
     * @param string $message Message pattern with placeholders
     * @param array<string, mixed> $params Parameters for formatting
     * @param string|null $locale Locale for formatting rules
     * @return string Formatted message
     */
    public function formatMessage(string $message, array $params = [], ?string $locale = null): string;
}