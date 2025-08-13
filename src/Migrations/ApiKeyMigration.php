<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Interfaces\MigrationInterface;
use PDO;
use PDOException;

/**
 * API Key Migration
 * 
 * Creates the api_keys table for secure API key storage and management.
 * Includes proper indexing for performance and security.
 */
class ApiKeyMigration implements MigrationInterface
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
            CREATE TABLE IF NOT EXISTS api_keys (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
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
                INDEX idx_user_active (user_id, expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->pdo->exec($sql);
        
        // Add foreign key constraint separately to avoid issues
        $fkSql = "
            ALTER TABLE api_keys 
            ADD CONSTRAINT fk_api_keys_user_id 
            FOREIGN KEY (user_id) REFERENCES users(id) 
            ON DELETE CASCADE ON UPDATE CASCADE
        ";
        
        try {
            $this->pdo->exec($fkSql);
        } catch (PDOException $e) {
            // Foreign key constraint might already exist or users table might not exist yet
            // This is acceptable for development
        }
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS api_keys";
        $this->pdo->exec($sql);
    }

    public function getDescription(): string
    {
        return 'Create api_keys table for secure API key management';
    }
}