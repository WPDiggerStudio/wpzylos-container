<?php

declare(strict_types=1);

namespace WPZylos\Framework\Container\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPZylos\Framework\Container\Container;
use WPZylos\Framework\Container\Exceptions\ContainerException;
use WPZylos\Framework\Container\Exceptions\NotFoundException;

// Test classes for auto-wiring
class NoDependency
{
    public string $value = 'works';
}

class SingleDependency
{
    public function __construct(public NoDependency $dep)
    {
    }
}

class MultipleDependencies
{
    public function __construct(
        public NoDependency $first,
        public SingleDependency $second
    ) {
    }
}

class OptionalDependency
{
    public function __construct(
        public NoDependency $required,
        public ?SingleDependency $optional = null,
        public string $default = 'default'
    ) {
    }
}

abstract class AbstractClass
{
}

/**
 * Tests for Container auto-wiring.
 */
class AutowiringTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testAutowiresClassWithNoDependencies(): void
    {
        $instance = $this->container->get(NoDependency::class);

        $this->assertInstanceOf(NoDependency::class, $instance);
        $this->assertSame('works', $instance->value);
    }

    public function testAutowiresClassWithSingleDependency(): void
    {
        $instance = $this->container->get(SingleDependency::class);

        $this->assertInstanceOf(SingleDependency::class, $instance);
        $this->assertInstanceOf(NoDependency::class, $instance->dep);
    }

    public function testAutowiresClassWithMultipleDependencies(): void
    {
        $instance = $this->container->get(MultipleDependencies::class);

        $this->assertInstanceOf(MultipleDependencies::class, $instance);
        $this->assertInstanceOf(NoDependency::class, $instance->first);
        $this->assertInstanceOf(SingleDependency::class, $instance->second);
    }

    public function testHandlesOptionalDependencies(): void
    {
        $instance = $this->container->get(OptionalDependency::class);

        $this->assertInstanceOf(OptionalDependency::class, $instance);
        $this->assertInstanceOf(NoDependency::class, $instance->required);
        // Container autowires optional dependencies when possible
        $this->assertInstanceOf(SingleDependency::class, $instance->optional);
        $this->assertSame('default', $instance->default);
    }

    public function testThrowsForAbstractClass(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('not instantiable');

        $this->container->get(AbstractClass::class);
    }

    public function testThrowsForNonExistentClass(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('NonExistentClass12345');
    }
}
