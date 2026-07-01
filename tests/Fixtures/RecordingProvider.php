<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

use WPTechnix\DI\Container;
use WPTechnix\DI\ServiceProvider;

final class RecordingProvider implements ServiceProvider
{
    public function __construct(
        private string $name,
        private OrderRecorder $recorder,
    ) {
    }

    public function register(Container $container): void
    {
        $this->recorder->record($this->name . ".register");
    }

    public function boot(Container $container): void
    {
        $this->recorder->record($this->name . ".boot");
    }
}
