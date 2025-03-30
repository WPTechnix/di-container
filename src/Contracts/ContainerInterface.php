<?php

/**
 * Interface for dependency injection containers.
 *
 * This interface defines the contract for a dependency injection container that extends
 * the PSR-11 ContainerInterface with additional functionality for managing object instances,
 * factory methods, contextual bindings, and service providers.
 *
 * @package WPTechnix\DI\Contracts
 * @author WPTechnix <developer@wptechnix.com>
 * @since 0.1.0
 */

declare(strict_types=1);

namespace WPTechnix\DI\Contracts;

use Closure;
use Psr\Container\NotFoundExceptionInterface;
use WPTechnix\DI\Exceptions\ContainerException;
use Psr\Container\ContainerInterface as PSRContainerInterface;

/**
 * Interface for dependency injection containers.
 *
 * Provides methods for registering, resolving, and managing service dependencies
 * in a standardized way. This container implementation supports:
 * - Service registration via bindings
 * - Singleton and factory patterns
 * - Contextual bindings
 * - Service tagging
 * - Extension of existing services
 * - Service providers
 *
 * @package WPTechnix\DI\Contracts
 * @author WPTechnix <developer@wptechnix.com>
 */
interface ContainerInterface extends PSRContainerInterface
{
    /**
     * Register an existing instance as a singleton.
     *
     * This method allows registering an already instantiated object within the container
     * as a singleton, making it available for future retrievals.
     *
     * @template T of object
     *
     * @param string|class-string<T> $id Service identifier or class name.
     * @param object $instance Instance to register.
     *
     * @phpstan-param ( $id is class-string<T> ? T : object ) $instance Instance to register.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function instance(string $id, object $instance): static;

    /**
     * Check if a service has been explicitly bound.
     *
     * Unlike the standard 'has' method which checks if a service can be resolved,
     * this method strictly checks if a binding has been explicitly registered.
     *
     * @param string $id Service identifier.
     *
     * @return bool Returns true if the identifier has been bound, false otherwise.
     */
    public function hasBinding(string $id): bool;

    /**
     * Resets the container to its initial state.
     *
     * Removes all bindings, instances, aliases, and other registered services
     * from the container, effectively resetting it to a clean state.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function reset(): static;

    /**
     * Binds a factory callback.
     *
     * Factory bindings provide a way to define how an object should be created
     * each time it is requested from the container.
     *
     * @template T of object
     * @param string|class-string<T> $id Service identifier.
     * @param ( $id is class-string<T> ?
     *          (Closure(self, array<string, mixed>): T ) :
     *          (Closure(self, array<string, mixed>): object)
     *        ) $factory Factory callback.
     * @param bool $override True to override preregistered implementation if there's any.
     *                       Default false.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function factory(string $id, Closure $factory, bool $override = false): static;

    /**
     * Binds an interface or class to a concrete implementation.
     *
     * This method allows associating an abstract type (interface or class) with
     * a concrete implementation that should be instantiated when the abstract type
     * is requested.
     *
     * @template T of object
     * @param string|class-string<T> $id Abstract type identifier.
     * @param ( $id is class-string<T> ?
     *          ( class-string<T>|(Closure(self, array<string, mixed>): T) ) :
     *         ( string|(Closure(self, array<string, mixed>): object) )
     *       ) $implementation Concrete class name or factory closure.
     * @param bool $shared Whether to share the instance across multiple resolutions.
     * @param bool $override True to override preregistered implementation if there's any.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function bind(
        string $id,
        string|Closure $implementation,
        bool $shared = false,
        bool $override = false
    ): static;

    /**
     * Begin a new contextual binding.
     *
     * Contextual bindings allow for different implementations of a dependency
     * based on the context in which it is being resolved.
     *
     * @param string|array<string> $concrete The concrete class(es) that should receive a contextual binding.
     *
     * @return ContextualBindingBuilderInterface A builder interface to specify the contextual binding.
     */
    public function when(string|array $concrete): ContextualBindingBuilderInterface;

    /**
     * Add a contextual binding to the container.
     *
     * Direct method to register a contextual binding without using the builder pattern.
     *
     * @param string         $concrete       The concrete class that receives the contextual binding.
     * @param string         $abstract       The abstract type that should be resolved differently.
     * @param string|Closure(self, array<string, mixed>): object $implementation The implementation or factory to use.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function addContextualBinding(string $concrete, string $abstract, string|Closure $implementation): static;

    /**
     * Resolves a type from the container with optional parameters.
     *
     * Creates an instance of the requested type with any bindings applied and
     * dependencies automatically injected. Additional constructor parameters
     * can be provided.
     *
     * @template T of object
     * @param string|class-string<T> $id Service identifier.
     * @param array<string, mixed> $parameters Additional constructor parameters.
     *
     * @return object The resolved instance.
     *
     * @phpstan-return ( $id is class-string<T> ? T : object )
     */
    public function resolve(string $id, array $parameters = []): object;

    /**
     * Pass the container to a service provider for registration.
     *
     * Service providers offer a way to organize related container bindings
     * and bootstrapping logic in separate classes.
     *
     * @param ProviderInterface|class-string<ProviderInterface> $provider Service Provider instance or class name.
     *
     * @return static Returns the container instance for method chaining.
     *
     * @throws ContainerException When provider registration fails.
     */
    public function provider(ProviderInterface|string $provider): static;

    /**
     * Retrieve a service from the container.
     *
     * Implementation of the PSR-11 get method to resolve an entry from the container.
     *
     * @template T of object
     *
     * @param string|class-string<T> $id Identifier of the entry to look for.
     *
     * @return ($id is class-string<T> ? T : object) The resolved entry.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     */
    public function get(string $id);

    /**
     * Binds a singleton implementation.
     *
     * Registers a binding that will return the same instance each time
     * it is resolved from the container.
     *
     * @template T of object
     *
     * @param string|class-string<T> $id Service identifier.
     * @param ( $id is class-string<T> ?
     *          ( null|class-string<T>|(Closure(self, array<string, mixed>): T) ) :
     *          ( null|string|(Closure(self, array<string, mixed>): object) )
     *        ) $implementation Concrete implementation or factory.
     * @param bool $override True to override preregistered implementation if there's any.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function singleton(string $id, null|string|Closure $implementation = null, bool $override = false): static;

    /**
     * Resolve all services under a specific tag.
     *
     * Retrieves and instantiates all services that have been registered
     * under the given tag.
     *
     * @template T of object
     * @param string|class-string<T> $tag Tag name.
     * @return ( $tag is class-string<T> ? array<T> : array<object> ) Array of resolved services.
     */
    public function resolveTagged(string $tag): array;

    /**
     * Tag several services under a common tag.
     *
     * Tags provide a way to group related services so they can be resolved
     * collectively later.
     *
     * @template T of object
     * @param string|class-string<T> $tag Tag name.
     * @param ( $tag is class-string<T> ?
     *          array<class-string<T>> :
     *          array<string>
     *        ) $ids Array of service identifiers to tag.
     * @param bool $merge Whether to merge the services on given tag or replace them by the given service ids list.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function tag(string $tag, array $ids, bool $merge = true): static;

    /**
     * Untag service(s) under a common tag.
     *
     * Removes one or more services from a specific tag.
     *
     * @template T of object
     * @param string|class-string<T> $tag Tag name.
     * @param ( $tag is class-string<T> ?
     *          array<class-string<T>>|class-string<T> :
     *          array<string>|string
     *        ) $ids Service identifier(s) to untag.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function untag(string $tag, array|string $ids): static;

    /**
     * Extend an existing binding.
     *
     * Allows decorating or modifying a service after it has been resolved
     * from the container.
     *
     * @template T of object
     * @param string|class-string<T> $id Service identifier.
     * @param ( $id is class-string<T> ?
     *          ( Closure(T, self): T ) :
     *          ( Closure(object, self): object )
     *        ) $extension Extension callback.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function extend(string $id, Closure $extension): static;

    /**
     * Remove a binding from the container.
     *
     * Unregisters a previously bound service identifier from the container.
     *
     * @param string $id Service identifier.
     *
     * @return static Returns the container instance for method chaining.
     */
    public function unbind(string $id): static;

    /**
     * Remove contextual bindings for a concrete class.
     *
     * @param string $concrete The concrete class.
     * @param string|null $abstract Optional. The abstract type to remove. If null, removes all.
     *
     * @return static
     */
    public function forgetWhen(string $concrete, ?string $abstract = null): static;
}
