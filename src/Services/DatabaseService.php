<?php
declare(strict_types=1);
namespace App\Services;

use PDO;

/**
 * Class DatabaseService
 *
 * This service handles the database connection using PDO.
 *
 * @package App\Services
 */
class DatabaseService
{
    /**
     * @var PDO The PDO instance for database connection.
     */
    private PDO $pdo;

    /**
     * DatabaseService constructor.
     *
     * Initializes the PDO instance with the database connection parameters.
     */
    public function __construct()
    {
        $this->pdo = new PDO(
            'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4',
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }

    /**
     * Get the PDO connection instance.
     *
     * @return PDO The PDO instance.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}