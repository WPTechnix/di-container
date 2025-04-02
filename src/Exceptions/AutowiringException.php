<?php

/**
 * Exception thrown when autowiring fails.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Exceptions;

use Throwable;

/**
 * Exception for autowiring failures.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */
class AutowiringException extends ResolutionException
{
    /**
     * Constructor.
     *
     * @param string $class The class that failed autowiring.
     * @param string $parameter The parameter that could not be autowired.
     * @param string $type The parameter type.
     * @param array<string> $dependencyChain Dependency resolution chain.
     * @param array<string,mixed> $context Additional context.
     * @param int $code Error code.
     * @param Throwable|null $previous Previous exception.
     */
    public function __construct(
        string $class,
        string $parameter,
        string $type,
        array $dependencyChain = [],
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $reason = $this->determineReason($type, $context);

        $message = sprintf(
            'Cannot autowire parameter "$%s" of type "%s" in class "%s"%s',
            $parameter,
            $type,
            $class,
            ! empty($reason) ? ': ' . $reason : ''
        );

        $context['parameter'] = $parameter;
        $context['parameter_type'] = $type;

        parent::__construct($message, $class, $dependencyChain, $context, $code, $previous);
    }

    /**
     * Determines the reason for the autowiring failure.
     *
     * @param string $type Type name.
     * @param array<string, mixed> $context Additional context.
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    private function determineReason(string $type, array $context): string
    {
        if (isset($context['union_types'])) {
            return sprintf('cannot autowire union types (%s)', implode('|', $context['union_types']));
        }

        if ($type === 'unknown') {
            return 'parameter has no type hint';
        }

        if (! empty($context['is_variadic'])) {
            return 'cannot autowire variadic parameters';
        }

        if (! empty($context['is_by_reference'])) {
            return 'cannot autowire parameters passed by reference';
        }

        return '';
    }
}
