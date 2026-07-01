<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Container;
use WPTechnix\DI\Exception\ServiceAlreadyResolvedException;
use WPTechnix\DI\Exception\ServiceNotFoundException;
use WPTechnix\DI\Tests\Fixtures\CachedLogger;
use WPTechnix\DI\Tests\Fixtures\Logger;
use WPTechnix\DI\Tests\Fixtures\LoggerInterface;
use WPTechnix\DI\Tests\Fixtures\UuidGenerator;
use PHPUnit\Framework\TestCase;

final class ExtendTest extends TestCase
{
    public function testExtendDecoratesAService(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
        $container->extend(
            Logger::class,
            static fn(
                Logger $logger,
                Container $c,
            ): CachedLogger => new CachedLogger($logger),
        );

        $resolved = $container->get(Logger::class);

        self::assertInstanceOf(CachedLogger::class, $resolved);
        self::assertInstanceOf(Logger::class, $resolved->inner());
    }

    public function testExtendReceivesTheContainer(): void
    {
        $container = new Container();
        $received = null;
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
        $container->extend(Logger::class, static function (
            Logger $logger,
            Container $c,
        ) use (&$received): Logger {
            $received = $c;
            return $logger;
        });

        $container->get(Logger::class);

        self::assertSame($container, $received);
    }

    public function testMultipleDecoratorsRunInRegistrationOrder(): void
    {
        $container = new Container();
        $container->singleton("value", static fn(): string => "base");
        $container->extend(
            "value",
            static fn(string $value): string => $value . "-a",
        );
        $container->extend(
            "value",
            static fn(string $value): string => $value . "-b",
        );

        self::assertSame("base-a-b", $container->get("value"));
    }

    public function testExtendingAnUnknownServiceThrows(): void
    {
        $container = new Container();

        $this->expectException(ServiceNotFoundException::class);

        $container->extend("missing", static fn(mixed $value): mixed => $value);
    }

    public function testExtendingAnAlreadyResolvedSingletonThrows(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
        $container->get(Logger::class);

        $this->expectException(ServiceAlreadyResolvedException::class);

        $container->extend(
            Logger::class,
            static fn(Logger $logger): Logger => $logger,
        );
    }

    public function testAFactoryCanBeExtendedAfterResolution(): void
    {
        $container = new Container();
        $container->factory(
            UuidGenerator::class,
            static fn(): UuidGenerator => new UuidGenerator(),
        );
        $container->get(UuidGenerator::class);

        $container->extend(
            UuidGenerator::class,
            static fn(UuidGenerator $g): UuidGenerator => $g,
        );

        self::assertInstanceOf(
            UuidGenerator::class,
            $container->get(UuidGenerator::class),
        );
    }

    public function testFactoryDecoratorsRunOnEveryResolution(): void
    {
        $container = new Container();
        $calls = 0;
        $container->factory("value", static fn(): string => "x");
        $container->extend("value", static function (string $value) use (
            &$calls,
        ): string {
            $calls++;
            return $value;
        });

        $container->get("value");
        $container->get("value");

        self::assertSame(2, $calls);
    }

    public function testExtendCanBeTargetedThroughAnAlias(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
        $container->alias(LoggerInterface::class, Logger::class);
        $container->extend(
            LoggerInterface::class,
            static fn(Logger $logger): CachedLogger => new CachedLogger(
                $logger,
            ),
        );

        self::assertInstanceOf(
            CachedLogger::class,
            $container->get(Logger::class),
        );
    }
}
