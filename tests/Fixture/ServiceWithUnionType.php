<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ServiceWithUnionType
{
    private string|int $value;

    public function __construct(string|int $value)
    {
        $this->value = $value;
    }
}
