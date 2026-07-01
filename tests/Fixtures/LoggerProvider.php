<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

use WPTechnix\DI\Container;
use WPTechnix\DI\ServiceProvider;

/**
 * Registers a logger and, during boot, consumes a service registered by a
 * different provider to prove that the boot phase sees every registration.
 */
final class LoggerProvider implements ServiceProvider
{
    public bool $sawConsumer = false;

    public function register(Container $container): void
    {
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
    }

    public function boot(Container $container): void
    {
        $this->sawConsumer = $container->has(UuidGenerator::class);
    }
}
