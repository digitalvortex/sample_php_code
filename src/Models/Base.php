<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Services\EncryptionService;

/**
 * Abstract Base Class
 * 
 * Provides core database operations with encryption support
 */
abstract class Base
{
    protected PDO $pdo;
    protected EncryptionService $encryptionService;
    protected string $table;
    protected array $fillable = [];
    protected array $encrypted = [];

    public function __construct(PDO $pdo, EncryptionService $encryptionService)
    {
        $this->pdo = $pdo;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Create a new record
     */
    protected function create(array $data): int
    {
        $fields = array_intersect_key($data, array_flip($this->fillable));

        // Encrypt fields that should be encrypted
        foreach ($this->encrypted as $field) {
            if (isset($fields[$field])) {
                $fields[$field] = $this->encryptionService->encrypt($fields[$field]);
            }
        }

        $columns = implode(', ', array_keys($fields));
        $values = implode(', ', array_fill(0, count($fields), '?'));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute(array_values($fields))) {
            return (int)$this->pdo->lastInsertId();
        }
        
        throw new \Exception('Unable to insert record');
    }

    /**
     * Find a record by ID
     */
    protected function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Decrypt encrypted fields
            foreach ($this->encrypted as $field) {
                if (isset($result[$field])) {
                    $result[$field] = $this->encryptionService->decrypt($result[$field]);
                }
            }
        }

        return $result ?: null;
    }

    /**
     * Update an existing record
     */
    protected function update(int $id, array $data): bool
    {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        
        // Encrypt fields that should be encrypted
        foreach ($this->encrypted as $field) {
            if (isset($fields[$field])) {
                $fields[$field] = $this->encryptionService->encrypt($fields[$field]);
            }
        }

        if (empty($fields)) {
            return false;
        }

        $setClause = [];
        $params = [':id' => $id];

        foreach ($fields as $field => $value) {
            $setClause[] = "$field = :$field";
            $params[":$field"] = $value;
        }

        // Add updated_at timestamp if it exists in fillable
        if (in_array('updated_at', $this->fillable)) {
            $setClause[] = "updated_at = CURRENT_TIMESTAMP";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Delete a record
     */
    protected function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Find all records
     */
    protected function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decrypt encrypted fields for all results
        foreach ($results as &$result) {
            foreach ($this->encrypted as $field) {
                if (isset($result[$field])) {
                    $result[$field] = $this->encryptionService->decrypt($result[$field]);
                }
            }
        }

        return $results;
    }

    /**
     * Find records by a specific field value
     */
    protected function findBy(string $field, mixed $value): array
    {
        if (!in_array($field, $this->fillable)) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE $field = ?");
        $stmt->execute([$value]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decrypt encrypted fields for all results
        foreach ($results as &$result) {
            foreach ($this->encrypted as $field) {
                if (isset($result[$field])) {
                    $result[$field] = $this->encryptionService->decrypt($result[$field]);
                }
            }
        }

        return $results;
    }

    /**
     * Count total records
     */
    protected function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }
}