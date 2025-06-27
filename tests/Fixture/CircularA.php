<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class CircularA
{
    public function __construct(CircularB $b)
    {
    }
}
