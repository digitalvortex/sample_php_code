<?php
declare(strict_types=1);

namespace App\Definitions;

use App\Core\Container;
use App\Controllers\{HomeController, AboutController, ServicesController, ContactController, ErrorController};

class RoutingDefinitions
{
    public static function getDefinitions(): array
    {
        return [
            '/' => [
                'controller' => 'HomeController',
                'action' => 'indexAction'
            ],
            '/about' => [
                'controller' => 'AboutController',
                'action' => 'indexAction'
            ],
            '/services' => [
                'controller' => 'ServicesController',
                'action' => 'indexAction'
            ],
            '/contact' => [
                'controller' => 'ContactController',
                'action' => 'indexAction'
            ],
            '/error' => [
                'controller' => 'ErrorController',
                'action' => 'indexAction'
            ],
        ];
    }
}