<?php

/**
 * Base exception for resolution-related errors.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developer@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Exceptions;

use Throwable;

/**
 * Base exception for resolution-related errors.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developer@wptechnix.com>
 */
class ResolutionException extends ContainerException
{
    /**
     * Constructor.
     *
     * @param string $message Error message.
     * @param string $serviceId Service identifier.
     * @param array<string> $dependencyChain Dependency resolution chain.
     * @param array<string,mixed> $context Additional context.
     * @param int $code Error code.
     * @param Throwable|null $previous Previous exception.
     */
    public function __construct(
        string $message,
        string $serviceId,
        array $dependencyChain = [],
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $serviceId, $dependencyChain, $context, $code, $previous);
    }
}
