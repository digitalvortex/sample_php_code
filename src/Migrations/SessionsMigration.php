<?php
declare(strict_types=1);

namespace App\Migrations;

use PDO;
use App\Interfaces\MigrationInterface;

/**
 * Class SessionsMigration
 *
 * This class handles the creation and deletion of the sessions table in the database.
 *
 * @package App\Migrations
 */
class SessionsMigration implements MigrationInterface
{
    /**
     * @var PDO The PDO instance for database connection.
     */
    private PDO $pdo;

    /**
     * SessionsMigration constructor.
     *
     * @param PDO $pdo The PDO instance for database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run the migrations to create the sessions table.
     *
     * @return void
     */
    public function up(): void
    {
        $sql = <<<SQL
        CREATE TABLE sessions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            payload TEXT NULL,
            last_activity TIMESTAMP NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY sessions_session_id_unique (session_id),
            KEY sessions_user_id_index (user_id),
            KEY sessions_last_activity_index (last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->pdo->exec($sql);
    }

    /**
     * Revert the migrations by dropping the sessions table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS sessions');
    }
}