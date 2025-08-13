<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Interfaces\MigrationInterface;
use PDO;

/**
 * User Token Migration
 * 
 * Creates the user_tokens table for password reset tokens, email verification, etc.
 * Includes proper indexing for performance and security.
 */
class UserTokenMigration implements MigrationInterface
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

    public function up(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS user_tokens (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                token_type ENUM('password_reset', 'email_verification', 'remember_me') NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                token_data JSON DEFAULT NULL,
                expires_at TIMESTAMP NOT NULL,
                used_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Indexes for performance
                INDEX idx_user_id (user_id),
                INDEX idx_token_type (token_type),
                INDEX idx_token_hash (token_hash),
                INDEX idx_expires_at (expires_at),
                INDEX idx_used_at (used_at),
                
                -- Composite indexes for common queries
                INDEX idx_user_token_type (user_id, token_type),
                INDEX idx_token_hash_type (token_hash, token_type),
                INDEX idx_active_tokens (token_type, expires_at, used_at),
                
                -- Foreign key constraint to users table
                CONSTRAINT fk_user_tokens_user_id 
                    FOREIGN KEY (user_id) 
                    REFERENCES users(id) 
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->pdo->exec($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS user_tokens";
        $this->pdo->exec($sql);
    }

    public function getDescription(): string
    {
        return 'Create user_tokens table for password reset and verification tokens';
    }
}