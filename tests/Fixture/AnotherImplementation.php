<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class AnotherImplementation implements TestInterface
{
    public function getName(): string
    {
        return 'Another';
    }
}
