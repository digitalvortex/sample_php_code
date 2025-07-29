<?php
declare(strict_types=1);

namespace App\Definitions;

use App\Core\Container;
use App\Interfaces\DatabaseDefinitionsInterface;
use PDO;

/**
 * Class DatabaseDefinitions
 *
 * Provides database connection definitions for dependency injection.
 *
 * @package App\Definitions
 */
readonly class DatabaseDefinitions implements DatabaseDefinitionsInterface
{
    /**
     * Returns an array of database connection definitions.
     *
     * @return array<string, mixed>
     * @throws \Exception If the database configuration is incomplete.
     */
    public static function getDefinitions(): array
    {
        return [
            /**
             * Defines a PDO instance.
             *
             * @param Container $c The container instance.
             * @return PDO The PDO instance.
             * @throws \Exception If the database configuration is incomplete.
             */
            PDO::class => function (Container $c) {
                $host = $_ENV['DB_HOST'] ?? null;
                $db   = $_ENV['DB_NAME'] ?? null;
                $user = $_ENV['DB_USER'] ?? null;
                $pass = $_ENV['DB_PASSWORD'] ?? null;
                $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

                if (!$host || !$db || !$user || !$pass || !$charset) {
                    throw new \Exception("Database configuration is incomplete.");
                }

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                return new PDO($dsn, $user, $pass, $options);
            },
        ];
    }

    /**
     * Get a specific definition by key.
     * 
     * @param string $key The definition key
     * @return mixed The definition value
     * @throws \InvalidArgumentException If key doesn't exist
     */
    public static function getDefinition(string $key): mixed
    {
        $definitions = static::getDefinitions();
        
        if (!array_key_exists($key, $definitions)) {
            throw new \InvalidArgumentException("Definition key '{$key}' does not exist.");
        }
        
        return $definitions[$key];
    }

    /**
     * Check if a definition exists.
     * 
     * @param string $key The definition key
     * @return bool True if definition exists
     */
    public static function hasDefinition(string $key): bool
    {
        $definitions = static::getDefinitions();
        return array_key_exists($key, $definitions);
    }

    /**
     * Validate all definitions.
     * 
     * @return bool True if all definitions are valid
     * @throws \RuntimeException If validation fails
     */
    public static function validateDefinitions(): bool
    {
        try {
            $definitions = static::getDefinitions();
            
            // Validate that we have definitions
            if (empty($definitions)) {
                throw new \RuntimeException("No database definitions found.");
            }
            
            // Validate PDO definition exists
            if (!static::hasDefinition(PDO::class)) {
                throw new \RuntimeException("PDO definition is required but not found.");
            }
            
            // Validate required environment variables
            $requiredEnvVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
            $missingVars = [];
            
            foreach ($requiredEnvVars as $var) {
                if (empty($_ENV[$var])) {
                    $missingVars[] = $var;
                }
            }
            
            if (!empty($missingVars)) {
                throw new \RuntimeException("Missing required environment variables: " . implode(', ', $missingVars));
            }
            
            // Validate that definitions are callable
            foreach ($definitions as $key => $definition) {
                if (!is_callable($definition)) {
                    throw new \RuntimeException("Definition for '{$key}' is not callable.");
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            throw new \RuntimeException("Database definitions validation failed: " . $e->getMessage(), 0, $e);
        }
    }
}