<?php
declare(strict_types=1);

namespace App\Migrations;

use App\Interfaces\MigrationInterface;
use PDO;

/**
 * JWT Blacklist Migration
 * 
 * Creates the jwt_blacklist table for storing revoked JWT tokens.
 * PHP 8.4 compatible with strict typing.
 */
class JwtBlacklistMigration implements MigrationInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run the migration to create jwt_blacklist table.
     */
    public function up(): void
    {
        $sql = "
        CREATE TABLE jwt_blacklist (
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            jti VARCHAR(255) NOT NULL UNIQUE,
            user_id BIGINT UNSIGNED NOT NULL,
            expires_at DATETIME NOT NULL,
            blacklisted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            reason VARCHAR(255) NULL,
            INDEX idx_jti (jti),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->pdo->exec($sql);
        echo "Created jwt_blacklist table.\n";
    }

    /**
     * Reverse the migration (drop jwt_blacklist table).
     */
    public function down(): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS jwt_blacklist");
        echo "Dropped jwt_blacklist table.\n";
    }
}