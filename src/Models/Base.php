<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Services\EncryptionService;
use App\Interfaces\ModelInterface;

/**
 * Abstract Base Class
 * 
 * Provides core database operations with encryption support.
 * PHP 8.4 compatible implementing ModelInterface.
 */
abstract class Base implements ModelInterface
{
    protected static PDO $pdo;
    protected static EncryptionService $encryptionService;
    protected static string $table = '';
    protected static array $fillable = [];
    protected static array $encrypted = [];
    protected static array $hidden = [];
    protected static string $primaryKey = 'id';
    
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }
    
    /**
     * Initialize static dependencies.
     */
    public static function initialize(PDO $pdo, EncryptionService $encryptionService): void
    {
        static::$pdo = $pdo;
        static::$encryptionService = $encryptionService;
    }
    
    /**
     * Fill model with data.
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable) || $key === static::$primaryKey) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Create a new record.
     */
    public static function create(array $data): static
    {
        $instance = new static($data);
        $instance->save();
        return $instance;
    }
    
    /**
     * Save the model to the database.
     */
    public function save(): bool
    {
        if ($this->exists) {
            return $this->performUpdate();
        } else {
            return $this->performInsert();
        }
    }
    
    /**
     * Perform insert operation.
     */
    protected function performInsert(): bool
    {
        $fields = array_intersect_key($this->attributes, array_flip(static::$fillable));

        // Encrypt fields that should be encrypted
        foreach (static::$encrypted as $field) {
            if (isset($fields[$field])) {
                $fields[$field] = static::$encryptionService->encrypt($fields[$field]);
            }
        }

        $columns = implode(', ', array_keys($fields));
        $values = implode(', ', array_fill(0, count($fields), '?'));

        $sql = "INSERT INTO " . static::getTableName() . " ($columns) VALUES ($values)";
        $stmt = static::$pdo->prepare($sql);
        
        if ($stmt->execute(array_values($fields))) {
            $this->attributes[static::$primaryKey] = (int)static::$pdo->lastInsertId();
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }
        
        return false;
    }

    /**
     * Find a record by ID.
     */
    public static function find(mixed $id): ?static
    {
        $stmt = static::$pdo->prepare("SELECT * FROM " . static::getTableName() . " WHERE " . static::$primaryKey . " = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return static::newFromAttributes(static::decryptAttributes($result));
        }

        return null;
    }
    
    /**
     * Find a record by ID or throw exception.
     */
    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);
        
        if ($model === null) {
            throw new \RuntimeException('Model not found with ID: ' . $id);
        }
        
        return $model;
    }

    /**
     * Update the model with new data.
     */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }
    
    /**
     * Perform update operation.
     */
    protected function performUpdate(): bool
    {
        $dirty = $this->getDirty();
        
        if (empty($dirty)) {
            return true; // No changes to update
        }
        
        // Encrypt fields that should be encrypted
        foreach (static::$encrypted as $field) {
            if (isset($dirty[$field])) {
                $dirty[$field] = static::$encryptionService->encrypt($dirty[$field]);
            }
        }

        $setClause = [];
        $params = [':' . static::$primaryKey => $this->getId()];

        foreach ($dirty as $field => $value) {
            $setClause[] = "$field = :$field";
            $params[":$field"] = $value;
        }

        // Add updated_at timestamp if it exists in fillable
        if (in_array('updated_at', static::$fillable)) {
            $setClause[] = "updated_at = CURRENT_TIMESTAMP";
        }

        $sql = "UPDATE " . static::getTableName() . " SET " . implode(', ', $setClause) . " WHERE " . static::$primaryKey . " = :" . static::$primaryKey;
        $stmt = static::$pdo->prepare($sql);
        
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->syncOriginal();
        }
        
        return $result;
    }

    /**
     * Delete the model from the database.
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $sql = "DELETE FROM " . static::getTableName() . " WHERE " . static::$primaryKey . " = :id";
        $stmt = static::$pdo->prepare($sql);
        $result = $stmt->execute([':id' => $this->getId()]);
        
        if ($result) {
            $this->exists = false;
        }
        
        return $result;
    }

    /**
     * Get all records.
     */
    public static function all(): array
    {
        $stmt = static::$pdo->query("SELECT * FROM " . static::getTableName());
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $models = [];
        foreach ($results as $result) {
            $models[] = static::newFromAttributes(static::decryptAttributes($result));
        }

        return $models;
    }

    /**
     * Find records matching criteria.
     */
    public static function findBy(array $criteria): array
    {
        $whereClause = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            $whereClause[] = "$field = :$field";
            $params[":$field"] = $value;
        }
        
        $sql = "SELECT * FROM " . static::getTableName() . " WHERE " . implode(' AND ', $whereClause);
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $models = [];
        foreach ($results as $result) {
            $models[] = static::newFromAttributes(static::decryptAttributes($result));
        }

        return $models;
    }
    
    /**
     * Find first record matching criteria.
     */
    public static function findOneBy(array $criteria): ?static
    {
        $results = static::findBy($criteria);
        return $results[0] ?? null;
    }

    /**
     * Count total records
     */
    public static function count(): int
    {
        $stmt = static::$pdo->query("SELECT COUNT(*) FROM " . static::getTableName());
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Get the model's primary key value.
     */
    public function getId(): mixed
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }
    
    /**
     * Get the table name for this model.
     */
    public static function getTableName(): string
    {
        return static::$table;
    }
    
    /**
     * Get the primary key column name.
     */
    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }
    
    /**
     * Get fillable attributes.
     */
    public static function getFillable(): array
    {
        return static::$fillable;
    }
    
    /**
     * Get hidden attributes.
     */
    public static function getHidden(): array
    {
        return static::$hidden;
    }
    
    /**
     * Convert model to array.
     */
    public function toArray(): array
    {
        $array = $this->attributes;
        
        // Remove hidden attributes
        foreach (static::$hidden as $hidden) {
            unset($array[$hidden]);
        }
        
        return $array;
    }
    
    /**
     * Convert model to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Check if model exists in database.
     */
    public function exists(): bool
    {
        return $this->exists;
    }
    
    /**
     * Check if model is new.
     */
    public function isNew(): bool
    {
        return !$this->exists;
    }
    
    /**
     * Refresh model data from database.
     */
    public function refresh(): static
    {
        if (!$this->exists) {
            throw new \RuntimeException('Cannot refresh a model that does not exist in the database');
        }
        
        $fresh = static::find($this->getId());
        
        if ($fresh === null) {
            throw new \RuntimeException('Model no longer exists in the database');
        }
        
        $this->attributes = $fresh->attributes;
        $this->original = $fresh->original;
        
        return $this;
    }
    
    /**
     * Create new instance from database attributes.
     */
    protected static function newFromAttributes(array $attributes): static
    {
        $instance = new static();
        $instance->attributes = $attributes;
        $instance->original = $attributes;
        $instance->exists = true;
        
        return $instance;
    }
    
    /**
     * Decrypt encrypted attributes.
     */
    protected static function decryptAttributes(array $attributes): array
    {
        foreach (static::$encrypted as $field) {
            if (isset($attributes[$field])) {
                $attributes[$field] = static::$encryptionService->decrypt($attributes[$field]);
            }
        }
        
        return $attributes;
    }
    
    /**
     * Get dirty attributes (changed since last sync).
     */
    protected function getDirty(): array
    {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                if (in_array($key, static::$fillable)) {
                    $dirty[$key] = $value;
                }
            }
        }
        
        return $dirty;
    }
    
    /**
     * Sync original attributes with current.
     */
    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }
    
    /**
     * Get an attribute value.
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }
    
    /**
     * Set an attribute value.
     */
    public function setAttribute(string $key, mixed $value): void
    {
        if (in_array($key, static::$fillable) || $key === static::$primaryKey) {
            $this->attributes[$key] = $value;
        }
    }
    
    /**
     * Magic getter for attributes.
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Magic setter for attributes.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Magic isset for attributes.
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
    
    /**
     * Magic unset for attributes.
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }
}