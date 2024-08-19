<?php
declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;
use Exception;

class Container
{
    private array $services = [];
    private array $instances = [];

    public function register(string $name, callable $definition, bool $singleton = false): void
    {
        $this->services[$name] = ['definition' => $definition, 'singleton' => $singleton];
    }

    public function resolve(string $name)
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

    public function get(string $name)
    {
        return $this->resolve($name);
    }

    /**
     * Autowire a class by resolving its dependencies.
     *
     * @param string $name The name of the class.
     * @return mixed The instance of the class.
     * @throws Exception If the class cannot be resolved.
     */
    private function autowire(string $name)
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
            throw new Exception("Cannot resolve class dependency {$parameter->name}");
        }, $parameters);

        return $reflectionClass->newInstanceArgs($dependencies);
    }
}