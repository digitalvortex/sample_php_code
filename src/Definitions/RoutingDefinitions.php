<?php

declare(strict_types=1);

namespace App\Definitions;

use App\Controllers\{HomeController, AboutController, ContactController, ErrorController};
use App\Core\Router;

class RoutingDefinitions
{
    public static function getDefinitions(): array
    {
        return [
            Router::class => function ($container) {
                $router = new Router($container);
                $router->addRoute('GET', '/', HomeController::class . '@index');
                $router->addRoute('GET', '/about', AboutController::class . '@show');
                $router->addRoute('GET', '/contact', ContactController::class . '@show');
                $router->addRoute('POST', '/contact', ContactController::class . '@submit');
                $router->addRoute('GET', '/error/404', ErrorController::class . '@notFound');
                $router->addRoute('GET', '/error/500', ErrorController::class . '@internalError');
                return $router;
            },
        ];
    }
}
