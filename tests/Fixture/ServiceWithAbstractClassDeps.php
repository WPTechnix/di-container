<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ServiceWithAbstractClassDeps
{
    protected TestInterface $dependency;

    public function __construct(AbstractClass $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependency(): TestInterface
    {
        return $this->dependency;
    }
}
