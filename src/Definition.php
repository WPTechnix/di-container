<?php

declare(strict_types=1);

namespace WPTechnix\DI;

/**
 * The internal representation of a single registered service.
 *
 * A definition is returned by {@see Container::singleton()} and
 * {@see Container::factory()} so that tags can be attached fluently. All other
 * state is managed by the container.
 */
final class Definition
{
    /**
     * The factory callable that creates the service instance.
     *
     * Receives the container and the resolved positional parameters.
     *
     * @var callable(Container, array<int, mixed>): mixed
     */
    private $factory;

    /**
     * Positional constructor parameters passed to the factory.
     *
     * Each entry stores the raw value together with a flag indicating whether
     * the value should be resolved at resolution time: a string is fetched as
     * a service via {@see Container::get()} and a {@see \Closure} is invoked
     * with the container. Only integer keys are supported — named parameters
     * would require reflection.
     *
     * @var list<array{value: mixed, resolve: bool}>
     */
    private array $parameters = [];

    /**
     * Tags attached to this service.
     *
     * Tags allow grouping related services for bulk resolution via
     * {@see Container::tagged()}. Duplicate tags are ignored.
     *
     * @var list<string>
     */
    private array $tags = [];

    /**
     * Extender callables registered for this service.
     *
     * Extenders are applied in registration order at resolution time,
     * each receiving the current instance and the container.
     *
     * @var list<callable(mixed, Container): mixed>
     */
    private array $extenders = [];

    /**
     * Whether the service has been resolved at least once.
     *
     * Once true, shared singletons return a cached instance and no
     * further extenders can be added.
     */
    private bool $resolved = false;

    /**
     * The cached service instance for shared definitions.
     *
     * Remains null until the service is resolved for the first time.
     */
    private mixed $instance = null;

    /**
     * Create a new service definition.
     *
     * @param string $id The unique service identifier.
     * @param callable(Container, array<int, mixed>): mixed $factory Factory that receives
     *     the container and the resolved positional parameters, and returns the service instance.
     * @param bool $shared Whether the instance should be cached after first resolution.
     */
    public function __construct(
        private string $id,
        callable $factory,
        private bool $shared,
    ) {
        $this->factory = $factory;
    }

    /**
     * Attach one or more tags to the service. Duplicate tags are ignored.
     *
     * Tags are used by {@see Container::tagged()} to group related services
     * and resolve them as a collection.
     *
     * @param string ...$tags One or more tag names to attach.
     * @return self The definition instance for fluent chaining.
     */
    public function tag(string ...$tags): self
    {
        foreach ($tags as $tag) {
            if (!in_array($tag, $this->tags, true)) {
                $this->tags[] = $tag;
            }
        }

        return $this;
    }

    /**
     * Check whether the service carries a specific tag.
     *
     * @param string $tag The tag name to check for.
     * @return bool True if the tag is attached, false otherwise.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }

    /**
     * Return all tags attached to the service.
     *
     * @return list<string> The list of attached tag names.
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Append a single positional constructor parameter.
     *
     * Parameters are passed to the factory in the order they are added. When a
     * string or null factory is used, they are spread into the constructor.
     *
     * The {@see $resolve} flag controls how the value is treated at resolution
     * time: when true, a string value is fetched as a service via
     * {@see Container::get()} and a {@see \Closure} value is invoked with the
     * container; otherwise the value is used exactly as given.
     *
     * @param mixed $value The parameter value, or a service id / closure when resolving.
     * @param bool $resolve Whether to resolve the value at resolution time. Defaults to false.
     * @return self The definition instance for fluent chaining.
     */
    public function addParameter(mixed $value, bool $resolve = false): self
    {
        $this->parameters[] = ['value' => $value, 'resolve' => $resolve];

        return $this;
    }

    /**
     * Replace all positional parameters with a plain indexed list of values.
     *
     * Every value is stored with its resolve flag set to false. To resolve
     * specific entries, use {@see addParameter()} with `resolve: true` instead.
     *
     * @param list<mixed> $values The positional parameter values, in order.
     * @return self The definition instance for fluent chaining.
     */
    public function addParameters(array $values): self
    {
        $this->parameters = [];

        foreach ($values as $value) {
            $this->parameters[] = ['value' => $value, 'resolve' => false];
        }

        return $this;
    }

    /**
     * Return the raw, unresolved positional parameters.
     *
     * @return list<array{value: mixed, resolve: bool}> The stored parameters in order.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Return the service identifier.
     *
     * @return string The unique identifier for this service.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Check whether the service is shared (a singleton).
     *
     * Shared services are cached after first resolution; non-shared services
     * produce a new instance on every call to {@see Container::get()}.
     *
     * @return bool True if the instance is cached after first resolution, false otherwise.
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * Check whether the service has been resolved at least once.
     *
     * Once a shared service has been resolved, its instance is frozen and
     * no further extenders can be added.
     *
     * @return bool True if the service has been resolved, false otherwise.
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Return the factory callable for this service.
     *
     * @return callable(Container, array<int, mixed>): mixed The factory that creates the service instance.
     */
    public function getFactory(): callable
    {
        return $this->factory;
    }

    /**
     * Append an extender callable to the decoration chain.
     *
     * Extenders run in registration order when the service is resolved.
     *
     * @param callable(mixed, Container): mixed $extender Callable that receives the
     *     current instance and the container, and returns the decorated instance.
     */
    public function addExtender(callable $extender): void
    {
        $this->extenders[] = $extender;
    }

    /**
     * Return all registered extender callables in registration order.
     *
     * @return list<callable(mixed, Container): mixed> The list of registered extenders.
     */
    public function getExtenders(): array
    {
        return $this->extenders;
    }

    /**
     * Store the resolved instance and mark the service as resolved.
     *
     * Once set, the instance is returned directly on subsequent resolutions
     * for shared services. The service is marked as resolved so that no
     * further extenders can be added.
     *
     * @param mixed $instance The resolved service instance to cache.
     */
    public function setInstance(mixed $instance): void
    {
        $this->instance = $instance;
        $this->resolved = true;
    }

    /**
     * Return the cached instance, if one has been set.
     *
     * Returns null if the service has not been resolved yet.
     *
     * @return mixed The cached instance, or null if not yet resolved.
     */
    public function getInstance(): mixed
    {
        return $this->instance;
    }
}
