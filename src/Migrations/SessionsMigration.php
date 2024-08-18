<?php
declare(strict_types=1);

namespace App\Migrations;

use PDO;

/**
 * Class SessionsMigration
 *
 * This class handles the creation and deletion of the sessions table in the database.
 *
 * @package App\Migrations
 */
class SessionsMigration
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
        $this->pdo->exec('CREATE TABLE sessions (
            session_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_key TEXT NOT NULL,
            value TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    /**
     * Revert the migrations by dropping the sessions table.
     *
     * @return void
     */
    public function down(): void
    {
        $this->pdo->exec('DROP TABLE sessions');
    }
}