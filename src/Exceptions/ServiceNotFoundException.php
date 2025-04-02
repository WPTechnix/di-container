<?php

/**
 * Exception thrown when a service is not found.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Exceptions;

use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/**
 * Exception for service not found.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */
class ServiceNotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string $serviceId Service identifier.
     * @param array<string> $dependencyChain Dependency resolution chain.
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
        $message = sprintf('Service "%s" not found', $serviceId);

        // Add operation context if available
        if (isset($context['operation'])) {
            $message .= sprintf(' during %s operation', $context['operation']);
        }

        parent::__construct($message, $serviceId, $dependencyChain, $context, $code, $previous);
    }
}
