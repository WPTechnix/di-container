<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ValueImplementation implements AnotherInterface
{
    public function getValue(): int
    {
        return 42;
    }
}
