<?php

declare(strict_types=1);

namespace App\Definitions;

use App\Controllers\{HomeController, AboutController, ContactController, ErrorController, BlogController, ServicesController};
use App\Core\Router;

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
                $router->addRoute('GET', '/', HomeController::class . '@show');
                $router->addRoute('GET', '/about', AboutController::class . '@show');
                $router->addRoute('GET', '/contact', ContactController::class . '@show');
                $router->addRoute('POST', '/contact', ContactController::class . '@submit');
                $router->addRoute('GET', '/services', ServicesController::class . '@show');
                $router->addRoute('GET', '/blog', BlogController::class . '@show');
                $router->addRoute('GET', '/error/404', ErrorController::class . '@notFound');
                $router->addRoute('GET', '/error/500', ErrorController::class . '@internalError');
                return $router;
            },
        ];
    }
}
