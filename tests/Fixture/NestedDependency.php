<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class NestedDependency
{
    private ServiceWithDependency $service;

    public function __construct(ServiceWithDependency $service)
    {
        $this->service = $service;
    }

    public function getService(): ServiceWithDependency
    {
        return $this->service;
    }
}
