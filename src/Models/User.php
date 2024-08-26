<?php
declare(strict_types=1);

namespace App\Models;

use App\Services\EncryptionService;
use PDO;

/**
 * Class User
 * 
 * Model representing the user entity. Handles CRUD operations with the database.
 */
class User
{
    private PDO $pdo;
    private EncryptionService $encryptionService;

    /**
     * User constructor.
     * 
     * @param PDO $pdo Database connection.
     * @param EncryptionService $encryptionService Service for encrypting/decrypting sensitive data.
     */
    public function __construct(PDO $pdo, EncryptionService $encryptionService)
    {
        $this->pdo = $pdo;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Create a new user.
     * 
     * @param array $data User data to insert.
     * @return bool Returns true on success, false on failure.
     */
    public function create(array $data): bool
    {
        $sql = 'INSERT INTO users (username, email, password, first_name, last_name, created_at) 
                VALUES (:username, :email, :password, :first_name, :last_name, :created_at)';
        $stmt = $this->pdo->prepare($sql);

        /**
         * Encrypt sensitive data
         * - Only if it's being inserted
         * @param array $data
         * @return array $data
         */
        $data['email'] = $this->encryptionService->encrypt($data['email']);
        $data['first_name'] = $this->encryptionService->encrypt($data['first_name']);
        $data['last_name'] = $this->encryptionService->encrypt($data['last_name']);

        return $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Find a user by ID.
     * 
     * @param int $id The ID of the user to find.
     * @return array|null Returns the user data or null if not found.
     */
    public function find(int $id): ?array
    {
        $sql = 'SELECT * FROM users WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $user['email'] = $this->encryptionService->decrypt($user['email']);
            $user['first_name'] = $this->encryptionService->decrypt($user['first_name']);
            $user['last_name'] = $this->encryptionService->decrypt($user['last_name']);
        }

        return $user ?: null;
    }

    /**
     * Update an existing user.
     * 
     * @param int $id The ID of the user to update.
     * @param array $data The user data to update.
     * @return bool Returns true on success, false on failure.
     */
    public function update(int $id, array $data): bool
    {
        // Fetch current password if it's not being updated
        $currentUser = $this->find($id);
        $currentPassword = $currentUser['password'];

        $sql = 'UPDATE users 
                SET username = :username, email = :email, password = :password, 
                    first_name = :first_name, last_name = :last_name, updated_at = :updated_at 
                WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);

        /** Encrypt sensitive data
         * - Only if it's being updated
         */
        if (isset($data['email'])) {
            $data['email'] = $this->encryptionService->encrypt($data['email']);
        }
        if (isset($data['first_name'])) {
            $data['first_name'] = $this->encryptionService->encrypt($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $data['last_name'] = $this->encryptionService->encrypt($data['last_name']);
        }

        return $stmt->execute([
            ':id' => $id,
            ':username' => $data['username'],
            ':email' => $data['email'] ?? $currentUser['email'],
            ':password' => isset($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : $currentPassword,
            ':first_name' => $data['first_name'] ?? $currentUser['first_name'],
            ':last_name' => $data['last_name'] ?? $currentUser['last_name'],
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Find all deleted users.
     * 
     * @return array Returns an array of deleted users.
     */
    public function findDeleted(): array
    {
        $sql = 'SELECT * FROM users WHERE deleted_at IS NOT NULL';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Soft delete a user by ID.
     * 
     * @param int $id The ID of the user to soft delete.
     * @return bool Returns true on success, false on failure.
     */
    public function softDelete(int $id): bool
    {
        $sql = 'UPDATE users SET deleted_at = :deleted_at WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id, ':deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Delete a user by ID.
     * 
     * @param int $id The ID of the user to delete.
     * @return bool Returns true on success, false on failure.
     */
    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM users WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}
