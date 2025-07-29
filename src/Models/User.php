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
    protected static string $table = 'users';
    
    protected static array $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    
    protected static array $encrypted = [
        'email',
        'first_name',
        'last_name'
    ];
    
    protected static array $hidden = [
        'password'
    ];

    /**
     * Create a new user with password hashing
     *
     * @param array $userData The user data to create
     * @return static Returns the newly created user instance
     */
    public static function createUser(array $userData): static
    {
        // Validate required fields
        $requiredFields = ['username', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                throw new \InvalidArgumentException('Missing required field: ' . $field);
            }
        }

        // Hash password before creation if it exists
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_ARGON2ID);
        }

        // Add creation timestamp
        $userData['created_at'] = date('Y-m-d H:i:s');
        
        return static::create($userData);
    }

    /**
     * Update user data with password hashing.
     * 
     * @param array $data The user data to update.
     * @return bool Returns true on success, false on failure.
     */
    public function updateUser(array $data): bool
    {
        // Handle password hashing if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }
    
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->update($data);
    }

    /**
     * Find a user by their ID
     *
     * @param int $id The user ID
     * @return static|null Returns user instance or null if not found
     */
    public static function findUser(int $id): ?static
    {
        return static::find($id);
    }

    /**
     * Get all users from the database
     *
     * @return array<static> Returns an array of all user instances
     */
    public static function findAllUsers(): array
    {
        return static::all();
    }

    /**
     * Find all deleted users.
     * 
     * @return array<static> Returns an array of deleted users.
     */
    public static function findDeleted(): array
    {
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE deleted_at IS NOT NULL";
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $models = [];
        foreach ($results as $result) {
            $models[] = static::newFromAttributes(static::decryptAttributes($result));
        }

        return $models;
    }

    /**
     * Soft delete the user.
     * 
     * @return bool Returns true on success, false on failure.
     */
    public function softDelete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $sql = "UPDATE " . static::getTableName() . " SET deleted_at = :deleted_at WHERE " . static::$primaryKey . " = :id";
        $stmt = static::$pdo->prepare($sql);
        $result = $stmt->execute([
            ':id' => $this->getId(), 
            ':deleted_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
        }
        
        return $result;
    }
    
    /**
     * Check if user password matches.
     * 
     * @param string $password The password to verify
     * @return bool Returns true if password matches
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->getAttribute('password'));
    }
    
    /**
     * Get user's full name.
     * 
     * @return string The user's full name
     */
    public function getFullName(): string
    {
        $firstName = $this->getAttribute('first_name') ?? '';
        $lastName = $this->getAttribute('last_name') ?? '';
        
        return trim($firstName . ' ' . $lastName);
    }
    
    /**
     * Check if user is deleted (soft deleted).
     * 
     * @return bool True if user is soft deleted
     */
    public function isDeleted(): bool
    {
        return $this->getAttribute('deleted_at') !== null;
    }
}