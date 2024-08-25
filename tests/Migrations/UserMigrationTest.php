<?php
declare(strict_types=1);

namespace Tests\Migrations;

use App\Config\LoadEnv;
use App\Migrations\UserMigration;
use App\Services\DatabaseService;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Class UserMigrationTest
 *
 * This class tests the UserMigration class.
 *
 * @package Tests\Migrations
 */
class UserMigrationTest extends TestCase
{
    /**
     * @var DatabaseService The DatabaseService instance.
     */
    private DatabaseService $databaseService;

    /**
     * @var UserMigration The UserMigration instance.
     */
    private UserMigration $migration;

    /**
     * @var string The database name.
     */
    private string $dbName;

    /**
     * @var PDO The PDO instance.
     */
    private PDO $pdo;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $env = __DIR__ . '/../../.env';
        if (!file_exists($env)) {
            die("Environment file not found.");
        }

        LoadEnv::load('.env');

        $this->dbName = $_ENV['DB_NAME'];
        if (!$this->dbName || !is_string($this->dbName) || empty(trim($this->dbName))) {
            throw new \InvalidArgumentException('Invalid database name in environment variable.');
        }

        $this->databaseService = new DatabaseService();
        $this->pdo = $this->databaseService->getConnection();
        $this->migration = new UserMigration($this->databaseService->getConnection());

    }

    /**
     * Test that the up method creates the users table.
     *
     * @return void
     */
    public function testUpCreatesUsersTable(): void
    {
        $this->migration->up();
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute([':tableName' => 'users']);
        $this->assertNotFalse($stmt->fetch());
    }

    /**
     * Test that the down method drops the users table.
     *
     * @return void
     */
    public function testDownDropsUsersTable(): void
    {
        $this->migration->down();
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute([':tableName' => 'users']);
        $this->assertFalse($stmt->fetch());
    }
}