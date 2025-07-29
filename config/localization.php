<?php

declare(strict_types=1);

/**
 * Localization Configuration
 * 
 * Configuration settings for the localization/internationalization system.
 * These settings can be overridden by environment variables.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | This is the default locale that will be used when no specific locale
    | is detected or specified. This should be one of the supported locales.
    |
    */
    'default_locale' => env('LOCALIZATION_DEFAULT_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used when a translation is not available in the
    | current locale. This should typically be your primary language.
    |
    */
    'fallback_locale' => env('LOCALIZATION_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | List of all locales supported by your application. Only these locales
    | will be accepted for translation and locale detection.
    |
    */
    'supported_locales' => [
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español', 
        'de' => 'Deutsch',
        'it' => 'Italiano'
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Files Path
    |--------------------------------------------------------------------------
    |
    | The base directory where translation files are stored. This should
    | contain subdirectories for each supported locale.
    |
    */
    'translation_path' => __DIR__ . '/../resources/lang',

    /*
    |--------------------------------------------------------------------------
    | Compiled Translations Path
    |--------------------------------------------------------------------------
    |
    | Directory where compiled translation files are stored for better
    | performance. These are generated from the source JSON files.
    |
    */
    'compiled_path' => __DIR__ . '/../resources/compiled/translations',

    /*
    |--------------------------------------------------------------------------
    | Use Compiled Translations
    |--------------------------------------------------------------------------
    |
    | Whether to use compiled PHP translations for better performance.
    | Disable this during development for easier debugging.
    |
    */
    'use_compiled' => env('LOCALIZATION_USE_COMPILED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Translations
    |--------------------------------------------------------------------------
    |
    | Whether to cache loaded translations in memory during the request.
    | This improves performance but uses more memory.
    |
    */
    'cache_enabled' => env('LOCALIZATION_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Locale Detection Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for how locales are detected from user requests.
    |
    */
    'detection' => [
        /*
        | Priority order for locale detection methods.
        | Available methods: path, session, user, cookie, header
        */
        'priority' => ['path', 'session', 'user', 'cookie', 'header'],

        /*
        | Session key used to store user's locale preference
        */
        'session_key' => env('LOCALIZATION_SESSION_KEY', 'locale'),

        /*
        | Cookie name used to store user's locale preference
        */
        'cookie_name' => env('LOCALIZATION_COOKIE_NAME', 'locale'),

        /*
        | Cookie lifetime in seconds (default: 1 year)
        */
        'cookie_lifetime' => 365 * 24 * 60 * 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for how locales are handled in URLs.
    |
    */
    'url' => [
        /*
        | Whether to include locale in URLs (/en/page vs /page)
        */
        'locale_in_url' => env('LOCALIZATION_LOCALE_IN_URL', true),

        /*
        | Whether to hide the default locale from URLs
        | If true: /page instead of /en/page for default locale
        */
        'hide_default_locale' => env('LOCALIZATION_HIDE_DEFAULT_LOCALE', false),

        /*
        | Redirect behavior when no locale is specified in URL
        | Options: 'detect' (detect and redirect), 'default' (use default), 'none'
        */
        'redirect_behavior' => env('LOCALIZATION_REDIRECT_BEHAVIOR', 'detect'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Domains
    |--------------------------------------------------------------------------
    |
    | Predefined translation domains (files) that should be available.
    | These correspond to JSON files in the translation directories.
    |
    */
    'domains' => [
        'common',      // Common UI elements, buttons, labels
        'auth',        // Authentication and authorization
        'validation',  // Form validation messages
        'messages',    // Success, error, info messages
        'pages',       // Page-specific content
        'emails',      // Email templates
        'admin',       // Admin interface
    ],

    /*
    |--------------------------------------------------------------------------
    | Date and Time Formatting
    |--------------------------------------------------------------------------
    |
    | Locale-specific formatting patterns for dates, times, and numbers.
    |
    */
    'formatting' => [
        'date_format' => [
            'en' => 'M j, Y',
            'fr' => 'j M Y',
            'es' => 'j \d\e M \d\e Y',
            'de' => 'j. M Y',
            'it' => 'j M Y',
        ],
        'time_format' => [
            'en' => 'g:i A',
            'fr' => 'H:i',
            'es' => 'H:i',
            'de' => 'H:i',
            'it' => 'H:i',
        ],
        'currency' => [
            'en' => 'USD',
            'fr' => 'EUR',
            'es' => 'EUR',
            'de' => 'EUR',
            'it' => 'EUR',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pluralization Rules
    |--------------------------------------------------------------------------
    |
    | Language-specific pluralization rules. These determine which plural
    | form to use based on the count value.
    |
    */
    'pluralization' => [
        /*
        | Default pluralization rule (for most languages including English)
        | 0 = zero, 1 = one, >1 = other
        */
        'default' => function (int $count): string {
            if ($count === 0) return 'zero';
            if ($count === 1) return 'one';
            return 'other';
        },

        /*
        | French pluralization (0,1 = one, >1 = other)
        */
        'fr' => function (int $count): string {
            return ($count === 0 || $count === 1) ? 'one' : 'other';
        },

        /*
        | Add more language-specific rules as needed
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings that are useful during development and debugging.
    |
    */
    'development' => [
        /*
        | Log missing translations for debugging
        */
        'log_missing_translations' => env('LOCALIZATION_LOG_MISSING', false),

        /*
        | Highlight missing translations in output (wrap with markers)
        */
        'highlight_missing' => env('LOCALIZATION_HIGHLIGHT_MISSING', false),

        /*
        | Show translation keys instead of values (for debugging)
        */
        'show_keys' => env('LOCALIZATION_SHOW_KEYS', false),

        /*
        | Enable translation statistics collection
        */
        'collect_stats' => env('LOCALIZATION_COLLECT_STATS', false),
    ],
];