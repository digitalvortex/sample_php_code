<?php
declare(strict_types=1);

namespace Tests\Service;

use App\Config\LoadEnv;
use App\Service\DatabaseService;
use Attribute;
use PHPUnit\Framework\TestCase;


class DatabaseServiceTest extends TestCase
{
    private DatabaseService $databaseService;

    public function setUp(): void
    {
        $env = __DIR__ . '/../../.env';
        LoadEnv::load($env);

        $this->databaseService = new DatabaseService();
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(DatabaseService::class));
    }

    public function testClassMethodConstructorExists(): void
    {
        $this->assertTrue(method_exists(DatabaseService::class, '__construct'));
    }

    // test for getConnection exists
    public function testClassMethodgetConnectionExists(): void
    {
        $this->assertTrue(method_exists(DatabaseService::class, 'getConnection'));
    }

    // check for attribute PDO exists
    public function testClassAttributeExists(): void
    {
        $this->assertTrue(property_exists(DatabaseService::class, 'pdo'));
    }

    // check for attribute PDO is private
    public function testClassAttributeIsPrivate(): void
    {
        $reflection = new \ReflectionClass(DatabaseService::class);
        $attribute = $reflection->getProperty('pdo');
        $attribute->setAccessible(true);

        $this->assertTrue($attribute->isPrivate());
    }

    public function testPDOConnection(): void
    {
        $pdo = $this->databaseService->getConnection();
        $this->assertInstanceOf(\PDO::class, $pdo, 'Connection should be an instance of PDO');

        //simple query
        $stmt = $pdo->query('SELECT 1');
        $this->assertNotFalse($stmt, 'Query should not be false');
    }
}