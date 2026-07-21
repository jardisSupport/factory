<?php

declare(strict_types=1);

namespace JardisSupport\Factory;

use Psr\Container\ContainerInterface;
use ReflectionClass;

/**
 * PSR-11 Container with reflection-based instantiation.
 *
 * Resolution order for get():
 * 1. Pre-registered instances (exact key match)
 * 2. Backend container delegation (if configured and has the entry)
 * 3. Reflection-based instantiation (classes without required constructor params)
 *
 * Use create() for instantiation with constructor parameters.
 */
class Factory implements ContainerInterface
{
    /**
     * @param ContainerInterface|null $container Backend container (Symfony, Laravel, PHP-DI, etc.)
     * @param array<string, mixed> $instances Pre-registered instances, keyed by ID (e.g. interface FQCN)
     */
    public function __construct(
        private readonly ?ContainerInterface $container = null,
        private readonly array $instances = [],
    ) {
    }

    /**
     * PSR-11: Finds an entry by its identifier and returns it.
     *
     * @param string $id FQCN or service identifier
     * @return mixed The resolved instance
     * @throws NotFoundException If the entry cannot be found
     */
    public function get(string $id): mixed
    {
        // 1. Pre-registered instance
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        // 2. Backend container
        if ($this->container !== null && $this->container->has($id)) {
            return $this->container->get($id);
        }

        // 3. Reflection instantiation
        if (class_exists($id)) {
            return $this->createInstance($id);
        }

        throw new NotFoundException($id);
    }

    /**
     * PSR-11: Returns true if the container can return an entry for the given identifier.
     *
     * @param string $id Identifier of the entry to look for
     */
    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->instances)) {
            return true;
        }

        if ($this->container !== null && $this->container->has($id)) {
            return true;
        }

        return class_exists($id);
    }

    /**
     * Instantiates a class with constructor parameters via Reflection.
     *
     * Unlike get(), this always creates a new instance and passes the given
     * parameters to the constructor. Does not check instances or backend container.
     *
     * @template T of object
     * @param class-string<T> $className
     * @return T
     * @throws ContainerException If the class cannot be instantiated
     */
    public function create(string $className, mixed ...$parameters): object
    {
        if (!class_exists($className)) {
            throw new ContainerException(sprintf('Class %s not found.', $className));
        }

        if (empty($parameters)) {
            return $this->createInstance($className);
        }

        $class = new ReflectionClass($className);

        if ($class->getConstructor() === null) {
            /** @var T */
            return new $className();
        }

        /** @var T */
        return $class->newInstanceArgs($parameters);
    }

    // =========================================================================
    // Private
    // =========================================================================

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     * @throws ContainerException If the class has required constructor parameters
     */
    private function createInstance(string $className): object
    {
        $class = new ReflectionClass($className);

        if ($class->getConstructor() === null || $class->getConstructor()->getNumberOfRequiredParameters() === 0) {
            /** @var T */
            return new $className();
        }

        throw new ContainerException(sprintf(
            'Class %s has required constructor parameters. Register it as instance or use create().',
            $className
        ));
    }
}
