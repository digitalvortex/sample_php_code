<?php
declare(strict_types=1);

namespace App\Definitions;

use App\Core\Container;
use App\Models\User;
use App\Services\EncryptionService;
use PDO;

class ModelDefinitions
{
    public static function getDefinitions(): array
    {
        return [
            User::class => function (Container $c) {
                return new User($c->get(PDO::class), $c->get(EncryptionService::class));
            },
        ];
    }
}