<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Interfaces\MiddlewareInterface;
use App\Interfaces\RequestInterface;
use App\Interfaces\ResponseInterface;
use App\Interfaces\LocalizationServiceInterface;
use App\Interfaces\LocaleDetectorInterface;

/**
 * Locale Middleware
 * 
 * Detects and sets the application locale for each request.
 * Integrates with the existing middleware pipeline and request/response system.
 * PHP 8.4 compatible with priority-based execution.
 */
class LocaleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LocalizationServiceInterface $localizationService,
        private LocaleDetectorInterface $localeDetector
    ) {}

    public function handle(RequestInterface $request, callable $next): ResponseInterface
    {
        // Detect locale from the request
        $detectedLocale = $this->localeDetector->detectLocale($request, $this->getCurrentUser($request));
        
        // Set the locale in the localization service
        $this->localizationService->setLocale($detectedLocale);
        
        // Store locale information in the request for other components
        $this->addLocaleToRequest($request, $detectedLocale);
        
        // Store locale in session for persistence
        $this->persistLocaleInSession($detectedLocale);
        
        // Process the request
        $response = $next($request);
        
        // Add locale information to response headers (optional)
        $this->addLocaleHeaders($response, $detectedLocale);
        
        return $response;
    }

    public function getPriority(): int
    {
        // Priority 200 - after authentication but before route-specific middleware
        return 200;
    }

    public function getName(): string
    {
        return 'locale';
    }

    public function shouldApply(RequestInterface $request): bool
    {
        // Apply to all requests except static assets
        $path = $request->getPath();
        $staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2', '.ttf'];
        
        foreach ($staticExtensions as $extension) {
            if (str_ends_with($path, $extension)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get current user from request (if available).
     */
    private function getCurrentUser(RequestInterface $request): mixed
    {
        // Try to get user from request attributes
        if (method_exists($request, 'getAttribute')) {
            $user = $request->getAttribute('user');
            if ($user !== null) {
                return $user;
            }
        }
        
        // Try to get user from session
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        
        // Try to get user ID from session and load user (simplified)
        if (isset($_SESSION['user_id'])) {
            // In a real application, you would load the user from database
            // For now, return a simple array with user_id
            return ['id' => $_SESSION['user_id']];
        }
        
        return null;
    }

    /**
     * Add locale information to the request.
     */
    private function addLocaleToRequest(RequestInterface $request, string $locale): void
    {
        // If request supports attributes, add locale info
        if (method_exists($request, 'setAttribute')) {
            $request->setAttribute('locale', $locale);
            $request->setAttribute('locale_source', $this->getLocaleSource($request, $locale));
        }
    }

    /**
     * Persist locale in session for future requests.
     */
    private function persistLocaleInSession(string $locale): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['locale'] = $locale;
        $_SESSION['locale_set_at'] = time();
    }

    /**
     * Add locale headers to response.
     */
    private function addLocaleHeaders(ResponseInterface $response, string $locale): void
    {
        // Add Content-Language header
        $response->setHeader('Content-Language', $locale);
        
        // Add custom header for debugging (remove in production)
        if ($this->isDebugMode()) {
            $response->setHeader('X-Detected-Locale', $locale);
            $response->setHeader('X-Supported-Locales', implode(',', $this->localizationService->getSupportedLocales()));
        }
    }

    /**
     * Determine the source of locale detection for debugging.
     */
    private function getLocaleSource(RequestInterface $request, string $detectedLocale): string
    {
        // Check various sources to determine how locale was detected
        
        // Check URL path
        if ($this->localeDetector->detectFromPath($request->getPath()) === $detectedLocale) {
            return 'url_path';
        }
        
        // Check session
        if (isset($_SESSION['locale']) && $_SESSION['locale'] === $detectedLocale) {
            return 'session';
        }
        
        // Check cookie
        if (isset($_COOKIE['locale']) && $_COOKIE['locale'] === $detectedLocale) {
            return 'cookie';
        }
        
        // Check Accept-Language header
        $headerLocale = $this->localeDetector->detectFromHeader($request->getHeader('Accept-Language') ?? '');
        if ($headerLocale === $detectedLocale) {
            return 'accept_language_header';
        }
        
        // Check user preference
        $user = $this->getCurrentUser($request);
        if ($this->localeDetector->detectFromUser($user) === $detectedLocale) {
            return 'user_preference';
        }
        
        // Default locale
        if ($detectedLocale === $this->localeDetector->getDefaultLocale()) {
            return 'default';
        }
        
        return 'unknown';
    }

    /**
     * Check if debug mode is enabled.
     */
    private function isDebugMode(): bool
    {
        return (bool) ($_ENV['DEBUG'] ?? false);
    }
}