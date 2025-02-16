<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Class User
 * 
 * Model representing the user entity. Handles CRUD operations with the database.
 */
class User extends Base
{
    protected string $table = 'users';
    
    protected array $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    
    protected array $encrypted = [
        'email',
        'first_name',
        'last_name'
    ];

    /**
     * Create a new user with password hashing
     *
     * @param array $userData The user data to create
     * @return int Returns the ID of the newly created user
     */
    public function createUser(array $userData): int
    {
        // Validate required fields
        $requiredFields = ['username', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                // You can throw an exception or handle error as needed
                throw new \InvalidArgumentException('Missing required field: ' . $field);
            }
        }

        // Hash password before creation if it exists
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_ARGON2ID);
        }

        // Add creation timestamp
        $userData['created_at'] = date('Y-m-d H:i:s');
        
        return $this->create($userData);
    }

    /**
     * Update an existing user.
     * 
     * @param int $id The ID of the user to update.
     * @param array $data The user data to update.
     * @return bool Returns true on success, false on failure.
     */
    public function updateUser(int $id, array $data): bool
    {
        // First check if user exists
        $currentUser = $this->find($id);
        if (!$currentUser) {
            return false;
        }
    
        // Now we can safely get the current password
        $currentPassword = $currentUser['password'];
    
        // Handle password separately
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        } else {
            $data['password'] = $currentPassword;
        }
    
        // Encrypt sensitive fields if they exist in update data
        foreach ($this->encrypted as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encryptionService->encrypt($data[$field]);
            }
        }
    
        $data['updated_at'] = date('Y-m-d H:i:s');
    
        $setClause = [];
        $params = [':id' => $id];
    
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $setClause[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
    
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Find a user by their ID
     *
     * @param int $id The user ID
     * @return array|null Returns user data or null if not found
     */
    public function findUser(int $id): ?array
    {
        return $this->find($id);
    }

    /**
     * Get all users from the database
     *
     * @return array Returns an array of all users
     */
    public function findAllUsers(): array
    {
        return $this->findAll();
    }

    /**
     * Find all deleted users.
     * 
     * @return array Returns an array of deleted users.
     */
    public function findDeleted(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NOT NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Decrypt sensitive fields for all users
        foreach ($users as &$user) {
            foreach ($this->encrypted as $field) {
                if (isset($user[$field])) {
                    $user[$field] = $this->encryptionService->decrypt($user[$field]);
                }
            }
        }

        return $users;
    }

    /**
     * Soft delete a user by ID.
     * 
     * @param int $id The ID of the user to soft delete.
     * @return bool Returns true on success, false on failure.
     */
    public function softDelete(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET deleted_at = :deleted_at WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id, 
            ':deleted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Delete a user by ID.
     * 
     * @param int $id The ID of the user to delete.
     * @return bool Returns true on success, false on failure.
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
}