<?php
declare(strict_types=1);

namespace App\Definitions;

use App\Core\Container;
use App\Models\User;
use App\Models\Level;
use App\Models\Blog;
use App\Models\Session;
use App\Services\EncryptionService;
use PDO;

class ModelsDefinitions
{
    public static function getDefinitions(): array
    {
        return [
            User::class => function (Container $c) {
                User::initialize($c->get(PDO::class), $c->get(EncryptionService::class));
                return new User();
            },
            Level::class => function (Container $c) {
                Level::initialize($c->get(PDO::class), $c->get(EncryptionService::class));
                return new Level();
            },
            Blog::class => function (Container $c) {
                Blog::initialize($c->get(PDO::class), $c->get(EncryptionService::class));
                return new Blog();
            },
            Session::class => function (Container $c) {
                Session::initialize($c->get(PDO::class), $c->get(EncryptionService::class));
                return new Session();
            },
        ];
    }
}