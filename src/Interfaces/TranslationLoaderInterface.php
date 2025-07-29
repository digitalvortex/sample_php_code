<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface TranslationLoaderInterface
 * 
 * Defines the contract for loading translation files from various sources.
 * Supports multiple file formats and caching strategies.
 * PHP 8.4 compatible with strict typing.
 */
interface TranslationLoaderInterface
{
    /**
     * Load translations for a specific locale and domain.
     * 
     * @param string $locale Locale code (e.g., 'en', 'fr')
     * @param string $domain Translation domain (e.g., 'common', 'auth')
     * @return array<string, mixed> Loaded translations as nested array
     * @throws \RuntimeException If translations cannot be loaded
     */
    public function load(string $locale, string $domain): array;

    /**
     * Check if translations exist for locale and domain.
     * 
     * @param string $locale Locale code
     * @param string $domain Translation domain
     * @return bool True if translations exist
     */
    public function exists(string $locale, string $domain): bool;

    /**
     * Get all available domains for a locale.
     * 
     * @param string $locale Locale code
     * @return array<string> Array of available domain names
     */
    public function getAvailableDomains(string $locale): array;

    /**
     * Get all available locales.
     * 
     * @return array<string> Array of available locale codes
     */
    public function getAvailableLocales(): array;

    /**
     * Get the last modification time for translations.
     * 
     * @param string $locale Locale code
     * @param string $domain Translation domain
     * @return int Unix timestamp of last modification
     */
    public function getLastModified(string $locale, string $domain): int;

    /**
     * Preload all translations for a locale.
     * 
     * @param string $locale Locale code
     * @return array<string, array<string, mixed>> All translations keyed by domain
     */
    public function preloadLocale(string $locale): array;

    /**
     * Set the base path for translation files.
     * 
     * @param string $path Base directory path
     */
    public function setBasePath(string $path): void;

    /**
     * Get the base path for translation files.
     * 
     * @return string Base directory path
     */
    public function getBasePath(): string;

    /**
     * Compile translation files for performance optimization.
     * 
     * @param string $locale Locale to compile
     * @param string|null $domain Specific domain or null for all
     * @return bool True if compilation successful
     */
    public function compile(string $locale, ?string $domain = null): bool;

    /**
     * Check if compiled translations are available and up-to-date.
     * 
     * @param string $locale Locale code
     * @param string $domain Translation domain
     * @return bool True if compiled version is current
     */
    public function isCompiled(string $locale, string $domain): bool;
}