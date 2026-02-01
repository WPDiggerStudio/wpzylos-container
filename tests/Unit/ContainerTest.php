<?php

declare(strict_types=1);

namespace WPZylos\Framework\Container\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;
use WPZylos\Framework\Container\Container;
use WPZylos\Framework\Container\Exceptions\ContainerException;
use WPZylos\Framework\Container\Exceptions\NotFoundException;

/**
 * Tests for Container class.
 */
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testBindAndResolve(): void
    {
        $this->container->bind('foo', fn() => 'bar');

        $this->assertSame('bar', $this->container->get('foo'));
    }

    public function testBindReturnsDefinition(): void
    {
        $definition = $this->container->bind('foo', fn() => 'bar');

        $this->assertSame('foo', $definition->getId());
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $this->container->singleton(stdClass::class, fn() => new stdClass());

        $a = $this->container->get(stdClass::class);
        $b = $this->container->get(stdClass::class);

        $this->assertSame($a, $b);
    }

    public function testFactoryReturnsNewInstance(): void
    {
        $this->container->bind(stdClass::class, fn() => new stdClass());

        $a = $this->container->get(stdClass::class);
        $b = $this->container->get(stdClass::class);

        $this->assertNotSame($a, $b);
    }

    public function testHasReturnsTrueForBoundService(): void
    {
        $this->container->bind('foo', fn() => 'bar');

        $this->assertTrue($this->container->has('foo'));
    }

    public function testHasReturnsFalseForUnboundService(): void
    {
        $this->assertFalse($this->container->has('nonexistent'));
    }

    public function testHasReturnsTrueForExistingClass(): void
    {
        $this->assertTrue($this->container->has(stdClass::class));
    }

    public function testAliasResolvesToOriginal(): void
    {
        $this->container->singleton('original', fn() => new stdClass());
        $this->container->alias('aliased', 'original');

        $original = $this->container->get('original');
        $aliased = $this->container->get('aliased');

        $this->assertSame($original, $aliased);
    }

    public function testNotFoundThrowsException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('nonexistent.service.name');
    }

    public function testCircularDependencyThrowsException(): void
    {
        $this->container->bind('a', fn($c) => $c->get('b'));
        $this->container->bind('b', fn($c) => $c->get('a'));

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency');

        $this->container->get('a');
    }

    public function testTaggedReturnsTaggedServices(): void
    {
        $this->container->bind('cache.file', fn() => 'file')->addTag('cache');
        $this->container->bind('cache.redis', fn() => 'redis')->addTag('cache');

        $caches = $this->container->tagged('cache');

        $this->assertCount(2, $caches);
        $this->assertContains('file', $caches);
        $this->assertContains('redis', $caches);
    }

    public function testForgetRemovesService(): void
    {
        $this->container->bind('foo', fn() => 'bar');

        $this->assertTrue($this->container->has('foo'));
        $this->container->forget('foo');
        $this->assertFalse($this->container->has('foo'));
    }

    public function testKeysReturnsRegisteredIds(): void
    {
        $this->container->bind('foo', fn() => 'a');
        $this->container->bind('bar', fn() => 'b');

        $keys = $this->container->keys();

        $this->assertContains('foo', $keys);
        $this->assertContains('bar', $keys);
    }

    public function testAddIsAliasForBind(): void
    {
        $definition = $this->container->add('foo', fn() => 'bar');

        $this->assertSame('bar', $this->container->get('foo'));
        $this->assertFalse($definition->isShared());
    }

    public function testAddSharedIsAliasForSingleton(): void
    {
        $definition = $this->container->addShared('foo', fn() => new stdClass());

        $this->assertTrue($definition->isShared());
    }
}
