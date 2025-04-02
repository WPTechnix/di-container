<?php

/**
 * Container Exception Interface.
 *
 * @package WPTechnix\DI\Contracts
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Contracts;

use Psr\Container\ContainerExceptionInterface as PsrContainerExceptionInterface;

/**
 * Container Exception Interface.
 *
 * @package WPTechnix\DI\Contracts
 * @author WPTechnix <developers@wptechnix.com>
 */
interface ContainerExceptionInterface extends PsrContainerExceptionInterface
{
    /**
     * Gets the service identifier.
     *
     * @return string
     */
    public function getServiceId(): string;

    /**
     * Gets the error context.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * Gets the dependency chain.
     *
     * @return array<string>
     */
    public function getDependencyChain(): array;

    /**
     * Gets a formatted string representation of the exception for detailed debugging.
     *
     * @return string
     */
    public function getDebugInfo(): string;
}
