<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Container;
use WPTechnix\DI\Exception\FrozenContainerException;
use WPTechnix\DI\Tests\Fixtures\Logger;
use WPTechnix\DI\Tests\Fixtures\LoggerProvider;
use WPTechnix\DI\Tests\Fixtures\OrderRecorder;
use WPTechnix\DI\Tests\Fixtures\RecordingProvider;
use WPTechnix\DI\Tests\Fixtures\UuidProvider;
use PHPUnit\Framework\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function testAllProvidersAreRegisteredBeforeAnyAreBooted(): void
    {
        $recorder = new OrderRecorder();
        $container = new Container();
        $container->provider(new RecordingProvider("A", $recorder));
        $container->provider(new RecordingProvider("B", $recorder));
        $container->provider(new RecordingProvider("C", $recorder));

        $container->boot();

        self::assertSame(
            [
                "A.register",
                "B.register",
                "C.register",
                "A.boot",
                "B.boot",
                "C.boot",
            ],
            $recorder->events,
        );
    }

    public function testProviderRegistrationIsFluent(): void
    {
        $recorder = new OrderRecorder();
        $container = new Container();

        $container
            ->provider(new RecordingProvider("A", $recorder))
            ->provider(new RecordingProvider("B", $recorder))
            ->boot();

        self::assertSame(
            ["A.register", "B.register", "A.boot", "B.boot"],
            $recorder->events,
        );
    }

    public function testBootPhaseSeesServicesRegisteredByOtherProviders(): void
    {
        $loggerProvider = new LoggerProvider();
        $container = new Container();
        $container->provider($loggerProvider);
        $container->provider(new UuidProvider());

        $container->boot();

        self::assertTrue($loggerProvider->sawConsumer);
        self::assertInstanceOf(Logger::class, $container->get(Logger::class));
    }

    public function testTheContainerCannotBeBootedTwice(): void
    {
        $container = new Container();
        $container->boot();

        $this->expectException(FrozenContainerException::class);

        $container->boot();
    }

    public function testProvidersCannotBeAddedAfterBoot(): void
    {
        $recorder = new OrderRecorder();
        $container = new Container();
        $container->boot();

        $this->expectException(FrozenContainerException::class);

        $container->provider(new RecordingProvider("late", $recorder));
    }
}
