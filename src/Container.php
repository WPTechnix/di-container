<?php

declare(strict_types=1);

namespace WPTechnix\DI;

use Closure;
use WPTechnix\DI\Exception\ContainerException;
use WPTechnix\DI\Exception\DuplicateServiceException;
use WPTechnix\DI\Exception\FrozenContainerException;
use WPTechnix\DI\Exception\ServiceAlreadyResolvedException;
use WPTechnix\DI\Exception\ServiceNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * A minimal, explicit PSR-11 dependency injection container for PHP.
 *
 * Services are registered manually as either singletons (created once and
 * cached) or factories (created fresh on every resolution). There is no
 * autowiring, reflection, attributes or magic involved: what you register is
 * exactly what you get.
 */
final class Container implements ContainerInterface
{
    /**
     * Registered service definitions, keyed by identifier.
     *
     * Each definition holds the factory, shared flag, tags, extenders, and
     * the resolved instance (if applicable).
     *
     * @var array<string, Definition>
     */
    private array $definitions = [];

    /**
     * Alias map pointing alias identifiers to their target identifiers.
     *
     * Aliases are resolved transitively at lookup time.
     *
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * Queued service providers awaiting registration and booting.
     *
     * Providers are consumed in FIFO order during {@see boot()}.
     *
     * @var list<ServiceProvider>
     */
    private array $providers = [];

    /**
     * Stack of identifiers currently being resolved.
     *
     * Used to detect circular dependencies during service resolution.
     *
     * @var array<string, true>
     */
    private array $resolving = [];

    /**
     * Whether the container has been booted.
     *
     * Once true, no new providers or definitions can be added, and
     * calling {@see boot()} again throws an exception.
     */
    private bool $booted = false;

    /**
     * Register a shared service.
     *
     * The factory is invoked lazily the first time the service is resolved and
     * the resulting instance is cached for every subsequent resolution.
     *
     * @param string $id The service identifier.
     * @param string|callable(Container, array<int, mixed>): mixed|null $resolver How to build the
     *     service: a callable that receives the container and resolved parameters, a
     *     class-string to instantiate, or null to instantiate the class named by the identifier.
     * @param bool $override Whether to replace an existing registration. Defaults to false.
     * @return Definition The definition object, allowing fluent tag and parameter attachment.
     *
     * @throws DuplicateServiceException When the identifier already exists and override is false.
     * @throws ContainerException When a string or null resolver refers to a class that does not exist.
     */
    public function singleton(
        string $id,
        string|callable|null $resolver = null,
        bool $override = false,
    ): Definition {
        return $this->register($id, $resolver, true, $override);
    }

    /**
     * Register a factory service.
     *
     * The resolver is invoked on every resolution and the result is never
     * cached, so each call to {@see self::get()} returns a fresh instance.
     *
     * @param string $id The service identifier.
     * @param string|callable(Container, array<int, mixed>): mixed|null $resolver How to build the
     *     service: a callable that receives the container and resolved parameters, a
     *     class-string to instantiate, or null to instantiate the class named by the identifier.
     * @param bool $override Whether to replace an existing registration. Defaults to false.
     * @return Definition The definition object, allowing fluent tag and parameter attachment.
     *
     * @throws DuplicateServiceException When the identifier already exists and override is false.
     * @throws ContainerException When a string or null resolver refers to a class that does not exist.
     */
    public function factory(
        string $id,
        string|callable|null $resolver = null,
        bool $override = false,
    ): Definition {
        return $this->register($id, $resolver, false, $override);
    }

    /**
     * Shared registration logic for singletons and factories.
     *
     * Validates the identifier, removes any pre-existing alias, creates a new
     * definition, and stores it in the definitions map.
     *
     * @param string $id The service identifier.
     * @param string|callable(Container, array<int, mixed>): mixed|null $resolver A callable,
     *     a class-string to instantiate, or null to instantiate the class named by the identifier.
     * @param bool $shared Whether the instance should be cached after first resolution.
     * @param bool $override Whether to replace an existing registration.
     * @return Definition The newly created definition.
     *
     * @throws DuplicateServiceException When the identifier already exists and override is false.
     * @throws ContainerException When a string or null resolver refers to a class that does not exist.
     */
    private function register(
        string $id,
        string|callable|null $resolver,
        bool $shared,
        bool $override,
    ): Definition {
        if (
            !$override &&
            ($this->hasDefinition($id) || isset($this->aliases[$id]))
        ) {
            throw DuplicateServiceException::forId($id);
        }

        unset($this->aliases[$id]);

        $definition = new Definition($id, $this->normalizeFactory($id, $resolver), $shared);
        $this->definitions[$id] = $definition;

        return $definition;
    }

    /**
     * Normalize a registration target into a factory callable.
     *
     * A callable is returned unchanged. A string or null is treated as a class
     * name — null falls back to the service identifier — and is wrapped in a
     * closure that instantiates the class, spreading the resolved positional
     * parameters into the constructor. The class is validated up front with
     * {@see class_exists()} so unknown classes fail fast at registration time.
     *
     * @param string $id The service identifier, used as the class name when the resolver is null.
     * @param string|callable(Container, array<int, mixed>): mixed|null $resolver The registration target.
     * @return callable(Container, array<int, mixed>): mixed The normalized factory callable.
     *
     * @throws ContainerException When a string or null resolver refers to a class that does not exist.
     */
    private function normalizeFactory(string $id, string|callable|null $resolver): callable
    {
        if (is_callable($resolver)) {
            return $resolver;
        }

        /** @var class-string $className */
        $className = $resolver ?? $id;

        if (!class_exists($className)) {
            throw ContainerException::invalidFactoryClass($className);
        }

        return static fn(Container $c, array $params): object => new $className(...$params);
    }

    /**
     * Point an identifier at another, already registered, identifier.
     *
     * Aliases never own a factory; resolving an alias resolves its target.
     * An alias cannot reference itself, and it cannot collide with an
     * existing definition or another alias without an explicit override.
     *
     * @param string $alias The alias identifier to create.
     * @param string $id The target identifier the alias should resolve to.
     *
     * @throws ContainerException When the alias references itself.
     * @throws DuplicateServiceException When the alias identifier is already registered.
     */
    public function alias(string $alias, string $id): void
    {
        if ($alias === $id) {
            throw new ContainerException(
                sprintf('An alias cannot reference itself: "%s".', $alias),
            );
        }

        if ($this->hasDefinition($alias) || isset($this->aliases[$alias])) {
            throw DuplicateServiceException::forId($alias);
        }

        $this->aliases[$alias] = $id;
    }

    /**
     * Decorate a registered service before it is resolved.
     *
     * Extenders run in registration order. The extender receives the current
     * instance and the container, and must return the (possibly replaced)
     * instance. A singleton that has already been resolved can no longer be
     * extended.
     *
     * @param string $id The service identifier to decorate.
     * @param callable(mixed, Container): mixed $extender Callable that receives the
     *     current instance and the container, and returns the decorated instance.
     *
     * @throws ServiceNotFoundException When no service is registered for the given identifier.
     * @throws ServiceAlreadyResolvedException When the service is a singleton that has already been resolved.
     * @throws ContainerException When the alias chain is circular.
     */
    public function extend(string $id, callable $extender): void
    {
        $resolvedId = $this->resolveAlias($id);
        $definition = $this->definitions[$resolvedId] ?? null;

        if ($definition === null) {
            throw ServiceNotFoundException::forId($id);
        }

        if ($definition->isResolved()) {
            throw ServiceAlreadyResolvedException::forId($resolvedId);
        }

        $definition->addExtender($extender);
    }

    /**
     * Resolve every service that carries the given tag, in registration order.
     *
     * Iterates over all definitions and collects those whose tags include the
     * requested value. Services are resolved at call time, so tagged factories
     * produce a fresh instance on every call.
     *
     * @param string $tag The tag to filter services by.
     * @return list<mixed> The resolved service instances in registration order.
     */
    public function tagged(string $tag): array
    {
        $services = [];

        foreach ($this->definitions as $id => $definition) {
            if ($definition->hasTag($tag)) {
                $services[] = $this->get($id);
            }
        }

        return $services;
    }

    /**
     * Queue a service provider to be registered and booted by {@see self::boot()}.
     *
     * Multiple providers can be chained fluently. Providers cannot be added
     * after the container has been booted.
     *
     * @param ServiceProvider $provider The provider to queue.
     * @return self The container instance for fluent chaining.
     *
     * @throws FrozenContainerException When the container has already been booted.
     */
    public function provider(ServiceProvider $provider): self
    {
        if ($this->booted) {
            throw FrozenContainerException::cannotAddProvider();
        }

        $this->providers[] = $provider;

        return $this;
    }

    /**
     * Register every queued provider, then boot every queued provider.
     *
     * All providers are registered before any provider is booted, so the boot
     * phase has access to every registered service. The container can only be
     * booted once; subsequent calls throw an exception.
     *
     * @throws FrozenContainerException When the container has already been booted.
     */
    public function boot(): void
    {
        if ($this->booted) {
            throw FrozenContainerException::alreadyBooted();
        }

        $this->booted = true;

        foreach ($this->providers as $provider) {
            $provider->register($this);
        }

        foreach ($this->providers as $provider) {
            $provider->boot($this);
        }
    }

    /**
     * Check whether a service or alias is registered in the container.
     *
     * Returns true if the identifier has been registered as a service, or if
     * it is an alias pointing to a registered service. Dangling aliases whose
     * target has not been registered return false.
     *
     * @param string $id The service or alias identifier to check.
     * @return bool True if the service is registered and resolvable, false otherwise.
     *
     * @throws ContainerException When the alias chain is circular.
     */
    public function has(string $id): bool
    {
        if ($this->hasDefinition($id)) {
            return true;
        }

        if (isset($this->aliases[$id])) {
            return $this->hasDefinition($this->resolveAlias($id));
        }

        return false;
    }

    /**
     * Resolve a service by its identifier or alias.
     *
     * If the identifier is an alias, it is resolved through the chain to its
     * target. Shared services are cached after first resolution; factory
     * services produce a new instance on every call.
     *
     * @template T of object
     * @param string|class-string<T> $id The service or alias identifier to resolve.
     * @return ($id is class-string<T> ? T : mixed) The resolved service instance.
     *
     * @throws ServiceNotFoundException When no service is registered for the given identifier.
     * @throws ContainerException When a circular dependency is detected during resolution.
     */
    public function get(string $id): mixed
    {
        $resolvedId = $this->resolveAlias($id);
        $definition = $this->definitions[$resolvedId] ?? null;

        if ($definition === null) {
            throw ServiceNotFoundException::forId($id);
        }

        return $this->resolve($definition);
    }

    /**
     * Resolve a service definition to its concrete instance.
     *
     * For shared definitions that have already been resolved, the cached
     * instance is returned immediately. Otherwise any positional parameters are
     * resolved, the factory is invoked with the container and those parameters,
     * all registered extenders are applied in order, and the result is cached
     * if the definition is shared. Circular dependencies are detected by
     * tracking identifiers currently on the resolution stack.
     *
     * @param Definition $definition The service definition to resolve.
     * @return mixed The resolved service instance after all extenders have been applied.
     *
     * @throws ContainerException When a circular dependency is detected.
     * @throws ServiceNotFoundException When a resolvable string parameter names an unregistered service.
     */
    private function resolve(Definition $definition): mixed
    {
        if ($definition->isShared() && $definition->isResolved()) {
            return $definition->getInstance();
        }

        $id = $definition->getId();

        if (isset($this->resolving[$id])) {
            throw ContainerException::circularDependency([
                ...array_keys($this->resolving),
                $id,
            ]);
        }

        $this->resolving[$id] = true;

        try {
            $resolvedParams = [];

            foreach ($definition->getParameters() as $param) {
                $value = $param['value'];

                if ($param['resolve']) {
                    if (is_string($value)) {
                        $value = $this->get($value);
                    } elseif ($value instanceof Closure) {
                        $value = $value($this);
                    }
                }

                $resolvedParams[] = $value;
            }

            $factory = $definition->getFactory();
            $instance = $factory($this, $resolvedParams);

            foreach ($definition->getExtenders() as $extender) {
                $instance = $extender($instance, $this);
            }
        } finally {
            unset($this->resolving[$id]);
        }

        if ($definition->isShared()) {
            $definition->setInstance($instance);
        }

        return $instance;
    }

    /**
     * Check whether a definition exists for the given identifier.
     *
     * This method checks only direct definitions and does not consult
     * aliases. It is used internally by {@see has()} and the registration
     * logic.
     *
     * @param string $id The service identifier to check.
     * @return bool True if a definition exists, false otherwise.
     */
    private function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    /**
     * Resolve an identifier through any chain of aliases to its ultimate target.
     *
     * If the identifier is not an alias it is returned unchanged. If it is
     * part of a chain, each alias is resolved in sequence until a concrete
     * identifier is found. Circular alias chains are detected and rejected
     * with an exception.
     *
     * @param string $id The identifier (or alias) to resolve.
     * @return string The resolved target identifier that has a definition.
     *
     * @throws ContainerException When a circular alias chain is detected.
     */
    private function resolveAlias(string $id): string
    {
        /** @var array<string, true> $seen */
        $seen = [];

        while (isset($this->aliases[$id])) {
            if (isset($seen[$id])) {
                throw ContainerException::circularAlias([
                    ...array_keys($seen),
                    $id,
                ]);
            }

            $seen[$id] = true;
            $id = $this->aliases[$id];
        }

        return $id;
    }
}
