<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

use WPTechnix\DI\Container;
use WPTechnix\DI\ServiceProvider;

final class UuidProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->factory(
            UuidGenerator::class,
            static fn(): UuidGenerator => new UuidGenerator(),
        );
    }

    public function boot(Container $container): void
    {
    }
}
