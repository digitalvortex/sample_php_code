<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface DatabaseDefinitionsInterface
 * 
 * Defines the contract for database definition classes.
 * PHP 8.4 compatible with strict typing and comprehensive method definitions.
 */
interface DatabaseDefinitionsInterface
{
    /**
     * Get all database definitions/configurations.
     * 
     * @return array<string, mixed> Database definitions
     */
    public static function getDefinitions(): array;

    /**
     * Get a specific definition by key.
     * 
     * @param string $key The definition key
     * @return mixed The definition value
     * @throws \InvalidArgumentException If key doesn't exist
     */
    public static function getDefinition(string $key): mixed;

    /**
     * Check if a definition exists.
     * 
     * @param string $key The definition key
     * @return bool True if definition exists
     */
    public static function hasDefinition(string $key): bool;

    /**
     * Validate all definitions.
     * 
     * @return bool True if all definitions are valid
     * @throws \RuntimeException If validation fails
     */
    public static function validateDefinitions(): bool;
}