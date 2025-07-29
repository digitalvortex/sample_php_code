<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\LocaleDetectorInterface;
use App\Interfaces\RequestInterface;

/**
 * Class LocaleDetector
 * 
 * Detects user locale from various sources with configurable priority.
 * Supports URL paths, headers, session, cookies, and user preferences.
 * PHP 8.4 compatible with strict typing and comprehensive detection strategies.
 */
class LocaleDetector implements LocaleDetectorInterface
{
    private string $defaultLocale;
    private array $supportedLocales;
    private array $detectionPriority;
    private string $sessionKey;
    private string $cookieName;

    public function __construct(array $config = [])
    {
        $this->defaultLocale = $config['default_locale'] ?? 'en';
        $this->supportedLocales = $config['supported_locales'] ?? ['en'];
        $this->sessionKey = $config['session_key'] ?? 'locale';
        $this->cookieName = $config['cookie_name'] ?? 'locale';
        
        $this->detectionPriority = $config['detection_priority'] ?? [
            'path',
            'session',
            'user',
            'cookie',
            'header'
        ];
        
        $this->validateConfiguration();
    }

    public function detectFromRequest(RequestInterface $request): ?string
    {
        // Try to detect from URL path first
        $path = $request->getPath();
        $locale = $this->detectFromPath($path);
        
        if ($locale !== null) {
            return $locale;
        }
        
        // Try Accept-Language header
        $acceptLanguage = $request->getHeader('Accept-Language') ?? '';
        return $this->detectFromHeader($acceptLanguage);
    }

    public function detectFromPath(string $path): ?string
    {
        // Remove leading slash and get first segment
        $path = ltrim($path, '/');
        $segments = explode('/', $path);
        
        if (empty($segments[0])) {
            return null;
        }
        
        $potentialLocale = $segments[0];
        
        // Validate and normalize
        if ($this->isValidLocaleFormat($potentialLocale)) {
            $normalizedLocale = $this->normalizeLocale($potentialLocale);
            
            if (in_array($normalizedLocale, $this->supportedLocales, true)) {
                return $normalizedLocale;
            }
        }
        
        return null;
    }

    public function detectFromHeader(string $acceptLanguage): ?string
    {
        if (empty($acceptLanguage)) {
            return null;
        }
        
        // Parse Accept-Language header
        $languages = $this->parseAcceptLanguage($acceptLanguage);
        
        // Find best matching supported locale
        foreach ($languages as $language) {
            $normalized = $this->normalizeLocale($language['locale']);
            
            // Exact match
            if (in_array($normalized, $this->supportedLocales, true)) {
                return $normalized;
            }
            
            // Language-only match (e.g., 'en-US' -> 'en')
            $languageOnly = explode('-', $normalized)[0];
            if (in_array($languageOnly, $this->supportedLocales, true)) {
                return $languageOnly;
            }
        }
        
        return null;
    }

    public function detectFromSession(array $session): ?string
    {
        $locale = $session[$this->sessionKey] ?? null;
        
        if ($locale === null || !is_string($locale)) {
            return null;
        }
        
        $normalized = $this->normalizeLocale($locale);
        
        return in_array($normalized, $this->supportedLocales, true) ? $normalized : null;
    }

    public function detectFromUser(mixed $user): ?string
    {
        if ($user === null) {
            return null;
        }
        
        // Try different ways to get locale from user object
        $locale = null;
        
        if (is_object($user)) {
            // Try common property/method names
            if (property_exists($user, 'preferred_locale')) {
                $locale = $user->preferred_locale;
            } elseif (property_exists($user, 'locale')) {
                $locale = $user->locale;
            } elseif (method_exists($user, 'getPreferredLocale')) {
                $locale = $user->getPreferredLocale();
            } elseif (method_exists($user, 'getLocale')) {
                $locale = $user->getLocale();
            }
        } elseif (is_array($user)) {
            $locale = $user['preferred_locale'] ?? $user['locale'] ?? null;
        }
        
        if ($locale === null || !is_string($locale)) {
            return null;
        }
        
        $normalized = $this->normalizeLocale($locale);
        
        return in_array($normalized, $this->supportedLocales, true) ? $normalized : null;
    }

    public function detectFromCookie(array $cookies): ?string
    {
        $locale = $cookies[$this->cookieName] ?? null;
        
        if ($locale === null || !is_string($locale)) {
            return null;
        }
        
        $normalized = $this->normalizeLocale($locale);
        
        return in_array($normalized, $this->supportedLocales, true) ? $normalized : null;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(string $locale): void
    {
        if (!in_array($locale, $this->supportedLocales, true)) {
            throw new \InvalidArgumentException("Default locale must be in supported locales: {$locale}");
        }
        
        $this->defaultLocale = $locale;
    }

    public function setSupportedLocales(array $locales): void
    {
        if (empty($locales)) {
            throw new \InvalidArgumentException('At least one supported locale must be provided');
        }
        
        $this->supportedLocales = array_values(array_unique($locales));
        
        // Ensure default locale is still supported
        if (!in_array($this->defaultLocale, $this->supportedLocales, true)) {
            $this->defaultLocale = $this->supportedLocales[0];
        }
    }

    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }

    public function detectLocale(RequestInterface $request, mixed $user = null): string
    {
        foreach ($this->detectionPriority as $method) {
            $locale = match ($method) {
                'path' => $this->detectFromPath($request->getPath()),
                'session' => $this->detectFromSession($_SESSION ?? []),
                'user' => $this->detectFromUser($user),
                'cookie' => $this->detectFromCookie($_COOKIE ?? []),
                'header' => $this->detectFromHeader($request->getHeader('Accept-Language') ?? ''),
                default => null
            };
            
            if ($locale !== null) {
                return $locale;
            }
        }
        
        return $this->defaultLocale;
    }

    public function setDetectionPriority(array $methods): void
    {
        $validMethods = ['path', 'session', 'user', 'cookie', 'header'];
        
        foreach ($methods as $method) {
            if (!in_array($method, $validMethods, true)) {
                throw new \InvalidArgumentException("Invalid detection method: {$method}");
            }
        }
        
        $this->detectionPriority = $methods;
    }

    public function getDetectionPriority(): array
    {
        return $this->detectionPriority;
    }

    public function isValidLocaleFormat(string $locale): bool
    {
        // Basic validation: language code (2 letters) optionally followed by country code
        return preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $locale) === 1;
    }

    public function normalizeLocale(string $locale): string
    {
        // Convert to lowercase and handle common formats
        $locale = strtolower(trim($locale));
        
        // Handle underscore separators (convert to hyphen)
        $locale = str_replace('_', '-', $locale);
        
        // Extract language part if we have country code
        $parts = explode('-', $locale, 2);
        $language = $parts[0];
        
        // For now, we'll just return the language part
        // In a more sophisticated implementation, you might want to handle
        // regional variants like 'en-US', 'en-GB', etc.
        return $language;
    }

    /**
     * Parse Accept-Language header into array of locales with quality scores.
     */
    private function parseAcceptLanguage(string $acceptLanguage): array
    {
        $languages = [];
        $locales = explode(',', $acceptLanguage);
        
        foreach ($locales as $locale) {
            $locale = trim($locale);
            
            if (empty($locale)) {
                continue;
            }
            
            // Parse quality score (q=0.9)
            $quality = 1.0;
            if (str_contains($locale, ';q=')) {
                [$locale, $qualityStr] = explode(';q=', $locale, 2);
                $quality = (float) $qualityStr;
                $locale = trim($locale);
            }
            
            if ($this->isValidLocaleFormat($locale)) {
                $languages[] = [
                    'locale' => $locale,
                    'quality' => $quality
                ];
            }
        }
        
        // Sort by quality score (highest first)
        usort($languages, fn($a, $b) => $b['quality'] <=> $a['quality']);
        
        return $languages;
    }

    /**
     * Validate detector configuration.
     */
    private function validateConfiguration(): void
    {
        if (empty($this->supportedLocales)) {
            throw new \InvalidArgumentException('At least one supported locale must be configured');
        }
        
        if (!in_array($this->defaultLocale, $this->supportedLocales, true)) {
            throw new \InvalidArgumentException('Default locale must be in supported locales');
        }
        
        if (empty($this->detectionPriority)) {
            throw new \InvalidArgumentException('Detection priority cannot be empty');
        }
    }
}