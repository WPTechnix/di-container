<?php

/**
 * Exception thrown when a property injection fails.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Exceptions;

use Throwable;

/**
 * Exception thrown when a property injection fails.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */
class InjectionException extends ResolutionException
{
    /**
     * Constructor.
     *
     * @param string $class_name The class with injection failure.
     * @param string $target_name The property or method name.
     * @param string $injection_type The type of injection ('property' or 'method').
     * @param string $reason The reason for failure.
     * @param array<string> $dependencyChain Dependency resolution chain.
     * @param array<string,mixed> $context Additional context.
     * @param int $code Error code.
     * @param Throwable|null $previous Previous exception.
     */
    public function __construct(
        string $class_name,
        string $target_name,
        string $injection_type,
        string $reason,
        array $dependencyChain = [],
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            'Failed to inject %s "%s" in class "%s": %s',
            $injection_type,
            $target_name,
            $class_name,
            $reason
        );

        $context['target'] = $target_name;
        $context['injection_type'] = $injection_type;
        $context['injection_reason'] = $reason;

        parent::__construct($message, $class_name, $dependencyChain, $context, $code, $previous);
    }
}
