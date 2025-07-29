<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Interface ServiceInterface
 * 
 * Defines the contract for application services.
 * PHP 8.4 compatible with strict typing and service-oriented architecture.
 */
interface ServiceInterface
{
    /**
     * Initialize the service.
     * Called when the service is first instantiated.
     * 
     * @return void
     */
    public function initialize(): void;

    /**
     * Get the service name/identifier.
     * 
     * @return string Service name
     */
    public function getName(): string;

    /**
     * Get service version.
     * 
     * @return string Service version
     */
    public function getVersion(): string;

    /**
     * Check if the service is available/operational.
     * 
     * @return bool True if service is available
     */
    public function isAvailable(): bool;

    /**
     * Perform health check on the service.
     * 
     * @return array<string, mixed> Health check results
     */
    public function healthCheck(): array;

    /**
     * Get service configuration.
     * 
     * @return array<string, mixed> Service configuration
     */
    public function getConfig(): array;

    /**
     * Set service configuration.
     * 
     * @param array<string, mixed> $config Configuration array
     * @return void
     */
    public function setConfig(array $config): void;

    /**
     * Get service dependencies.
     * 
     * @return array<string> List of required service names
     */
    public function getDependencies(): array;

    /**
     * Shutdown the service gracefully.
     * Called when the application is shutting down.
     * 
     * @return void
     */
    public function shutdown(): void;
}