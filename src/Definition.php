<?php

declare(strict_types=1);

namespace WPZylos\Framework\Container;

/**
 * Service definition.
 *
 * Represents a bound service in the container.
 *
 * @package WPZylos\Framework\Container
 */
class Definition
{
    /**
     * @var string Service identifier
     */
    private string $id;

    /**
     * @var callable|string Concrete factory or class name
     */
    private mixed $concrete;

    /**
     * @var bool Whether this is a shared (singleton) instance
     */
    private bool $shared = false;

    /**
     * @var mixed|null Resolved instance (for singletons)
     */
    private mixed $resolved = null;

    /**
     * @var bool Whether the service has been resolved
     */
    private bool $isResolved = false;

    /**
     * @var string[] Tags assigned to this definition
     */
    private array $tags = [];

    /**
     * Create a new definition.
     *
     * @param string $id Service identifier
     * @param callable|string|null $concrete Concrete implementation
     */
    public function __construct(string $id, callable|string|null $concrete = null)
    {
        $this->id = $id;
        $this->concrete = $concrete ?? $id;
    }

    /**
     * Get the service identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the concrete implementation.
     *
     * @return callable|string
     */
    public function getConcrete(): callable|string
    {
        return $this->concrete;
    }

    /**
     * Mark this service as shared (singleton).
     *
     * @return static
     */
    public function share(): static
    {
        $this->shared = true;

        return $this;
    }

    /**
     * Check if this is a shared service.
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * Set the resolved instance.
     *
     * @param mixed $instance Resolved instance
     *
     * @return void
     */
    public function setResolved(mixed $instance): void
    {
        $this->resolved = $instance;
        $this->isResolved = true;
    }

    /**
     * Get the resolved instance.
     *
     * @return mixed
     */
    public function getResolved(): mixed
    {
        return $this->resolved;
    }

    /**
     * Check if the service has been resolved.
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    /**
     * Add a tag to this definition.
     *
     * @param string $tag Tag name
     *
     * @return static
     */
    public function addTag(string $tag): static
    {
        if (!in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Get all tags.
     *
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Check if this definition has a specific tag.
     *
     * @param string $tag Tag to check
     *
     * @return bool
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }
}
