<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class ServiceB
{
    public function __construct(public ServiceA $a)
    {
    }
}
