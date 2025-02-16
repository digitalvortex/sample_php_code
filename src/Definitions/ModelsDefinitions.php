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
                return new User($c->get(PDO::class), $c->get(EncryptionService::class));
            },
            Level::class => function (Container $c) {
                return new Level($c->get(PDO::class), $c->get(EncryptionService::class));
            },
            Blog::class => function (Container $c) {
                return new Blog($c->get(PDO::class), $c->get(EncryptionService::class));
            },
            Session::class => function (Container $c) {
                return new Session($c->get(PDO::class), $c->get(EncryptionService::class));
            },
        ];
    }
}