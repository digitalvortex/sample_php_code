<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface LocaleDetectorInterface
 * 
 * Defines the contract for detecting user locale from various sources.
 * Supports multiple detection strategies with priority ordering.
 * PHP 8.4 compatible with strict typing.
 */
interface LocaleDetectorInterface
{
    /**
     * Detect locale from HTTP request.
     * 
     * @param RequestInterface $request The HTTP request
     * @return string|null Detected locale or null if none found
     */
    public function detectFromRequest(RequestInterface $request): ?string;

    /**
     * Detect locale from URL path.
     * 
     * @param string $path URL path (e.g., '/en/products')
     * @return string|null Detected locale or null if none found
     */
    public function detectFromPath(string $path): ?string;

    /**
     * Detect locale from Accept-Language header.
     * 
     * @param string $acceptLanguage Accept-Language header value
     * @return string|null Best matching locale or null
     */
    public function detectFromHeader(string $acceptLanguage): ?string;

    /**
     * Detect locale from session data.
     * 
     * @param array<string, mixed> $session Session data
     * @return string|null Locale from session or null
     */
    public function detectFromSession(array $session): ?string;

    /**
     * Detect locale from user preferences (if authenticated).
     * 
     * @param mixed $user User object or null if not authenticated
     * @return string|null User's preferred locale or null
     */
    public function detectFromUser(mixed $user): ?string;

    /**
     * Detect locale from cookie.
     * 
     * @param array<string, string> $cookies Cookie data
     * @return string|null Locale from cookie or null
     */
    public function detectFromCookie(array $cookies): ?string;

    /**
     * Get the default locale when no detection succeeds.
     * 
     * @return string Default locale code
     */
    public function getDefaultLocale(): string;

    /**
     * Set the default locale.
     * 
     * @param string $locale Default locale code
     * @throws \InvalidArgumentException If locale is not supported
     */
    public function setDefaultLocale(string $locale): void;

    /**
     * Set supported locales for validation.
     * 
     * @param array<string> $locales Array of supported locale codes
     */
    public function setSupportedLocales(array $locales): void;

    /**
     * Get supported locales.
     * 
     * @return array<string> Array of supported locale codes
     */
    public function getSupportedLocales(): array;

    /**
     * Detect locale using all available methods with priority.
     * 
     * @param RequestInterface $request The HTTP request
     * @param mixed $user User object or null
     * @return string Detected locale (never null, falls back to default)
     */
    public function detectLocale(RequestInterface $request, mixed $user = null): string;

    /**
     * Set detection priority order.
     * 
     * @param array<string> $methods Array of method names in priority order
     */
    public function setDetectionPriority(array $methods): void;

    /**
     * Get current detection priority order.
     * 
     * @return array<string> Method names in priority order
     */
    public function getDetectionPriority(): array;

    /**
     * Validate if a locale code is properly formatted.
     * 
     * @param string $locale Locale code to validate
     * @return bool True if locale format is valid
     */
    public function isValidLocaleFormat(string $locale): bool;

    /**
     * Normalize locale code to consistent format.
     * 
     * @param string $locale Raw locale code
     * @return string Normalized locale code
     */
    public function normalizeLocale(string $locale): string;
}