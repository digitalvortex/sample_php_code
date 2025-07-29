<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface ContainerInterface
 * 
 * Defines the contract for dependency injection containers.
 * PHP 8.4 compatible with strict typing and comprehensive method definitions.
 */
interface ContainerInterface
{
    /**
     * Register a service with the container.
     * 
     * @param string $name Service identifier
     * @param callable $definition Factory function that returns service instance
     * @param bool $singleton Whether to cache and reuse the instance
     * @return void
     */
    public function register(string $name, callable $definition, bool $singleton = false): void;

    /**
     * Resolve a service from the container.
     * 
     * @param string $name Service identifier
     * @return mixed The resolved service instance
     * @throws \Exception If service cannot be resolved
     */
    public function resolve(string $name): mixed;

    /**
     * Get a service from the container (alias for resolve).
     * 
     * @param string $name Service identifier
     * @return mixed The resolved service instance
     */
    public function get(string $name): mixed;

    /**
     * Check if a service is registered.
     * 
     * @param string $name Service identifier
     * @return bool True if service is registered
     */
    public function has(string $name): bool;

    /**
     * Remove a service from the container.
     * 
     * @param string $name Service identifier
     * @return void
     */
    public function remove(string $name): void;

    /**
     * Get all registered service names.
     * 
     * @return array<string> List of registered service names
     */
    public function getServices(): array;

    /**
     * Clear all services from the container.
     * 
     * @return void
     */
    public function clear(): void;
}