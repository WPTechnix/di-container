<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class UuidGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
