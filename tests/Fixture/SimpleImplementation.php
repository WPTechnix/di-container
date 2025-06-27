<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class SimpleImplementation extends AbstractClass implements TestInterface
{
    public function doSomething(): void
    {
        // Do nothing
    }

    public function getName(): string
    {
        return 'Simple';
    }
}
