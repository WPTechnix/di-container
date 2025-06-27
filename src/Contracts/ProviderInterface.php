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
     */
    public function register(): void;
}
