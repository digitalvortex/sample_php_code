<?php
declare(strict_types=1);

namespace App\Migrations;

use PDO;
use App\Interfaces\MigrationInterface;

/**
 * Class BlogMigration
 *
 * This class handles the creation and deletion of the blogs table in the database.
 *
 * @package App\Migrations
 */
class BlogMigration implements MigrationInterface
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
     * Run the migrations to create the blogs table.
     *
     * @return void
     */
    public function up(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS blogs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT NULL,
            author_id BIGINT UNSIGNED NULL,
            status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
            featured BOOLEAN NOT NULL DEFAULT FALSE,
            meta_title VARCHAR(255) NULL,
            meta_description TEXT NULL,
            published_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY blogs_slug_unique (slug),
            INDEX blogs_author_id_index (author_id),
            INDEX blogs_status_index (status),
            INDEX blogs_published_at_index (published_at),
            INDEX blogs_created_at_index (created_at),
            INDEX blogs_featured_index (featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->pdo->exec($sql);
    }

    /**
     * Revert the migrations by dropping the blogs table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS blogs');
    }
}