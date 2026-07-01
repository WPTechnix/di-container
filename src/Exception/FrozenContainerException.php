<?php

declare(strict_types=1);

namespace WPTechnix\DI\Exception;

/**
 * Thrown when the container is mutated after it has been booted.
 */
final class FrozenContainerException extends ContainerException
{
    /**
     * Create an exception for attempting to boot an already-booted container.
     *
     * @return self A new exception instance with a descriptive message.
     */
    public static function alreadyBooted(): self
    {
        return new self("The container has already been booted.");
    }

    /**
     * Create an exception for adding a provider after the container has been booted.
     *
     * @return self A new exception instance with a descriptive message.
     */
    public static function cannotAddProvider(): self
    {
        return new self(
            "Cannot register a service provider after the container has been booted.",
        );
    }
}
