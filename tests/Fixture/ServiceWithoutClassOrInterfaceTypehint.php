<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ServiceWithoutClassOrInterfaceTypehint
{
    private int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }
}
