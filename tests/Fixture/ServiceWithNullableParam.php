<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

use WPTechnix\DI\Attributes\Inject;

class ServiceWithNullableParam
{
    private ?TestInterface $dependency;

    public function __construct(?TestInterface $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency(): ?TestInterface
    {
        return $this->dependency;
    }
}
