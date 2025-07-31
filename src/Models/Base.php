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
    
    /** @var array<string> */
    protected static array $fillable = [];
    
    /** @var array<string> */
    protected static array $encrypted = [];
    
    /** @var array<string> */
    protected static array $hidden = [];
    
    protected static string $primaryKey = 'id';
    
    /**
     * @var array<string> Allowed table names for security validation
     */
    private static array $allowedTables = [
        'users',
        'blogs', 
        'contacts',
        'sessions',
        'levels',
        'api_keys'
    ];
    
    /**
     * @var array<string> Allowed operators for WHERE clauses
     */
    private static array $allowedOperators = [
        '=', '!=', '<>', '<', '>', '<=', '>=', 
        'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
        'IS NULL', 'IS NOT NULL'
    ];
    
    /** @var array<string, mixed> */
    protected array $attributes = [];
    
    /** @var array<string, mixed> */
    protected array $original = [];
    
    protected bool $exists = false;

    /**
     * @param array<string, mixed> $attributes
     */
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
     *
     * @param array<string, mixed> $attributes
     * @return static
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
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function create(array $data): static
    {
        /** @phpstan-ignore-next-line */
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
     * Perform insert operation with SQL injection protection.
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

        // Validate and escape field names
        $fieldNames = array_keys($fields);
        $escapedColumns = self::validateAndEscapeFields($fieldNames);
        $columns = implode(', ', $escapedColumns);
        $values = implode(', ', array_fill(0, count($fields), '?'));

        // Use validated table name and escaped column names
        $tableName = self::escapeIdentifier(static::getTableName());
        $sql = "INSERT INTO {$tableName} ({$columns}) VALUES ({$values})";
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
     * Find a record by ID with SQL injection protection.
     */
    public static function find(mixed $id): ?static
    {
        $tableName = self::escapeIdentifier(static::getTableName());
        $primaryKey = self::escapeIdentifier(static::$primaryKey);
        
        $sql = "SELECT * FROM {$tableName} WHERE {$primaryKey} = ?";
        $stmt = static::$pdo->prepare($sql);
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
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }
    
    /**
     * Perform update operation with SQL injection protection.
     */
    protected function performUpdate(): bool
    {
        $dirty = $this->getDirty();
        
        if (empty($dirty)) {
            return true; // No changes to update
        }
        
        // Validate field names
        $fieldNames = array_keys($dirty);
        foreach ($fieldNames as $field) {
            if (!self::isValidFieldName($field)) {
                throw new \InvalidArgumentException("Invalid field name for update: " . $field);
            }
        }
        
        // Encrypt fields that should be encrypted
        foreach (static::$encrypted as $field) {
            if (isset($dirty[$field])) {
                $dirty[$field] = static::$encryptionService->encrypt($dirty[$field]);
            }
        }

        $setClause = [];
        $primaryKeyParam = ':' . static::$primaryKey;
        $params = [$primaryKeyParam => $this->getId()];

        foreach ($dirty as $field => $value) {
            $escapedField = self::escapeIdentifier($field);
            $paramName = ":$field";
            $setClause[] = "$escapedField = $paramName";
            $params[$paramName] = $value;
        }

        // Add updated_at timestamp if it exists in fillable
        if (in_array('updated_at', static::$fillable)) {
            $updatedAtField = self::escapeIdentifier('updated_at');
            $setClause[] = "$updatedAtField = CURRENT_TIMESTAMP";
        }

        $tableName = self::escapeIdentifier(static::getTableName());
        $primaryKeyField = self::escapeIdentifier(static::$primaryKey);
        $sql = "UPDATE {$tableName} SET " . implode(', ', $setClause) . " WHERE {$primaryKeyField} = {$primaryKeyParam}";
        $stmt = static::$pdo->prepare($sql);
        
        $result = $stmt->execute($params);
        
        if ($result) {
            $this->syncOriginal();
        }
        
        return $result;
    }

    /**
     * Delete the model from the database with SQL injection protection.
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $tableName = self::escapeIdentifier(static::getTableName());
        $primaryKeyField = self::escapeIdentifier(static::$primaryKey);
        $sql = "DELETE FROM {$tableName} WHERE {$primaryKeyField} = :id";
        $stmt = static::$pdo->prepare($sql);
        $result = $stmt->execute([':id' => $this->getId()]);
        
        if ($result) {
            $this->exists = false;
        }
        
        return $result;
    }

    /**
     * Get all records with SQL injection protection.
     *
     * @return array<static>
     */
    public static function all(): array
    {
        $tableName = self::escapeIdentifier(static::getTableName());
        $sql = "SELECT * FROM {$tableName}";
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $models = [];
        foreach ($results as $result) {
            $models[] = static::newFromAttributes(static::decryptAttributes($result));
        }

        return $models;
    }

    /**
     * Find records matching criteria with SQL injection protection.
     *
     * @param array<string, mixed> $criteria
     * @return array<static>
     * @throws \InvalidArgumentException If field names are invalid
     */
    public static function findBy(array $criteria): array
    {
        $whereClause = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            // Validate field name
            if (!self::isValidFieldName($field)) {
                throw new \InvalidArgumentException("Invalid field name in criteria: " . $field);
            }
            
            $escapedField = self::escapeIdentifier($field);
            $paramName = ":$field";
            $whereClause[] = "$escapedField = $paramName";
            $params[$paramName] = $value;
        }
        
        $tableName = self::escapeIdentifier(static::getTableName());
        $sql = "SELECT * FROM {$tableName} WHERE " . implode(' AND ', $whereClause);
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
     *
     * @param array<string, mixed> $criteria
     * @return static|null
     */
    public static function findOneBy(array $criteria): ?static
    {
        $results = static::findBy($criteria);
        return $results[0] ?? null;
    }

    /**
     * Count total records with SQL injection protection.
     */
    public static function count(): int
    {
        $tableName = self::escapeIdentifier(static::getTableName());
        $sql = "SELECT COUNT(*) FROM {$tableName}";
        $stmt = static::$pdo->prepare($sql);
        $stmt->execute();
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
     * Get the table name for this model with security validation.
     * 
     * @throws \RuntimeException If table name is not in allowlist
     */
    public static function getTableName(): string
    {
        $tableName = static::$table;
        
        if (!self::isValidTableName($tableName)) {
            throw new \RuntimeException("Invalid or unauthorized table name: " . $tableName);
        }
        
        return $tableName;
    }
    
    /**
     * Validate table name against security allowlist.
     * 
     * @param string $tableName Table name to validate
     * @return bool True if table name is allowed
     */
    private static function isValidTableName(string $tableName): bool
    {
        return in_array($tableName, self::$allowedTables, true);
    }
    
    /**
     * Validate field name against model schema.
     * 
     * @param string $fieldName Field name to validate
     * @return bool True if field name is valid
     */
    protected static function isValidFieldName(string $fieldName): bool
    {
        // Allow primary key
        if ($fieldName === static::$primaryKey) {
            return true;
        }
        
        // Allow fillable fields
        if (in_array($fieldName, static::$fillable, true)) {
            return true;
        }
        
        // Allow encrypted fields
        if (in_array($fieldName, static::$encrypted, true)) {
            return true;
        }
        
        // Allow hidden fields
        if (in_array($fieldName, static::$hidden, true)) {
            return true;
        }
        
        // Allow common timestamp fields
        if (in_array($fieldName, ['created_at', 'updated_at', 'deleted_at'], true)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Escape SQL identifier (table or column name).
     * 
     * @param string $identifier SQL identifier to escape
     * @return string Escaped identifier
     */
    protected static function escapeIdentifier(string $identifier): string
    {
        // Remove any existing backticks and escape with backticks
        $clean = str_replace('`', '', $identifier);
        return "`{$clean}`";
    }
    
    /**
     * Validate and escape field names for SQL queries.
     * 
     * @param array<string> $fields Field names to validate
     * @return array<string> Validated and escaped field names
     * @throws \InvalidArgumentException If any field name is invalid
     */
    protected static function validateAndEscapeFields(array $fields): array
    {
        $escapedFields = [];
        
        foreach ($fields as $field) {
            if (!self::isValidFieldName($field)) {
                throw new \InvalidArgumentException("Invalid field name: " . $field);
            }
            $escapedFields[] = self::escapeIdentifier($field);
        }
        
        return $escapedFields;
    }
    
    /**
     * Validate SQL operator.
     * 
     * @param string $operator SQL operator to validate
     * @return bool True if operator is allowed
     */
    protected static function isValidOperator(string $operator): bool
    {
        return in_array(strtoupper($operator), self::$allowedOperators, true);
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
     *
     * @return array<string>
     */
    public static function getFillable(): array
    {
        return static::$fillable;
    }
    
    /**
     * Get hidden attributes.
     *
     * @return array<string>
     */
    public static function getHidden(): array
    {
        return static::$hidden;
    }
    
    /**
     * Convert model to array.
     *
     * @return array<string, mixed>
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
        $json = json_encode($this->toArray());
        return $json !== false ? $json : '{}';
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
     *
     * @param array<string, mixed> $attributes
     * @return static
     */
    protected static function newFromAttributes(array $attributes): static
    {
        /** @phpstan-ignore-next-line */
        $instance = new static();
        $instance->attributes = $attributes;
        $instance->original = $attributes;
        $instance->exists = true;
        
        return $instance;
    }
    
    /**
     * Decrypt encrypted attributes.
     *
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
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
     *
     * @return array<string, mixed>
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