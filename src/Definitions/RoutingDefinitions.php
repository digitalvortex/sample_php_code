<?php

declare(strict_types=1);

namespace App\Definitions;

use App\Controllers\{HomeController, AboutController, ContactController, ErrorController, BlogController, ServicesController, LanguageController};
use App\Controllers\User\{AuthController, RegisterController};
use App\Core\Router;
use App\Middleware\{LocaleMiddleware, AuthenticationMiddleware, CSRFMiddleware, RateLimitMiddleware};

/**
 * Class RoutingDefinitions
 * 
 * Defines the routing configuration for the application.
 */
class RoutingDefinitions
{
    /**
     * Get the routing definitions.
     *
     * @return array The array of routing definitions.
     */
    public static function getDefinitions(): array
    {
        return [
            Router::class => function ($container) {
                $router = new Router($container);
                
                // Add global middleware
                $localeMiddleware = $container->get(LocaleMiddleware::class);
                $router->addGlobalMiddleware($localeMiddleware);
                
                // Initialize middleware instances
                $rateLimitMiddleware = $container->get(RateLimitMiddleware::class);
                
                // Public routes (no authentication required)
                $router->addRoute('GET', '/', HomeController::class . '@show');
                $router->addRoute('GET', '/about', AboutController::class . '@show');
                $router->addRoute('GET', '/contact', ContactController::class . '@show');
                $router->addRoute('POST', '/contact/submit', ContactController::class . '@submit', [
                    $rateLimitMiddleware,
                    $container->get(CSRFMiddleware::class)
                ]);
                $router->addRoute('GET', '/services', ServicesController::class . '@show');
                $router->addRoute('GET', '/blog', BlogController::class . '@show');
                $router->addRoute('GET', '/error/404', ErrorController::class . '@notFound');
                $router->addRoute('GET', '/error/500', ErrorController::class . '@internalError');
                
                // Authentication routes (public) with rate limiting
                
                $router->addRoute('GET', '/login', AuthController::class . '@showLogin');
                $router->addRoute('POST', '/login', AuthController::class . '@login', [
                    $rateLimitMiddleware,
                    $container->get(CSRFMiddleware::class)
                ]);
                $router->addRoute('GET', '/logout', AuthController::class . '@logout');
                $router->addRoute('GET', '/register', RegisterController::class . '@show');
                $router->addRoute('POST', '/register', RegisterController::class . '@register', [
                    $rateLimitMiddleware,
                    $container->get(CSRFMiddleware::class)
                ]);
                
                // AJAX endpoints for registration
                $router->addRoute('GET', '/api/check-username', RegisterController::class . '@checkUsername');
                $router->addRoute('GET', '/api/check-email', RegisterController::class . '@checkEmail');
                $router->addRoute('POST', '/api/password-strength', RegisterController::class . '@checkPasswordStrength');
                
                // Protected routes (authentication required)
                $authMiddleware = $container->get(AuthenticationMiddleware::class);
                $router->addRoute('GET', '/dashboard', AuthController::class . '@dashboard', [$authMiddleware]);
                $router->addRoute('GET', '/api/session-check', AuthController::class . '@checkSession', [$authMiddleware]);
                
                // Language switching routes
                $router->addRoute('GET', '/language/switch/{locale}', LanguageController::class . '@switch');
                $router->addRoute('GET', '/language/available', LanguageController::class . '@getAvailable');
                return $router;
            },
        ];
    }
}
