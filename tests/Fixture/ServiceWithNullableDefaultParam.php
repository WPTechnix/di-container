<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ServiceWithNullableDefaultParam
{
    private ?TestInterface $dependency;

    public function __construct(?TestInterface $dependency = null)
    {
        $this->dependency = $dependency;
    }

    public function getDependency(): ?TestInterface
    {
        return $this->dependency;
    }
}
