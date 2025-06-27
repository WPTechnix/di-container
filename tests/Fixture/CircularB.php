<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class CircularB
{
    public function __construct(CircularA $a)
    {
    }
}
