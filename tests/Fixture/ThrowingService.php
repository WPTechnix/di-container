<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

use RuntimeException;

class ThrowingService
{
    public function __construct()
    {
        throw new RuntimeException('Exception during instantiation');
    }
}
