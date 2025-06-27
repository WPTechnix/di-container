<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

use WPTechnix\DI\Container;
use WPTechnix\DI\Contracts\ProviderInterface;

class ServiceProvider implements ProviderInterface
{
    public function __construct(
        protected Container $container
    ) {}

    public function register(): void
    {
        $this->container->singleton(TestInterface::class, SimpleImplementation::class);
        $this->container->singleton(AnotherInterface::class, ValueImplementation::class);
    }

    public function boot(): void
    {
        // Not used in tests
    }
}

