<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ServiceWithPrimitiveParam
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
