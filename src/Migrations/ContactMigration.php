<?php
declare(strict_types=1);

namespace App\Migrations;

use PDO;
use App\Interfaces\MigrationInterface;

/**
 * Class ContactMigration
 *
 * This class handles the creation and deletion of the contacts table in the database.
 *
 * @package App\Migrations
 */
class ContactMigration implements MigrationInterface
{
    /**
     * @var PDO The PDO instance for database connection.
     */
    private PDO $pdo;

    /**
     * ContactMigration constructor.
     *
     * @param PDO $pdo The PDO instance for database connection.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Run the migrations to create the contacts table.
     *
     * @return void
     */
    public function up(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS contacts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX contacts_email_index (email),
            INDEX contacts_created_at_index (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->pdo->exec($sql);
    }

    /**
     * Revert the migrations by dropping the contacts table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS contacts');
    }
}