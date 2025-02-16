<?php
//// filepath: /Users/michaelkingsnorth/Development/sample_php_code/src/Models/Level.php

namespace App\Models;

class Level extends Base
{
    protected string $table = 'levels';
    

    // Define what columns can be mass-assigned
    protected array $fillable = [
        'name'
    ];

    public function createLevel(array $data): int
    {
        return $this->create($data);
    }

    public function updateLevel(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteLevel(int $id): bool
    {
        return $this->delete($id);
    }

    public function findLevelById(int $id): ?array
    {
        return $this->find($id);
    }

    public function findAllLevels(): array
    {
        return $this->findAll();
    }

    // Optionally add helper methods
    public function getLevelName(): string
    {
        // Assuming there's a 'name' column in the levels table
        return $this->attributes['name'] ?? '';
    }
}