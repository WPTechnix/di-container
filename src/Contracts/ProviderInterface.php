<?php

/**
 * Service Provider Interface.
 *
 * @package WPTechnix\DI\Contracts
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Contracts;

/**
 * Service Provider Interface.
 *
 * @package WPTechnix\DI\Contracts
 * @author WPTechnix <developers@wptechnix.com>
 */
interface ProviderInterface
{
    /**
     * Register services.
     *
     * @param ContainerInterface $container The DI container.
     */
    public function register(ContainerInterface $container): void;

    /**
     * Boot services.
     *
     * @param ContainerInterface $container The DI container.
     */
    public function boot(ContainerInterface $container): void;
}
