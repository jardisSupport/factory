<?php

declare(strict_types=1);

namespace JardisSupport\Factory\Tests\Unit;

use JardisSupport\Factory\ContainerException;
use JardisSupport\Factory\Factory;
use JardisSupport\Factory\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

/**
 * Unit Tests for Factory (PSR-11 Container)
 *
 * Focus: Resolution order (instances → backend → reflection), PSR-11 compliance
 */
class FactoryTest extends TestCase
{
    // =========================================================================
    // ContainerInterface compliance
    // =========================================================================

    public function testImplementsContainerInterface(): void
    {
        $factory = new Factory();

        $this->assertInstanceOf(ContainerInterface::class, $factory);
    }

    // =========================================================================
    // get() — Pre-registered instances
    // =========================================================================

    public function testGetPreRegisteredInstance(): void
    {
        $obj = new stdClass();
        $factory = new Factory(instances: ['my.service' => $obj]);

        $this->assertSame($obj, $factory->get('my.service'));
    }

    public function testGetPreRegisteredInstanceByFqcn(): void
    {
        $obj = new stdClass();
        $factory = new Factory(instances: [stdClass::class => $obj]);

        $this->assertSame($obj, $factory->get(stdClass::class));
    }

    public function testGetPreRegisteredNullValue(): void
    {
        $factory = new Factory(instances: ['nullable' => null]);

        $this->assertNull($factory->get('nullable'));
    }

    public function testGetPreRegisteredFalseValue(): void
    {
        $factory = new Factory(instances: ['flag' => false]);

        $this->assertFalse($factory->get('flag'));
    }

    // =========================================================================
    // get() — Backend container delegation
    // =========================================================================

    public function testGetFromBackendContainer(): void
    {
        $expected = new stdClass();

        $backend = $this->createMock(ContainerInterface::class);
        $backend->method('has')->with('backend.service')->willReturn(true);
        $backend->method('get')->with('backend.service')->willReturn($expected);

        $factory = new Factory(container: $backend);

        $this->assertSame($expected, $factory->get('backend.service'));
    }

    // =========================================================================
    // get() — Reflection instantiation
    // =========================================================================

    public function testGetViaReflection(): void
    {
        $factory = new Factory();

        $result = $factory->get(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testGetViaReflectionClassWithOptionalParams(): void
    {
        $factory = new Factory();

        $result = $factory->get(ClassWithOptionalParams::class);

        $this->assertInstanceOf(ClassWithOptionalParams::class, $result);
        $this->assertSame('default', $result->value);
    }

    public function testReflectionCreatesNewInstanceEachTime(): void
    {
        $factory = new Factory();

        $a = $factory->get(stdClass::class);
        $b = $factory->get(stdClass::class);

        $this->assertNotSame($a, $b);
    }

    // =========================================================================
    // get() — Error cases
    // =========================================================================

    public function testGetThrowsNotFoundException(): void
    {
        $factory = new Factory();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Entry "NonExistent\\Service" not found');

        $factory->get('NonExistent\\Service');
    }

    public function testGetThrowsContainerException(): void
    {
        $factory = new Factory();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('has required constructor parameters');

        $factory->get(ClassWithRequiredParams::class);
    }

    // =========================================================================
    // get() — Resolution order
    // =========================================================================

    public function testInstancesOverrideBackend(): void
    {
        $instanceObj = new stdClass();
        $instanceObj->source = 'instance';

        $backendObj = new stdClass();
        $backendObj->source = 'backend';

        $backend = $this->createMock(ContainerInterface::class);
        $backend->method('has')->willReturn(true);
        $backend->method('get')->willReturn($backendObj);

        $factory = new Factory(container: $backend, instances: ['service' => $instanceObj]);

        $result = $factory->get('service');

        $this->assertSame('instance', $result->source);
    }

    public function testBackendOverridesReflection(): void
    {
        $backendObj = new stdClass();
        $backendObj->source = 'backend';

        $backend = $this->createMock(ContainerInterface::class);
        $backend->method('has')->with(stdClass::class)->willReturn(true);
        $backend->method('get')->with(stdClass::class)->willReturn($backendObj);

        $factory = new Factory(container: $backend);

        $result = $factory->get(stdClass::class);

        $this->assertSame('backend', $result->source);
    }

    public function testReflectionUsedWhenBackendDoesNotHave(): void
    {
        $backend = $this->createMock(ContainerInterface::class);
        $backend->method('has')->willReturn(false);

        $factory = new Factory(container: $backend);

        $result = $factory->get(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    // =========================================================================
    // has()
    // =========================================================================

    public function testHasPreRegisteredInstance(): void
    {
        $factory = new Factory(instances: ['my.service' => new stdClass()]);

        $this->assertTrue($factory->has('my.service'));
    }

    public function testHasFromBackendContainer(): void
    {
        $backend = $this->createMock(ContainerInterface::class);
        $backend->method('has')->with('backend.service')->willReturn(true);

        $factory = new Factory(container: $backend);

        $this->assertTrue($factory->has('backend.service'));
    }

    public function testHasExistingClass(): void
    {
        $factory = new Factory();

        $this->assertTrue($factory->has(stdClass::class));
    }

    public function testHasNonExistent(): void
    {
        $factory = new Factory();

        $this->assertFalse($factory->has('NonExistent\\Service'));
    }

    public function testHasPreRegisteredNullValue(): void
    {
        $factory = new Factory(instances: ['nullable' => null]);

        $this->assertTrue($factory->has('nullable'));
    }

    // =========================================================================
    // create() — Instantiation with parameters
    // =========================================================================

    public function testCreateWithParameters(): void
    {
        $factory = new Factory();

        $result = $factory->create(ClassWithRequiredParams::class, 'hello');

        $this->assertInstanceOf(ClassWithRequiredParams::class, $result);
        $this->assertSame('hello', $result->required);
    }

    public function testCreateWithMultipleParameters(): void
    {
        $factory = new Factory();

        $result = $factory->create(ClassWithMultipleParams::class, 'a', 42);

        $this->assertInstanceOf(ClassWithMultipleParams::class, $result);
        $this->assertSame('a', $result->name);
        $this->assertSame(42, $result->value);
    }

    public function testCreateWithoutParametersUsesReflection(): void
    {
        $factory = new Factory();

        $result = $factory->create(stdClass::class);

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testCreateAlwaysReturnsNewInstance(): void
    {
        $factory = new Factory();

        $a = $factory->create(ClassWithRequiredParams::class, 'one');
        $b = $factory->create(ClassWithRequiredParams::class, 'two');

        $this->assertNotSame($a, $b);
        $this->assertSame('one', $a->required);
        $this->assertSame('two', $b->required);
    }

    public function testCreateClassWithNoConstructor(): void
    {
        $factory = new Factory();

        $result = $factory->create(stdClass::class, 'ignored');

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testCreateThrowsForNonExistentClass(): void
    {
        $factory = new Factory();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('not found');

        $factory->create('NonExistent\\ClassName');
    }

    public function testCreateDoesNotUseInstances(): void
    {
        $registered = new stdClass();
        $registered->source = 'instance';

        $factory = new Factory(instances: [stdClass::class => $registered]);

        $result = $factory->create(stdClass::class);

        $this->assertNotSame($registered, $result);
    }

    // =========================================================================
    // Immutability
    // =========================================================================

    public function testImmutableAfterConstruction(): void
    {
        $reflection = new \ReflectionClass(Factory::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    // =========================================================================
    // Exception types
    // =========================================================================

    public function testNotFoundExceptionImplementsPsrInterface(): void
    {
        $exception = new NotFoundException('test');

        $this->assertInstanceOf(\Psr\Container\NotFoundExceptionInterface::class, $exception);
    }

    public function testContainerExceptionImplementsPsrInterface(): void
    {
        $exception = new ContainerException('test');

        $this->assertInstanceOf(\Psr\Container\ContainerExceptionInterface::class, $exception);
    }
}

// =========================================================================
// Test helper classes (inline, not worth separate files)
// =========================================================================

/**
 * Test class with optional constructor parameters.
 */
class ClassWithOptionalParams
{
    public function __construct(
        public readonly string $value = 'default'
    ) {
    }
}

/**
 * Test class with required constructor parameters.
 */
class ClassWithRequiredParams
{
    public function __construct(
        public readonly string $required
    ) {
    }
}

/**
 * Test class with multiple constructor parameters.
 */
class ClassWithMultipleParams
{
    public function __construct(
        public readonly string $name,
        public readonly int $value,
    ) {
    }
}
