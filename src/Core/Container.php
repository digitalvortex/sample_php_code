<?php
declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use Exception;

/**
 * Class Container
 *
 * A simple Dependency Injection (DI) container for managing service instances.
 */
class Container
{
    /**
     * @var array The registered services.
     */
    private $services = [];

    /**
     * @var array The singleton instances.
     */
    private $singletons = [];

    /**
     * Registers a service in the container.
     *
     * @param string   $name      The name of the service.
     * @param callable $resolver  The function that resolves the service instance.
     * @param bool     $singleton Whether the service should be treated as a singleton.
     */
    public function register(string $name, callable $resolver, bool $singleton = false): void
    {
        $this->services[$name] = $resolver;
        if ($singleton) {
            $this->singletons[$name] = null;
        }
    }

    /**
     * Resolves a service from the container.
     *
     * @param string $name The name of the service to resolve.
     * @return mixed The resolved service instance.
     * @throws Exception If the service is not found.
     */
    public function resolve(string $name)
    {
        if (array_key_exists($name, $this->singletons)) {
            if ($this->singletons[$name] === null) {
                $this->singletons[$name] = $this->build($name);
            }
            return $this->singletons[$name];
        }

        return $this->build($name);
    }

    /**
     * Builds a new service instance.
     *
     * @param string $name The name of the service to build.
     * @return mixed The newly created service instance.
     * @throws Exception If the service is not found.
     */
    private function build(string $name)
    {
        if (!isset($this->services[$name])) {
            throw new Exception("Service $name not found.");
        }
        $resolver = $this->services[$name];
        return $resolver($this);
    }

    /**
     * Automatically wires dependencies and creates an instance of the given class.
     *
     * @param string $className The name of the class to autowire.
     * @return mixed The created instance with dependencies injected.
     * @throws Exception If a dependency cannot be resolved.
     */
    public function autowire(string $className)
    {
        $reflector = new ReflectionClass($className);
        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type === null || $type->isBuiltin()) {
                throw new Exception("Cannot resolve class dependency {$parameter->name}");
            }
            $dependencyClassName = $type->getName();
            $dependencies[] = $this->resolve($dependencyClassName);
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
