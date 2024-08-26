<?php
// tests/Seeders/UsersSeederTest.php

declare(strict_types=1);

namespace Tests\Seeders;

use App\Models\User;
use App\Seeders\UserSeeder;
use App\Services\EncryptionService;
use PHPUnit\Framework\TestCase;
use PDO;
use App\Config\LoadEnv;

class UsersSeederTest extends TestCase
{
    private PDO $pdo;
    private User $userModel;
    private UserSeeder $userSeeder;

    protected function setUp(): void
    {
        // Load environment variables
        $env = __DIR__ . '/../../.env';
        LoadEnv::load($env);

        // Get database details from environment variables
        $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'];
        $username = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASSWORD'];
        $charset = $_ENV['DB_CHARSET'];

        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("SET NAMES '$charset'");
        $this->createUsersTable();

        $encryptionService = new EncryptionService();
        $this->userModel = new User($this->pdo, $encryptionService);
        $this->userSeeder = new UserSeeder($this->userModel);
    }

    private function createUsersTable(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username TEXT NOT NULL,
            email TEXT NOT NULL,
            password TEXT NOT NULL,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            deleted_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function testSeed(): void
    {
        $this->userSeeder->seed();

        $stmt = $this->pdo->query('SELECT COUNT(*) as count FROM users');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(0, $result['count'], 'Users should be seeded into the database.');
    }

    protected function tearDown(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS users');
    }
}