<?php
declare(strict_types=1);

namespace Tests\Models;

use App\Models\User;
use App\Services\EncryptionService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PDO;
use PDOStatement;

#[CoversClass(User::class)]
final class UserTest extends TestCase
{
    private MockObject|PDO $pdo;
    private MockObject|EncryptionService $encryptionService;
    private User $userModel;
    private MockObject|PDOStatement $stmt;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->encryptionService = $this->createMock(EncryptionService::class);
        $this->stmt = $this->createMock(PDOStatement::class);
        $this->userModel = new User($this->pdo, $this->encryptionService);
    }

    #[Test]
    #[TestDox('Can create a new user with valid data')]
    public function testCreateUser(): void
    {
        $userData = [
            'username'   => 'john_doe',
            'email'      => 'john.doe@example.com',
            'password'   => 'securepassword',
            'first_name' => 'John',
            'last_name'  => 'Doe'
        ];

        $this->encryptionService
            ->expects($this->exactly(3))
            ->method('encrypt')
            ->willReturnCallback(fn($value) => "encrypted_{$value}");

        $this->stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);

        // Add expectation for lastInsertId so that a non-zero value is returned.
        $this->pdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('2');

        $result = $this->userModel->createUser($userData);
        $this->assertIsInt($result);
        $this->assertNotSame(0, $result, 'User ID should be non-zero');
    }

    #[Test]
    #[TestDox('Can find a user by ID with decrypted sensitive fields')]
    public function testFindUser(): void
    {
        $userId = 1;
        $userData = [
            'id' => $userId,
            'username' => 'john_doe',
            'email' => 'encrypted_email',
            'first_name' => 'encrypted_first_name',
            'last_name' => 'encrypted_last_name',
            'password' => 'hashed_password'
        ];

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([$userId]);
        
        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn($userData);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with("SELECT * FROM users WHERE id = ?")
            ->willReturn($this->stmt);

        $this->encryptionService
            ->expects($this->exactly(3))
            ->method('decrypt')
            ->willReturnCallback(fn($value) => str_replace('encrypted_', '', $value));

        $result = $this->userModel->findUser($userId);
        
        $this->assertIsArray($result);
        $this->assertEquals('john_doe', $result['username']);
        $this->assertEquals('email', $result['email']);
        $this->assertEquals('first_name', $result['first_name']);
        $this->assertEquals('last_name', $result['last_name']);
    }

    #[Test]
    #[TestDox('Can update existing user with new data')]
    public function testUpdateUser(): void
    {
        $userId = 1;
        $updateData = [
            'email' => 'new.email@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Smith'
        ];

        $currentUser = [
            'id' => $userId,
            'username' => 'john_doe',
            'password' => 'current_hashed_password',
            'email' => 'encrypted_old.email@example.com',
            'first_name' => 'encrypted_John',
            'last_name' => 'encrypted_Doe'
        ];

        $findStmt = $this->createMock(PDOStatement::class);
        $findStmt->expects($this->once())
            ->method('execute')
            ->with([$userId])
            ->willReturn(true);
        
        $findStmt->expects($this->once())
            ->method('fetch')
            ->willReturn($currentUser);

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($findStmt, $this->stmt);

        $this->encryptionService
            ->expects($this->exactly(3))
            ->method('encrypt')
            ->willReturnCallback(fn($value) => "encrypted_{$value}");

        $this->stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $result = $this->userModel->updateUser($userId, $updateData);
        $this->assertTrue($result);
    }

        #[Test]
    #[TestDox('Cannot update user with invalid ID')]
    public function testUpdateUserWithInvalidId(): void
    {
        // Set up the find query to return null for non-existent user
        $findStmt = $this->createMock(PDOStatement::class);
        $findStmt->expects($this->once())
            ->method('execute')
            ->with([999])
            ->willReturn(true);
        
        $findStmt->expects($this->once())
            ->method('fetch')
            ->willReturn(false);
    
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with("SELECT * FROM users WHERE id = ?")
            ->willReturn($findStmt);
    
        // No additional prepare calls should be made
        $this->pdo->expects($this->exactly(1))
            ->method('prepare');
    
        // Test updating non-existent user
        $updateData = ['email' => 'new@example.com'];
        $result = $this->userModel->updateUser(999, $updateData);
        
        // Assert the update failed
        $this->assertFalse($result, 'Update should fail when user does not exist');
    }

    #[Test]
    #[TestDox('Can soft delete a user')]
    public function testSoftDeleteUser(): void
    {
        $userId = 1;
        $timestamp = date('Y-m-d H:i:s');

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with($this->equalTo([
                ':id' => $userId,
                ':deleted_at' => $timestamp
            ]))
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with("UPDATE users SET deleted_at = :deleted_at WHERE id = :id")
            ->willReturn($this->stmt);

        $result = $this->userModel->softDelete($userId);
        $this->assertTrue($result, 'Soft delete should succeed');
    }

    #[Test]
    #[TestDox('Can permanently delete a user')]
    public function testDeleteUser(): void
    {
        $userId = 1;

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => $userId])
            ->willReturn(true);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with("DELETE FROM users WHERE id = :id")
            ->willReturn($this->stmt);

        $result = $this->userModel->delete($userId);
        $this->assertTrue($result);
    }

    #[Test]
    #[TestDox('Can find all deleted users')]
    public function testFindDeletedUsers(): void
    {
        $deletedUsers = [
            [
                'id' => 1,
                'username' => 'deleted_user',
                'email' => 'encrypted_email',
                'first_name' => 'encrypted_John',
                'last_name' => 'encrypted_Doe',
                'deleted_at' => '2025-02-16 10:00:00'
            ]
        ];

        $this->stmt->expects($this->once())
            ->method('execute');

        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn($deletedUsers);

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with("SELECT * FROM users WHERE deleted_at IS NOT NULL")
            ->willReturn($this->stmt);

        $this->encryptionService
            ->expects($this->exactly(3))
            ->method('decrypt')
            ->willReturnCallback(fn($value) => str_replace('encrypted_', '', $value));

        $result = $this->userModel->findDeleted();
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('email', $result[0]['email']);
        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('Doe', $result[0]['last_name']);
    }

    #[Test]
    #[TestDox('Can find all users with decrypted data')]
    public function testFindAllUsers(): void
    {
        $users = [
            [
                'id' => 1,
                'username' => 'john_doe',
                'email' => 'encrypted_email1',
                'first_name' => 'encrypted_John',
                'last_name' => 'encrypted_Doe'
            ],
            [
                'id' => 2,
                'username' => 'jane_doe',
                'email' => 'encrypted_email2',
                'first_name' => 'encrypted_Jane',
                'last_name' => 'encrypted_Doe'
            ]
        ];

        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn($users);

        $this->pdo->expects($this->once())
            ->method('query')
            ->with("SELECT * FROM users")
            ->willReturn($this->stmt);

        $this->encryptionService
            ->expects($this->exactly(6))
            ->method('decrypt')
            ->willReturnCallback(fn($value) => str_replace('encrypted_', '', $value));

        $result = $this->userModel->findAllUsers();
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('email1', $result[0]['email']);
        $this->assertEquals('John', $result[0]['first_name']);
        $this->assertEquals('email2', $result[1]['email']);
        $this->assertEquals('Jane', $result[1]['first_name']);
    }

    #[Test]
    #[TestDox('Cannot create user with missing required fields')]
    public function testCreateUserWithMissingFields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: email');

        $userData = [
            'username' => 'john_doe'
            // Missing required fields email and password
        ];

        $this->userModel->createUser($userData);
    }
}