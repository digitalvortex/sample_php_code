<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Services\EncryptionService;

abstract class BaseModel
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

    protected function create(array $data): bool
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
        
        return $stmt->execute(array_values($fields));
    }

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
}