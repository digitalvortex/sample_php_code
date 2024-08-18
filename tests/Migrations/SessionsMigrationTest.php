<?php
namespace Tests\Migrations;

use App\Config\LoadEnv;
use App\Service\DatabaseService;
use App\Migrations\SessionsMigration;
use PDO;
use PHPUnit\Framework\TestCase;

class SessionsMigrationTest extends TestCase
{
    private PDO $pdo;
    private DatabaseService $databaseService;
    private SessionsMigration $migration;

    protected function setUp(): void
    {
        $env = __DIR__ . '/../../.env';
        if (!file_exists($env)) {
            die('Environment file not found.');
        }

        LoadEnv::load($env);

        $dbName = $_ENV['DB_NAME'] ?? null;
        if (!$dbName || !is_string($dbName) || empty(trim($dbName))) {
            throw new \InvalidArgumentException('Invalid database name in environment variable.');
        }

        $this->databaseService = new DatabaseService();
        $this->pdo = $this->databaseService->getConnection();
        $this->migration = new SessionsMigration($this->databaseService->getConnection());
    }

    public function testUpCreatesSessionsTable(): void
    {
        $this->migration->up();
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute([':tableName' => 'sessions']);
        $this->assertNotFalse($stmt->fetch());
    }

    public function testDownDropsSessionsTable(): void
    {
        $this->migration->down();
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE :tableName");
        $stmt->execute([':tableName' => 'sessions']);
        $this->assertFalse($stmt->fetch());
    }
}