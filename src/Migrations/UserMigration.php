<?php
declare(strict_types=1);

namespace App\Migrations;

use PDO;
use App\Interfaces\MigrationInterface;

/**
 * Class UserMigration
 *
 * This class handles the creation and deletion of the users table in the database.
 *
 * @package App\Migrations
 */
class UserMigration implements MigrationInterface
{
    /**
     * PDO instance for database operations
     */
    private PDO $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo The PDO instance
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
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            level_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
            recovery_token VARCHAR(255) NULL,
            recovery_token_created_at TIMESTAMP NULL,
            recovery_expires_at TIMESTAMP NULL,
            reset_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY users_username_unique (username),
            UNIQUE KEY users_email_unique (email),
            INDEX users_level_id_index (level_id),
            INDEX users_created_at_index (created_at),
            INDEX users_deleted_at_index (deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->pdo->exec($sql);
    }

    /**
     * Revert the migrations by dropping the users table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users');
    }
}