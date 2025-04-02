<?php

/**
 * Inject attribute.
 *
 * @package WPTechnix\DI
 * @author WPTechnix <developer@wptechnix.com>
 */

declare(strict_types=1);

namespace WPTechnix\DI\Attributes;

use Attribute;

/**
 * Inject attribute.
 *
 * @package WPTechnix\DI
 * @author WPTechnix <developer@wptechnix.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{
    /**
     * Constructs a new Inject attribute.
     *
     * @param class-string $dependency_class Dependency class name.
     */
    public function __construct(
        public ?string $dependency_class = null,
    ) {
    }

    /**
     * Get the dependency class name.
     *
     * @phpstan-return class-string|null
     */
    public function getDependencyClass(): ?string
    {
        return $this->dependency_class;
    }
}
