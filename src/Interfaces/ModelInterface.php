<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface ModelInterface
 * 
 * Defines the contract for database models.
 * PHP 8.4 compatible with strict typing and comprehensive ORM features.
 */
interface ModelInterface
{
    /**
     * Find a record by its primary key.
     * 
     * @param mixed $id Primary key value
     * @return static|null Model instance or null if not found
     */
    public static function find(mixed $id): ?static;

    /**
     * Find a record by its primary key or throw an exception.
     * 
     * @param mixed $id Primary key value
     * @return static Model instance
     * @throws \RuntimeException If record not found
     */
    public static function findOrFail(mixed $id): static;

    /**
     * Find records matching criteria.
     * 
     * @param array<string, mixed> $criteria Search criteria
     * @return array<static> Array of model instances
     */
    public static function findBy(array $criteria): array;

    /**
     * Find first record matching criteria.
     * 
     * @param array<string, mixed> $criteria Search criteria
     * @return static|null Model instance or null if not found
     */
    public static function findOneBy(array $criteria): ?static;

    /**
     * Get all records.
     * 
     * @return array<static> All model instances
     */
    public static function all(): array;

    /**
     * Create a new record.
     * 
     * @param array<string, mixed> $data Record data
     * @return static New model instance
     */
    public static function create(array $data): static;

    /**
     * Save the model to the database.
     * 
     * @return bool True if save was successful
     */
    public function save(): bool;

    /**
     * Update the model with new data.
     * 
     * @param array<string, mixed> $data Data to update
     * @return bool True if update was successful
     */
    public function update(array $data): bool;

    /**
     * Delete the model from the database.
     * 
     * @return bool True if delete was successful
     */
    public function delete(): bool;

    /**
     * Get the model's primary key value.
     * 
     * @return mixed Primary key value
     */
    public function getId(): mixed;

    /**
     * Get the table name for this model.
     * 
     * @return string Table name
     */
    public static function getTableName(): string;

    /**
     * Get the primary key column name.
     * 
     * @return string Primary key column name
     */
    public static function getPrimaryKey(): string;

    /**
     * Get fillable attributes (mass assignment protection).
     * 
     * @return array<string> Fillable attributes
     */
    public static function getFillable(): array;

    /**
     * Get hidden attributes (not included in arrays/JSON).
     * 
     * @return array<string> Hidden attributes
     */
    public static function getHidden(): array;

    /**
     * Convert model to array.
     * 
     * @return array<string, mixed> Model data as array
     */
    public function toArray(): array;

    /**
     * Convert model to JSON.
     * 
     * @return string Model data as JSON
     */
    public function toJson(): string;

    /**
     * Check if model exists in database.
     * 
     * @return bool True if model exists
     */
    public function exists(): bool;

    /**
     * Check if model is new (not saved to database).
     * 
     * @return bool True if model is new
     */
    public function isNew(): bool;

    /**
     * Refresh model data from database.
     * 
     * @return static Refreshed model instance
     */
    public function refresh(): static;
}