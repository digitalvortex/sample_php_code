<?php
declare(strict_types=1);

namespace App\Definitions;

use App\Core\Container;
use App\Interfaces\DatabaseDefinitionsInterface;
use PDO;

/**
 * Class DatabaseDefinitions
 *
 * Provides database connection definitions for dependency injection.
 *
 * @package App\Definitions
 */
class DatabaseDefinitions implements DatabaseDefinitionsInterface
{
    /**
     * Returns an array of database connection definitions.
     *
     * @return array
     * @throws \Exception If the database configuration is incomplete.
     */
    public static function getDefinitions(): array
    {
        return [
            /**
             * Defines a PDO instance.
             *
             * @param Container $c The container instance.
             * @return PDO The PDO instance.
             * @throws \Exception If the database configuration is incomplete.
             */
            PDO::class => function (Container $c) {
                $host = $_ENV['DB_HOST'] ?? null;
                $db   = $_ENV['DB_NAME'] ?? null;
                $user = $_ENV['DB_USER'] ?? null;
                $pass = $_ENV['DB_PASSWORD'] ?? null;
                $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

                if (!$host || !$db || !$user || !$pass || !$charset) {
                    throw new \Exception("Database configuration is incomplete.");
                }

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                return new PDO($dsn, $user, $pass, $options);
            },
        ];
    }
}