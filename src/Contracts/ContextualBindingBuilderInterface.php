<?php

/**
 * Interface for contextual bindings builder.
 *
 * This interface defines the contract for a fluent builder that configures
 * contextual bindings within a dependency injection container. Contextual bindings
 * allow specifying different implementations of a dependency based on where
 * it is being injected.
 *
 * @package WPTechnix\DI\Contracts
 * @author WPTechnix <developer@wptechnix.com>
 * @since 0.1.0
 */

declare(strict_types=1);

namespace WPTechnix\DI\Contracts;

use Closure;
use WPTechnix\DI\Contracts\ContainerInterface;
use WPTechnix\DI\Exceptions\BindingException;

/**
 * Interface for contextual bindings builder.
 *
 * Provides a fluent API for defining contextual bindings in the DI container.
 * Contextual bindings allow you to specify that a particular implementation
 * should be used when resolving a dependency within a specific class context.
 *
 * Example usage:
 * ```php
 * $container->when(UserController::class)
 *           ->needs(LoggerInterface::class)
 *           ->give(FileLogger::class);
 * ```
 *
 * @package WPTechnix\DI\Contracts
 */
interface ContextualBindingBuilderInterface
{
    /**
     * Define the abstract target that needs a contextual binding.
     *
     * Specifies which abstract type or interface should receive a
     * contextual implementation when injected into the concrete class(es)
     * defined in the `when()` method.
     *
     * @param string $abstract Abstract type or interface identifier.
     *
     * @return self Returns the builder instance for method chaining.
     */
    public function needs(string $abstract): self;

    /**
     * Define the implementation for the contextual binding.
     *
     * Specifies the concrete implementation that should be used when the
     * abstract type defined in `needs()` is requested within the context
     * of the class(es) defined in `when()`.
     *
     * @param string|Closure(ContainerInterface, array<string, mixed>): object $implementation
     *        The concrete class name or a factory closure that will be used
     *        to create the instance.
     *
     * @return ContainerInterface Returns the container instance after the binding is registered.
     *
     * @throws BindingException When the implementation is invalid.
     */
    public function give(string|Closure $implementation): ContainerInterface;
}
