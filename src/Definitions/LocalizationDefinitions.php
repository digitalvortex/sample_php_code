<?php

declare(strict_types=1);

namespace App\Definitions;

use App\Core\Container;
use App\Services\LocalizationService;
use App\Services\TranslationLoader;
use App\Services\LocaleDetector;
use App\Middleware\LocaleMiddleware;
use App\Interfaces\LocalizationServiceInterface;
use App\Interfaces\TranslationLoaderInterface;
use App\Interfaces\LocaleDetectorInterface;

/**
 * Class LocalizationDefinitions
 *
 * Provides localization service definitions for dependency injection.
 * Configures the complete localization system with proper dependencies.
 *
 * @package App\Definitions
 */
class LocalizationDefinitions
{
    /**
     * Returns an array of localization service definitions.
     *
     * @return array<string, mixed>
     */
    public static function getDefinitions(): array
    {
        return [
            /**
             * Translation Loader Service
             * Handles loading translation files from JSON with caching support.
             */
            TranslationLoaderInterface::class => function (Container $c) {
                $config = [
                    'base_path' => __DIR__ . '/../../resources/lang',
                    'compiled_path' => __DIR__ . '/../../resources/compiled/translations',
                    'use_compiled' => $_ENV['LOCALIZATION_USE_COMPILED'] ?? true,
                    'cache_enabled' => $_ENV['LOCALIZATION_CACHE_ENABLED'] ?? true,
                ];
                
                return new TranslationLoader($config);
            },

            /**
             * Locale Detector Service
             * Detects user locale from various sources with configurable priority.
             */
            LocaleDetectorInterface::class => function (Container $c) {
                $config = [
                    'default_locale' => $_ENV['LOCALIZATION_DEFAULT_LOCALE'] ?? 'en',
                    'supported_locales' => self::getSupportedLocales(),
                    'session_key' => $_ENV['LOCALIZATION_SESSION_KEY'] ?? 'locale',
                    'cookie_name' => $_ENV['LOCALIZATION_COOKIE_NAME'] ?? 'locale',
                    'detection_priority' => [
                        'path',
                        'session', 
                        'user',
                        'cookie',
                        'header'
                    ]
                ];
                
                return new LocaleDetector($config);
            },

            /**
             * Main Localization Service
             * Core i18n service implementing LocalizationServiceInterface.
             */
            LocalizationServiceInterface::class => function (Container $c) {
                $config = [
                    'default_locale' => $_ENV['LOCALIZATION_DEFAULT_LOCALE'] ?? 'en',
                    'fallback_locale' => $_ENV['LOCALIZATION_FALLBACK_LOCALE'] ?? 'en',
                    'supported_locales' => self::getSupportedLocales(),
                ];
                
                return new LocalizationService(
                    $c->get(TranslationLoaderInterface::class),
                    $c->get(LocaleDetectorInterface::class),
                    $config
                );
            },

            /**
             * Locale Middleware
             * Handles locale detection and setting from request context.
             */
            LocaleMiddleware::class => function (Container $c) {
                return new LocaleMiddleware(
                    $c->get(LocalizationServiceInterface::class),
                    $c->get(LocaleDetectorInterface::class)
                );
            },

            /**
             * Alias for easier access
             */
            'localization' => function (Container $c) {
                return $c->get(LocalizationServiceInterface::class);
            },
        ];
    }

    /**
     * Get supported locales from environment or default configuration.
     * 
     * @return array<string>
     */
    private static function getSupportedLocales(): array
    {
        $envLocales = $_ENV['LOCALIZATION_SUPPORTED_LOCALES'] ?? '';
        
        if (!empty($envLocales)) {
            return array_map('trim', explode(',', $envLocales));
        }
        
        // Default supported locales
        return ['en', 'fr', 'es', 'de', 'it'];
    }

    /**
     * Get localization configuration for debugging.
     * 
     * @return array<string, mixed>
     */
    public static function getConfiguration(): array
    {
        return [
            'default_locale' => $_ENV['LOCALIZATION_DEFAULT_LOCALE'] ?? 'en',
            'fallback_locale' => $_ENV['LOCALIZATION_FALLBACK_LOCALE'] ?? 'en',
            'supported_locales' => self::getSupportedLocales(),
            'use_compiled' => $_ENV['LOCALIZATION_USE_COMPILED'] ?? true,
            'cache_enabled' => $_ENV['LOCALIZATION_CACHE_ENABLED'] ?? true,
            'session_key' => $_ENV['LOCALIZATION_SESSION_KEY'] ?? 'locale',
            'cookie_name' => $_ENV['LOCALIZATION_COOKIE_NAME'] ?? 'locale',
            'translation_path' => __DIR__ . '/../../resources/lang',
            'compiled_path' => __DIR__ . '/../../resources/compiled/translations',
        ];
    }
}