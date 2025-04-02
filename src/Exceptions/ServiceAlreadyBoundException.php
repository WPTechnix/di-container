<?php

/**
 * Exception thrown when a service is already bound.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developer@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Exceptions;

use Throwable;

/**
 * Exception for service already bound.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developer@wptechnix.com>
 */
class ServiceAlreadyBoundException extends BindingException
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
        $binding_type = '';

        if (isset($context['existing_binding']) && is_array($context['existing_binding'])) {
            $binding = $context['existing_binding'];
            $type = $binding['type'] ?? 'unknown';
            $shared = isset($binding['shared']) && $binding['shared'] ? 'shared' : 'non-shared';

            $binding_type = sprintf(' (existing %s %s binding)', $shared, $type);
        }

        $message = sprintf('Service "%s" is already bound%s', $serviceId, $binding_type);

        parent::__construct($message, $serviceId, $dependencyChain, $context, $code, $previous);
    }
}
