<?php

/**
 * Exception thrown when a class cannot be instantiated.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Exceptions;

use Throwable;

/**
 * Exception thrown when a class exists but cannot be instantiated.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */
class InstantiationException extends ResolutionException
{
    /**
     * Constructor.
     *
     * @param string $class_name The class that cannot be instantiated.
     * @param string $reason The reason for failure.
     * @param array<string> $dependencyChain Dependency resolution chain.
     * @param array<string,mixed> $context Additional context.
     * @param int $code Error code.
     * @param Throwable|null $previous Previous exception.
     */
    public function __construct(
        string $class_name,
        string $reason,
        array $dependencyChain = [],
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = sprintf('Class "%s" could not be instantiated: %s', $class_name, $reason);

        parent::__construct($message, $class_name, $dependencyChain, $context, $code, $previous);
    }
}
