<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;
use App\Interfaces\LocalizationServiceInterface;

/**
 * Class LanguageController
 *
 * Handles language switching functionality for the application.
 * Provides endpoints for changing user locale preferences.
 *
 * @package App\Controllers
 */
class LanguageController extends Controller
{
    private LocalizationServiceInterface $localizationService;

    public function __construct(LocalizationServiceInterface $localizationService)
    {
        $this->localizationService = $localizationService;
    }

    /**
     * Switch the application language.
     * Sets the locale in session and cookie for persistence.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, mixed> $params
     * @return ResponseInterface
     */
    public function switch(RequestInterface $request, ResponseInterface $response, array $params): ResponseInterface
    {
        $locale = $params['locale'] ?? null;
        
        if (!$locale || !$this->localizationService->isLocaleSupported($locale)) {
            return $response
                ->withStatus(404)
                ->withJson([
                    'success' => false,
                    'message' => 'Unsupported locale'
                ]);
        }

        // Set the locale in the localization service
        $this->localizationService->setLocale($locale);

        // Store locale preference in session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['locale'] = $locale;

        // Set locale cookie for persistence (1 year)
        $cookieName = $_ENV['LOCALIZATION_COOKIE_NAME'] ?? 'locale';
        $cookieLifetime = 365 * 24 * 60 * 60; // 1 year
        
        setcookie(
            $cookieName,
            $locale,
            time() + $cookieLifetime,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );

        // Get the referrer URL for redirect
        $referrer = $_SERVER['HTTP_REFERER'] ?? '/';
        
        // Remove existing locale from URL if present
        $referrer = $this->removeLocaleFromUrl($referrer);
        
        // Add new locale to URL if configured to show in URLs
        $useLocaleInUrl = $_ENV['LOCALIZATION_LOCALE_IN_URL'] ?? true;
        $hideDefaultLocale = $_ENV['LOCALIZATION_HIDE_DEFAULT_LOCALE'] ?? false;
        $defaultLocale = $_ENV['LOCALIZATION_DEFAULT_LOCALE'] ?? 'en';
        
        if ($useLocaleInUrl && !($hideDefaultLocale && $locale === $defaultLocale)) {
            $referrer = $this->addLocaleToUrl($referrer, $locale);
        }

        // For AJAX requests, return JSON response
        if ($request->isAjax()) {
            return $response->withJson([
                'success' => true,
                'locale' => $locale,
                'redirect_url' => $referrer,
                'message' => trans('common.language') . ' changed to ' . $this->getLocaleName($locale)
            ]);
        }

        // For regular requests, redirect
        return $response->withRedirect($referrer);
    }

    /**
     * Get available languages for the language selector.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getAvailable(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $supportedLocales = $this->localizationService->getSupportedLocales();
        $currentLocale = $this->localizationService->getLocale();
        
        $languages = [];
        foreach ($supportedLocales as $locale) {
            $languages[] = [
                'code' => $locale,
                'name' => $this->getLocaleName($locale),
                'native_name' => $this->getNativeLocaleName($locale),
                'active' => $locale === $currentLocale
            ];
        }

        return $response->withJson([
            'success' => true,
            'current_locale' => $currentLocale,
            'languages' => $languages
        ]);
    }

    /**
     * Remove locale prefix from URL.
     *
     * @param string $url
     * @return string
     */
    private function removeLocaleFromUrl(string $url): string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '/';
        
        // Match locale patterns like /en/, /fr/, /en, /fr (with or without trailing slash)
        $supportedLocales = $this->localizationService->getSupportedLocales();
        $localePattern = '/^\/(' . implode('|', $supportedLocales) . ')(?:\/(.*))?$/';
        
        if (preg_match($localePattern, $path, $matches)) {
            // If there's a path after the locale, use it; otherwise use root
            $path = isset($matches[2]) && $matches[2] !== '' ? '/' . $matches[2] : '/';
        }
        
        // Rebuild URL
        $result = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? 'localhost');
        if (isset($parsedUrl['port'])) {
            $result .= ':' . $parsedUrl['port'];
        }
        $result .= $path;
        if (isset($parsedUrl['query'])) {
            $result .= '?' . $parsedUrl['query'];
        }
        if (isset($parsedUrl['fragment'])) {
            $result .= '#' . $parsedUrl['fragment'];
        }
        
        return $result;
    }

    /**
     * Add locale prefix to URL.
     *
     * @param string $url
     * @param string $locale
     * @return string
     */
    private function addLocaleToUrl(string $url, string $locale): string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '/';
        
        // First remove any existing locale to prevent double prefixes
        $supportedLocales = $this->localizationService->getSupportedLocales();
        $localePattern = '/^\/(' . implode('|', $supportedLocales) . ')(?:\/(.*))?$/';
        
        if (preg_match($localePattern, $path, $matches)) {
            // If there's a path after the existing locale, use it; otherwise use root
            $path = isset($matches[2]) && $matches[2] !== '' ? '/' . $matches[2] : '/';
        }
        
        // Add new locale prefix
        $path = '/' . $locale . ($path === '/' ? '' : $path);
        
        // Rebuild URL
        $result = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? 'localhost');
        if (isset($parsedUrl['port'])) {
            $result .= ':' . $parsedUrl['port'];
        }
        $result .= $path;
        if (isset($parsedUrl['query'])) {
            $result .= '?' . $parsedUrl['query'];
        }
        if (isset($parsedUrl['fragment'])) {
            $result .= '#' . $parsedUrl['fragment'];
        }
        
        return $result;
    }

    /**
     * Get the English name of a locale.
     *
     * @param string $locale
     * @return string
     */
    private function getLocaleName(string $locale): string
    {
        $names = [
            'en' => 'English',
            'fr' => 'French',
            'es' => 'Spanish', 
            'de' => 'German',
            'it' => 'Italian'
        ];

        return $names[$locale] ?? ucfirst($locale);
    }

    /**
     * Get the native name of a locale.
     *
     * @param string $locale
     * @return string
     */
    private function getNativeLocaleName(string $locale): string
    {
        $names = [
            'en' => 'English',
            'fr' => 'Français',
            'es' => 'Español',
            'de' => 'Deutsch', 
            'it' => 'Italiano'
        ];

        return $names[$locale] ?? ucfirst($locale);
    }

    /**
     * Initialize the controller.
     */
    public function initialize(): void
    {
        // Language controller initialization
    }

    /**
     * Get the controller name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'LanguageController';
    }

    /**
     * Set view data for the controller.
     *
     * @param array<string, mixed> $data
     */
    public function setViewData(array $data): void
    {
        $this->viewData = array_merge($this->viewData, $data);
    }

    /**
     * Get view data from the controller.
     *
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        return $this->viewData;
    }
}