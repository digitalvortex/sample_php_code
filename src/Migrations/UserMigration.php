<?php
declare(strict_types=1);

namespace App\Migrations;

use PDO;

/**
 * Class UserMigration
 *
 * This class handles the creation and deletion of the users table in the database.
 *
 * @package App\Migrations
 */
class UserMigration
{
    /**
     * @var PDO The PDO instance for database connection.
     */
    private PDO $pdo;

    /**
     * UserMigration constructor.
     *
     * @param PDO $pdo The PDO instance for database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run the migrations to create the users table.
     *
     * @return void
     */
    public function up(): void
    {
        $this->pdo->exec('CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            password TEXT NOT NULL,
            level INT NOT NULL DEFAULT 1,
            recovery_token TEXT NULL,
            recovery_token_created_at TIMESTAMP NULL,
            recovery_expires_at TIMESTAMP NULL,
            reset_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * Revert the migrations by dropping the users table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->pdo->exec('DROP TABLE users');
    }
}