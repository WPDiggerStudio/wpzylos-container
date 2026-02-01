<?php

declare(strict_types=1);

namespace WPZylos\Framework\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use WPZylos\Framework\Container\Exceptions\ContainerException;
use WPZylos\Framework\Container\Exceptions\NotFoundException;

/**
 * PSR-11 compatible dependency injection container.
 *
 * Supports auto-wiring, singletons, factories, and service tagging.
 *
 * @package WPZylos\Framework\Container
 */
class Container implements ContainerInterface
{
    /**
     * @var array<string, Definition> Service definitions
     */
    private array $definitions = [];

    /**
     * @var array<string, string> Alias mappings
     */
    private array $aliases = [];

    /**
     * @var string[] Stack of currently resolving services (for cycle detection)
     */
    private array $resolvingStack = [];

    /**
     * Bind a service to the container.
     *
     * @param string $id Service identifier
     * @param callable|string|null $concrete Concrete implementation or factory
     *
     * @return Definition
     */
    public function bind(string $id, callable|string|null $concrete = null): Definition
    {
        $definition               = new Definition($id, $concrete);
        $this->definitions[ $id ] = $definition;

        return $definition;
    }

    /**
     * Bind a singleton service.
     *
     * @param string $id Service identifier
     * @param callable|string|null $concrete Concrete implementation or factory
     *
     * @return Definition
     */
    public function singleton(string $id, callable|string|null $concrete = null): Definition
    {
        return $this->bind($id, $concrete)->share();
    }

    /**
     * Bind a singleton (alias for singleton).
     *
     * @param string $id Service identifier
     * @param callable|string|null $concrete Concrete implementation
     *
     * @return Definition
     */
    public function addShared(string $id, callable|string|null $concrete = null): Definition
    {
        return $this->singleton($id, $concrete);
    }

    /**
     * Bind a service (alias for bind, returns Definition for chaining).
     *
     * @param string $id Service identifier
     * @param callable|string|null $concrete Concrete implementation
     *
     * @return Definition
     */
    public function add(string $id, callable|string|null $concrete = null): Definition
    {
        return $this->bind($id, $concrete);
    }

    /**
     * Register an alias for a service.
     *
     * @param string $alias Alias name
     * @param string $id Target service identifier
     *
     * @return static
     */
    public function alias(string $alias, string $id): static
    {
        $this->aliases[ $alias ] = $id;

        return $this;
    }

    /**
     * Tag multiple services.
     *
     * @param string[] $ids Service identifiers
     * @param string $tag Tag name
     *
     * @return static
     */
    public function tag(array $ids, string $tag): static
    {
        foreach ($ids as $id) {
            if (isset($this->definitions[ $id ])) {
                $this->definitions[ $id ]->addTag($tag);
            }
        }

        return $this;
    }

    /**
     * Get all services with a specific tag.
     *
     * @param string $tag Tag name
     *
     * @return array Resolved services
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \WPZylos\Framework\Container\Exceptions\ContainerException
     * @throws \WPZylos\Framework\Container\Exceptions\NotFoundException|\ReflectionException
     */
    public function tagged(string $tag): array
    {
        $services = [];

        foreach ($this->definitions as $definition) {
            if ($definition->hasTag($tag)) {
                $services[] = $this->get($definition->getId());
            }
        }

        return $services;
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotFoundException If service not found
     * @throws ContainerException|\ReflectionException If resolution fails
     */
    public function get(string $id): mixed
    {
        // Check aliases
        $id = $this->aliases[ $id ] ?? $id;

        // Check for existing definition
        if (isset($this->definitions[ $id ])) {
            return $this->resolve($this->definitions[ $id ]);
        }

        // Try auto-wiring if it's a class
        if (class_exists($id)) {
            return $this->autowire($id);
        }

        throw new NotFoundException(
            sprintf('Service "%s" not found in container.', $id)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        $id = $this->aliases[ $id ] ?? $id;

        return isset($this->definitions[ $id ]) || class_exists($id);
    }

    /**
     * Resolve a definition.
     *
     * @param Definition $definition Definition to resolve
     *
     * @return mixed Resolved instance
     * @throws ContainerException If resolution fails
     */
    private function resolve(Definition $definition): mixed
    {
        // Return cached instance for singletons
        if ($definition->isShared() && $definition->isResolved()) {
            return $definition->getResolved();
        }

        $id       = $definition->getId();
        $concrete = $definition->getConcrete();

        // Detect circular dependencies
        if (in_array($id, $this->resolvingStack, true)) {
            throw new ContainerException(
                sprintf(
                    'Circular dependency detected: %s -> %s',
                    implode(' -> ', $this->resolvingStack),
                    $id
                )
            );
        }

        $this->resolvingStack[] = $id;

        try {
            $instance = $this->build($concrete);

            // Cache singleton
            if ($definition->isShared()) {
                $definition->setResolved($instance);
            }

            return $instance;
        } finally {
            array_pop($this->resolvingStack);
        }
    }

    /**
     * Build a concrete implementation.
     *
     * @param callable|string $concrete Factory callable or class name
     *
     * @return mixed Built instance
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException If the build fails
     * @throws \WPZylos\Framework\Container\Exceptions\ContainerException If the build fails
     */
    private function build(callable|string $concrete): mixed
    {
        // If it's a callable, invoke it with the container
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        // Otherwise, it's a class name - autowire it
        return $this->autowire($concrete);
    }

    /**
     * Autowire a class.
     *
     * @param string $class Class name to autowire
     *
     * @return object Instantiated object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException If autowiring fails
     * @throws \WPZylos\Framework\Container\Exceptions\ContainerException If autowiring fails
     */
    private function autowire(string $class): object
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ContainerException(
                sprintf('Class "%s" does not exist.', $class),
                previous: $e
            );
        }

        if (! $reflection->isInstantiable()) {
            throw new ContainerException(
                sprintf('Class "%s" is not instantiable.', $class)
            );
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $parameters   = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor dependencies.
     *
     * @param ReflectionParameter[] $parameters Constructor parameters
     *
     * @return array Resolved dependencies
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws \WPZylos\Framework\Container\Exceptions\ContainerException If resolution fails
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // No type hint - check for the default value
            if (! $type instanceof ReflectionNamedType) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } elseif ($parameter->allowsNull()) {
                    $dependencies[] = null;
                } else {
                    throw new ContainerException(
                        sprintf(
                            'Cannot resolve parameter "%s" without type hint.',
                            $parameter->getName()
                        )
                    );
                }
                continue;
            }

            $typeName = $type->getName();

            // Built-in type (string, int, etc.)
            if ($type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } elseif ($parameter->allowsNull()) {
                    $dependencies[] = null;
                } else {
                    throw new ContainerException(
                        sprintf(
                            'Cannot resolve built-in type "%s" for parameter "%s".',
                            $typeName,
                            $parameter->getName()
                        )
                    );
                }
                continue;
            }

            // Class/interface type - resolve from container
            try {
                $dependencies[] = $this->get($typeName);
            } catch (NotFoundException $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } elseif ($parameter->allowsNull()) {
                    $dependencies[] = null;
                } else {
                    throw new ContainerException(
                        sprintf(
                            'Cannot resolve dependency "%s" for parameter "%s".',
                            $typeName,
                            $parameter->getName()
                        ),
                        previous: $e
                    );
                }
            }
        }

        return $dependencies;
    }

    /**
     * Remove a service from the container.
     *
     * @param string $id Service identifier
     *
     * @return bool True if removed
     */
    public function forget(string $id): bool
    {
        if (isset($this->definitions[ $id ])) {
            unset($this->definitions[ $id ]);

            return true;
        }

        return false;
    }

    /**
     * Get all registered service identifiers.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->definitions);
    }
}
