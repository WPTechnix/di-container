<?php

/**
 * Base exception for all container-related exceptions.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Exceptions;

use Exception;
use Throwable;
use WPTechnix\DI\Contracts\ContainerExceptionInterface;

/**
 * Base exception for all container-related exceptions.
 *
 * @package WPTechnix\DI\Exceptions
 * @author WPTechnix <developers@wptechnix.com>
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string $message Error message.
     * @param string $serviceId Service identifier related to the exception.
     * @param array<string> $dependencyChain Dependency resolution chain.
     * @param array<string,mixed> $context Additional context for debugging.
     * @param int $code Error code.
     * @param Throwable|null $previous Previous exception.
     */
    public function __construct(
        string $message,
        protected string $serviceId,
        protected array $dependencyChain = [],
        private readonly array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {

        // Enhance the message with the dependency chain for better debugging
        $enhanced_message = $this->enhanceMessage($message, $serviceId, $dependencyChain, $context);

        parent::__construct($enhanced_message, $code, $previous);
    }

    /**
     * Enhances the exception message with dependency chain and context.
     *
     * @param string $message Original message.
     * @param string $serviceId Service identifier.
     * @param array<string> $dependencyChain Dependency chain.
     * @param array<string,mixed>  $context Additional context.
     *
     * @return string
     */
    protected function enhanceMessage(
        string $message,
        string $serviceId,
        array $dependencyChain,
        array $context
    ): string {
        $enhanced = $message;

        // Add the dependency chain to the message if it exists and has meaningful depth.
        if (! empty($dependencyChain) && count($dependencyChain) > 1) {
            $chain_str = implode(' -> ', $dependencyChain);
            $enhanced .= " [Dependency Chain: $chain_str]";
        }

        // Add key context data if available and useful (limited to avoid cluttering).
        $important_context = array_filter($context, function ($key) {
            return in_array($key, [ 'operation', 'error', 'original_error' ], true);
        }, ARRAY_FILTER_USE_KEY);

        if (! empty($important_context)) {
            foreach ($important_context as $key => $value) {
                if (is_scalar($value)) {
                    $enhanced .= " [$key: $value]";
                }
            }
        }

        return $enhanced;
    }

    /**
     * Gets the service identifier.
     *
     * @return string
     */
    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    /**
     * Gets the dependency chain.
     *
     * @return array
     * @phpstan-return array<string>
     */
    public function getDependencyChain(): array
    {
        return $this->dependencyChain;
    }

    /**
     * Gets the context.
     *
     * @return array
     *
     * @phpstan-return array<string,mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Gets a formatted string representation of the exception for detailed debugging.
     *
     * @return string
     */
    public function getDebugInfo(): string
    {
        $output = sprintf(
            "Container Exception: %s\nService: %s\n",
            $this->getMessage(),
            $this->serviceId
        );

        if (! empty($this->dependencyChain)) {
            $output .= sprintf(
                "Dependency Chain: %s\n",
                implode(' -> ', $this->dependencyChain)
            );
        }

        if (! empty($this->context)) {
            $output .= "Context:\n";
            foreach ($this->context as $key => $value) {
                $value_str = is_scalar($value) ? (string) $value : json_encode($value, JSON_PRETTY_PRINT);
                $output   .= sprintf("  %s: %s\n", $key, $value_str);
            }
        }

        if (! empty($this->getPrevious())) {
            // @codeCoverageIgnoreStart
            $output .= sprintf(
                "Previous Exception: %s: %s\n",
                get_class($this->getPrevious()),
                $this->getPrevious()->getMessage()
            );
            // @codeCoverageIgnoreEnd
        }

        return $output;
    }
}
