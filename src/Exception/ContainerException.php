<?php

declare(strict_types=1);

namespace WPTechnix\DI\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Base exception for every error raised by the container.
 */
class ContainerException extends RuntimeException implements
    ContainerExceptionInterface
{
    /**
     * @param list<string> $chain
     */
    public static function circularDependency(array $chain): self
    {
        return new self(
            sprintf(
                "Circular dependency detected while resolving: %s.",
                implode(" -> ", $chain),
            ),
        );
    }

    /**
     * @param list<string> $chain
     */
    public static function circularAlias(array $chain): self
    {
        return new self(
            sprintf("Circular alias detected: %s.", implode(" -> ", $chain)),
        );
    }

    /**
     * Create an exception for a string or null factory whose class does not exist.
     *
     * @param string $class The fully-qualified class name that could not be found.
     * @return self A new exception instance with a descriptive message.
     */
    public static function invalidFactoryClass(string $class): self
    {
        return new self(
            sprintf(
                'Cannot register service: class "%s" does not exist.',
                $class,
            ),
        );
    }
}
