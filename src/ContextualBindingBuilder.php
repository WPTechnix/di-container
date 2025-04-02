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
use WPTechnix\DI\Contracts\ContainerInterface;
use WPTechnix\DI\Contracts\ContextualBindingBuilderInterface;

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
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

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
     * @param ContainerInterface $container The container instance.
     * @param array<string> $concrete The concrete classes.
     */
    public function __construct(ContainerInterface $container, array $concrete)
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
     */
    public function give(string|Closure $implementation): ContainerInterface
    {
        foreach ($this->concrete as $concrete) {
            $this->container->addContextualBinding($concrete, $this->abstract, $implementation);
        }

        return $this->container;
    }
}
