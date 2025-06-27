<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ServiceWithCircularDependencyViaMethodInjection
{
    private CircularA $service;

    public function setDependency(CircularA $service): void
    {
        $this->service = $service;
    }
}
