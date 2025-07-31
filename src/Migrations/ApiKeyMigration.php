<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Interfaces\MigrationInterface;
use PDO;

/**
 * API Key Migration
 * 
 * Creates the api_keys table for secure API key storage and management.
 * Includes proper indexing for performance and security.
 */
class ApiKeyMigration implements MigrationInterface
{
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS api_keys (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                key_hash VARCHAR(255) NOT NULL,
                permissions JSON DEFAULT NULL,
                last_used_at TIMESTAMP NULL DEFAULT NULL,
                expires_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Indexes for performance
                INDEX idx_user_id (user_id),
                INDEX idx_expires_at (expires_at),
                INDEX idx_last_used_at (last_used_at),
                INDEX idx_created_at (created_at),
                
                -- Composite index for common queries
                INDEX idx_user_active (user_id, expires_at),
                
                -- Foreign key constraint to users table
                CONSTRAINT fk_api_keys_user_id 
                    FOREIGN KEY (user_id) 
                    REFERENCES users(id) 
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $pdo->exec($sql);
    }

    public function down(PDO $pdo): void
    {
        $sql = "DROP TABLE IF EXISTS api_keys";
        $pdo->exec($sql);
    }

    public function getDescription(): string
    {
        return 'Create api_keys table for secure API key management';
    }
}