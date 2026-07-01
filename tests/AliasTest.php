<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Container;
use WPTechnix\DI\Exception\ContainerException;
use WPTechnix\DI\Exception\DuplicateServiceException;
use WPTechnix\DI\Tests\Fixtures\Logger;
use WPTechnix\DI\Tests\Fixtures\LoggerInterface;
use PHPUnit\Framework\TestCase;

final class AliasTest extends TestCase
{
    public function testAnAliasResolvesToItsTarget(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
        $container->alias(LoggerInterface::class, Logger::class);

        self::assertSame(
            $container->get(Logger::class),
            $container->get(LoggerInterface::class),
        );
    }

    public function testHasReportsTrueForAnAlias(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
        $container->alias(LoggerInterface::class, Logger::class);

        self::assertTrue($container->has(LoggerInterface::class));
    }

    public function testHasReportsFalseForADanglingAlias(): void
    {
        $container = new Container();
        $container->alias(LoggerInterface::class, Logger::class);

        self::assertFalse($container->has(LoggerInterface::class));
    }

    public function testChainedAliasesAreResolved(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );
        $container->alias("logger", Logger::class);
        $container->alias(LoggerInterface::class, "logger");

        self::assertSame(
            $container->get(Logger::class),
            $container->get(LoggerInterface::class),
        );
    }

    public function testAnAliasCannotCollideWithADefinition(): void
    {
        $container = new Container();
        $container->singleton(
            Logger::class,
            static fn(): Logger => new Logger(),
        );

        $this->expectException(DuplicateServiceException::class);

        $container->alias(Logger::class, "something");
    }

    public function testAnAliasCannotReferenceItself(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);

        $container->alias(Logger::class, Logger::class);
    }

    public function testCircularAliasesAreDetected(): void
    {
        $container = new Container();
        $container->alias("a", "b");
        $container->alias("b", "a");

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Circular alias detected");

        $container->get("a");
    }

    public function testRegisteringOverAClashingAliasRequiresOverride(): void
    {
        $container = new Container();
        $container->alias("logger", Logger::class);

        $this->expectException(DuplicateServiceException::class);

        $container->singleton("logger", static fn(): Logger => new Logger());
    }

    public function testOverrideReplacesAClashingAliasWithADefinition(): void
    {
        $container = new Container();
        $container->alias("logger", Logger::class);
        $container->singleton(
            "logger",
            static fn(): Logger => new Logger(),
            override: true,
        );

        self::assertInstanceOf(Logger::class, $container->get("logger"));
    }
}
