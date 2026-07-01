<?php

declare(strict_types=1);

namespace WPTechnix\DI\Exception;

/**
 * Thrown when a singleton is extended after it has already been resolved.
 */
final class ServiceAlreadyResolvedException extends ContainerException
{
    /**
     * Create an exception for a service that was extended after resolution.
     *
     * @param string $id The service identifier that was already resolved.
     * @return self A new exception instance with a descriptive message.
     */
    public static function forId(string $id): self
    {
        return new self(
            sprintf(
                'Service "%s" has already been resolved and can no longer be extended.',
                $id,
            ),
        );
    }
}
