<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

use RuntimeException;
use WPTechnix\DI\Contracts\ProviderInterface;

class ThrowingServiceProvider implements ProviderInterface
{
    public function register(): void
    {
        throw new RuntimeException('Provider exception');
    }
}
