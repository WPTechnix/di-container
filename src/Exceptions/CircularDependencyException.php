<?php

/**
 * Exception thrown when circular dependencies are detected.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developer@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Exceptions;

use Throwable;

/**
 * Exception for circular dependencies.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developer@wptechnix.com>
 */
class CircularDependencyException extends ContainerException
{
    /**
     * Constructor.
     *
     * @param string $serviceId Service identifier that completes the cycle.
     * @param array<string> $dependencyChain Dependency resolution chain showing the cycle.
     * @param array<string,mixed> $context Additional context.
     * @param int $code Error code.
     * @param Throwable|null $previous Previous exception.
     */
    public function __construct(
        string $serviceId,
        array $dependencyChain = [],
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = sprintf('Circular dependency detected when resolving "%s"', $serviceId);
        parent::__construct($message, $serviceId, $dependencyChain, $context, $code, $previous);
    }

    /**
     * Enhances the message with a clear visualization of the circular dependency.
     *
     * @param string $message  Message to enhance.
     * @param string $serviceId Service ID.
     * @param array<string> $dependencyChain Dependency chain.
     * @param array<string,mixed> $context Context.
     */
    protected function enhanceMessage(
        string $message,
        string $serviceId,
        array $dependencyChain,
        array $context
    ): string {
        // Create a more visual representation of the circular dependency.
        if (! empty($dependencyChain)) {
            $cycle_start = array_search($serviceId, $dependencyChain, true);

            $message .= sprintf(
                " Chain: %s -> %s",
                implode(' -> ', $dependencyChain),
                $serviceId
            );

            if (false !== $cycle_start) {
                $cycle = array_slice($dependencyChain, (int) $cycle_start);
                $cycle[] = $serviceId; // Complete the cycle

                $message .= sprintf(" Cycle: %s", implode(' -> ', $cycle));
            }
        }

        return $message;
    }
}
