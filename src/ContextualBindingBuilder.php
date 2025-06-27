<?php

/**
 * Class for building contextual bindings.
 *
 * @package WPTechnix\DI
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI;

use Closure;
use WPTechnix\DI\Contracts\ContextualBindingBuilderInterface;
use WPTechnix\DI\Exceptions\BindingException;

/**
 * Builder for contextual bindings.
 *
 * @package WPTechnix\DI
 * @author WPTechnix <developers@wptechnix.com>
 */
class ContextualBindingBuilder implements ContextualBindingBuilderInterface
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * The concrete instances that need contextual bindings.
     *
     * @var array<string>
     */
    protected array $concrete;

    /**
     * The abstract type that is being requested.
     *
     * @var string
     */
    protected string $abstract;

    /**
     * Create a new contextual binding builder.
     *
     * @param Container $container The container instance.
     * @param array<string> $concrete The concrete classes.
     */
    public function __construct(Container $container, array $concrete)
    {
        $this->container = $container;
        $this->concrete  = $concrete;
    }

    /**
     * {@inheritDoc}
     */
    public function needs(string $abstract): self
    {
        $this->abstract = $abstract;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @param string|Closure(Container, array<string, mixed>): object $implementation
     *         The concrete class name or a factory closure that will be used
     *         to create the instance.
     *
     * @return Container Returns the container instance after the binding is registered.
     *
     * @throws BindingException When the implementation is invalid.
     */
    public function give(string|Closure $implementation): Container
    {
        foreach ($this->concrete as $concrete) {
            $this->container->addContextualBinding($concrete, $this->abstract, $implementation);
        }

        return $this->container;
    }
}
