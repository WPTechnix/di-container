<?php

declare(strict_types=1);

namespace WPTechnix\DI\Exception;

/**
 * Thrown when an identifier is registered more than once without an explicit
 * override.
 */
final class DuplicateServiceException extends ContainerException
{
    /**
     * Create an exception for a duplicate identifier.
     *
     * @param string $id The service identifier that was registered twice.
     * @return self A new exception instance with a descriptive message.
     */
    public static function forId(string $id): self
    {
        return new self(
            sprintf(
                'A service with the identifier "%s" is already registered. Pass override: true to replace it.',
                $id,
            ),
        );
    }
}
