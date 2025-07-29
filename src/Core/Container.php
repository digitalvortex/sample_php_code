<?php

declare(strict_types=1);

namespace App\Core;

use App\Interfaces\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Exception;

/**
 * Class Container
 *
 * A simple dependency injection container that supports manual registration of services
 * as well as automatic dependency resolution (autowiring) for unregistered classes.
 * PHP 8.4 compatible with comprehensive ContainerInterface implementation.
 */
class Container implements ContainerInterface
{
    /**
     * An array of service definitions.
     * Format: [ 'ClassName' => [ 'definition' => callable, 'singleton' => bool ] ]
     *
     * @var array
     */
    private array $services = [];
    
    /**
     * An array of instantiated singleton services.
     *
     * @var array
     */
    private array $instances = [];

    /**
     * Register a service with an associated callable definition.
     *
     * @param string   $name       The identifier of the service (typically a class name).
     * @param callable $definition A callable that returns an instance of the service.
     * @param bool     $singleton  Whether to cache and re-use this service instance.
     *
     * @return void
     */
    public function register(string $name, callable $definition, bool $singleton = false): void
    {
        $this->services[$name] = ['definition' => $definition, 'singleton' => $singleton];
    }

    /**
     * Resolve a service by its name, instantiating it if necessary.
     *
     * @param string $name The service identifier.
     *
     * @return mixed The resolved service instance.
     */
    public function resolve(string $name): mixed
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        if (!isset($this->services[$name])) {
            return $this->autowire($name);
        }

        $definition = $this->services[$name]['definition'];
        $service = $definition($this);

        if ($this->services[$name]['singleton']) {
            $this->instances[$name] = $service;
        }

        return $service;
    }

    /**
     * A shortcut method for resolving a service.
     *
     * @param string $name The service identifier.
     *
     * @return mixed The resolved service instance.
     */
    public function get(string $name): mixed
    {
        return $this->resolve($name);
    }

    /**
     * Autowire a class by resolving its constructor dependencies.
     *
     * If the class has no constructor, it returns a new instance.
     * If the class has dependencies, it attempts to resolve them recursively.
     *
     * @param string $name The fully qualified class name.
     *
     * @return mixed The instantiated object.
     * @throws Exception If a dependency cannot be resolved.
     */
    private function autowire(string $name): mixed
    {
        $reflectionClass = new ReflectionClass($name);
        $constructor = $reflectionClass->getConstructor();

        if (is_null($constructor)) {
            return new $name;
        }

        $parameters = $constructor->getParameters();
        $dependencies = array_map(function (ReflectionParameter $parameter) {
            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                return $this->resolve($type->getName());
            }
            throw new Exception("Cannot resolve class dependency \${$parameter->name}");
        }, $parameters);

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    /**
     * Check if a service is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]) || isset($this->instances[$name]);
    }

    /**
     * Remove a service from the container.
     */
    public function remove(string $name): void
    {
        unset($this->services[$name], $this->instances[$name]);
    }

    /**
     * Get all registered service names.
     */
    public function getServices(): array
    {
        return array_unique(array_merge(
            array_keys($this->services),
            array_keys($this->instances)
        ));
    }

    /**
     * Clear all services from the container.
     */
    public function clear(): void
    {
        $this->services = [];
        $this->instances = [];
    }

    /**
     * Register a singleton service.
     */
    public function singleton(string $name, callable $definition): void
    {
        $this->register($name, $definition, true);
    }

    /**
     * Register a service instance directly.
     */
    public function instance(string $name, mixed $instance): void
    {
        $this->instances[$name] = $instance;
    }

    /**
     * Check if a service is registered as singleton.
     */
    public function isSingleton(string $name): bool
    {
        return isset($this->services[$name]) && $this->services[$name]['singleton'];
    }

    /**
     * Get service definition (for debugging).
     */
    public function getDefinition(string $name): ?array
    {
        return $this->services[$name] ?? null;
    }
}