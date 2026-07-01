<?php

declare(strict_types=1);

namespace WPTechnix\DI\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Thrown when a requested identifier has not been registered.
 */
final class ServiceNotFoundException extends ContainerException implements
    NotFoundExceptionInterface
{
    /**
     * Create an exception for an unregistered service identifier.
     *
     * @param string $id The service identifier that was not found.
     * @return self A new exception instance with a descriptive message.
     */
    public static function forId(string $id): self
    {
        return new self(
            sprintf('Service "%s" is not registered in the container.', $id),
        );
    }
}
