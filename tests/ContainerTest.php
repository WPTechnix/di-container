<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Container;
use WPTechnix\DI\Exception\DuplicateServiceException;
use WPTechnix\DI\Exception\ServiceNotFoundException;
use WPTechnix\DI\Tests\Fixtures\Logger;
use WPTechnix\DI\Tests\Fixtures\UuidGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ContainerTest extends TestCase
{
    public function testItImplementsThePsrContainerInterface(): void
    {
        self::assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function testSingletonReturnsTheSameInstanceEveryTime(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );

        $first = $container->get(Logger::class);
        $second = $container->get(Logger::class);

        self::assertInstanceOf(Logger::class, $first);
        self::assertSame($first, $second);
    }

    public function testSingletonFactoryReceivesTheContainer(): void
    {
        $container = new Container();
        $received = null;
        $container->singleton("service", static function (Container $c) use (
            &$received,
        ): Logger {
            $received = $c;
            return new Logger();
        });

        $container->get("service");

        self::assertSame($container, $received);
    }

    public function testSingletonIsLazyAndOnlyBuiltOnce(): void
    {
        $container = new Container();
        $calls = 0;
        $container->singleton(Logger::class, static function () use (
            &$calls,
        ): Logger {
            $calls++;
            return new Logger();
        });

        self::assertSame(
            0,
            $calls,
            "Factory must not run until the service is resolved.",
        );

        $container->get(Logger::class);
        $container->get(Logger::class);

        self::assertSame(1, $calls);
    }

    public function testFactoryReturnsAFreshInstanceEveryTime(): void
    {
        $container = new Container();
        $container->factory(
            UuidGenerator::class,
            static fn(): UuidGenerator => new UuidGenerator(),
        );

        $first = $container->get(UuidGenerator::class);
        $second = $container->get(UuidGenerator::class);

        self::assertInstanceOf(UuidGenerator::class, $first);
        self::assertNotSame($first, $second);
    }

    public function testHasReportsRegisteredAndUnregisteredServices(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );

        self::assertTrue($container->has(Logger::class));
        self::assertFalse($container->has(UuidGenerator::class));
    }

    public function testGettingAnUnregisteredServiceThrows(): void
    {
        $container = new Container();

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service "missing" is not registered');

        $container->get("missing");
    }

    public function testServiceNotFoundIsAPsrNotFoundException(): void
    {
        $container = new Container();

        $this->expectException(NotFoundExceptionInterface::class);

        $container->get("missing");
    }

    public function testRegisteringADuplicateSingletonThrows(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );

        $this->expectException(DuplicateServiceException::class);

        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
    }

    public function testRegisteringADuplicateFactoryThrows(): void
    {
        $container = new Container();
        $container->factory(
            "uuid",
            static fn(): UuidGenerator => new UuidGenerator(),
        );

        $this->expectException(DuplicateServiceException::class);

        $container->factory(
            "uuid",
            static fn(): UuidGenerator => new UuidGenerator(),
        );
    }

    public function testOverrideReplacesAnExistingRegistration(): void
    {
        $container = new Container();
        $original = new Logger();
        $replacement = new Logger();

        $container->singleton(Logger::class, static fn(): Logger => $original);
        $container->singleton(
            Logger::class,
            static fn(): Logger => $replacement,
            override: true,
        );

        self::assertSame($replacement, $container->get(Logger::class));
    }

    public function testOverrideCanChangeAServiceFromSingletonToFactory(): void
    {
        $container = new Container();
        $container->singleton(
            UuidGenerator::class,
            static fn(): UuidGenerator => new UuidGenerator(),
        );
        $container->factory(
            UuidGenerator::class,
            static fn(): UuidGenerator => new UuidGenerator(),
            override: true,
        );

        self::assertNotSame(
            $container->get(UuidGenerator::class),
            $container->get(UuidGenerator::class),
        );
    }

    public function testStringIdentifiersAreSupported(): void
    {
        $container = new Container();
        $container->singleton("config.timeout", static fn(): int => 30);

        self::assertSame(30, $container->get("config.timeout"));
    }
}
