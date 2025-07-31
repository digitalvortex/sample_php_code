<?php
declare(strict_types=1);

namespace App\Models;

class Level extends Base
{
    protected static string $table = 'levels';
    
    /** @var array<string> */
    protected static array $fillable = [
        'name',
        'created_at',
        'updated_at'
    ];
    
    /** @var array<string> */
    protected static array $encrypted = [];
    
    /** @var array<string> */
    protected static array $hidden = [];

    /**
     * Create a new level.
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function createLevel(array $data): static
    {
        return static::create($data);
    }

    /**
     * Update an existing level.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public static function updateLevel(int $id, array $data): bool
    {
        $level = static::find($id);
        return $level ? $level->update($data) : false;
    }

    /**
     * Delete a level.
     *
     * @param int $id
     * @return bool
     */
    public static function deleteLevel(int $id): bool
    {
        $level = static::find($id);
        return $level ? $level->delete() : false;
    }

    /**
     * Find a level by ID.
     *
     * @param int $id
     * @return static|null
     */
    public static function findLevelById(int $id): ?static
    {
        return static::find($id);
    }

    /**
     * Get all levels.
     *
     * @return array<static>
     */
    public static function findAllLevels(): array
    {
        return static::all();
    }

    // Optionally add helper methods
    public function getLevelName(): string
    {
        // Assuming there's a 'name' column in the levels table
        return $this->attributes['name'] ?? '';
    }
}