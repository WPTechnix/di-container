<?php

declare(strict_types=1);

namespace WPTechnix\DI;

/**
 * A lightweight unit of bootstrapping.
 *
 * Providers are queued on the container and run in two phases: every provider
 * is registered first, then every provider is booted. This guarantees that the
 * boot phase can rely on every service registered by every other provider.
 */
interface ServiceProvider
{
    /**
     * Bind services into the container. Do not resolve services here.
     */
    public function register(Container $container): void;

    /**
     * Wire things together once every provider has been registered.
     */
    public function boot(Container $container): void;
}
