<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class ServiceA
{
    public function __construct(public ServiceB $b)
    {
    }
}
