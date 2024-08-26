<?php
namespace Tests\Models;

use App\Models\User;
use App\Services\EncryptionService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PDO;
use PDOStatement;

class UserTest extends TestCase
{
    /** @var MockObject|PDO */
    private $pdo;

    /** @var User */
    private $userModel;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $encryptionService = $this->createMock(EncryptionService::class);
        $this->userModel = new User($this->pdo, $encryptionService);
    }

    public function testCreateUser(): void
    {
        $userData = [
            'username' => 'john_doe',
            'email' => 'john.doe@example.com',
            'password' => 'securepassword',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->willReturn(true);

        $this->pdo->expects($this->once())
                  ->method('prepare')
                  ->willReturn($stmt);

        $result = $this->userModel->create($userData);
        $this->assertTrue($result, 'User should be created successfully.');
    }

    public function testSoftDeleteUser(): void
    {
        $userId = 1;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->willReturn(true);

        $this->pdo->expects($this->once())
                  ->method('prepare')
                  ->willReturn($stmt);

        $result = $this->userModel->softDelete($userId);
        $this->assertTrue($result, 'User should be soft deleted successfully.');
    }

    public function testDeleteUser(): void
    {
        $userId = 1;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->willReturn(true);

        $this->pdo->expects($this->once())
                  ->method('prepare')
                  ->willReturn($stmt);

        $result = $this->userModel->delete($userId);
        $this->assertTrue($result, 'User should be deleted successfully.');
    }
}