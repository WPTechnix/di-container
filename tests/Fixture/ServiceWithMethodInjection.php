<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

use WPTechnix\DI\Attributes\Inject;

class ServiceWithMethodInjection
{
    private $dependencies = [];

    private $random;

    public function setRandomProperty(int $random)
    {
        // Will be skipped by DI Container.
        $this->random = $random;
    }

    public function setAnotherRandomProperty($another_random)
    {
        // Will be skipped by DI Container.
        $this->random = $another_random;
    }

    public function setDependency(TestInterface $test): void
    {
        $this->dependencies['test'] = $test;
    }

    public function setMultipleProps(AnotherInterface $another, $prop)
    {
        // DI Container skip methods with multiple arguments
        $this->dependencies['another'] = $another;
        $this->random = $prop;
    }

    public function setOptionalDependency(?AnotherInterface $another)
    {
        $this->dependencies['another'] = $another;
    }

    public function setNonExistentOptionalDependency(?OrphanInterface $orphan)
    {
        $this->dependencies['orphan'] = $orphan;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
