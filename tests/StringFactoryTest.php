<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Container;
use WPTechnix\DI\Exception\ContainerException;
use WPTechnix\DI\Tests\Fixtures\Logger;
use WPTechnix\DI\Tests\Fixtures\WithNoConstructor;
use WPTechnix\DI\Tests\Fixtures\WithScalarParams;
use PHPUnit\Framework\TestCase;

final class StringFactoryTest extends TestCase
{
    public function testStringFactoryCreatesInstance(): void
    {
        $container = new Container();
        $container->singleton('logger', Logger::class);

        self::assertInstanceOf(Logger::class, $container->get('logger'));
    }

    public function testNullFactoryUsesServiceIdAsClass(): void
    {
        $container = new Container();
        $container->singleton(Logger::class);

        self::assertInstanceOf(Logger::class, $container->get(Logger::class));
    }

    public function testNullFactoryIsTheDefault(): void
    {
        $container = new Container();
        $container->singleton(WithNoConstructor::class);

        self::assertInstanceOf(
            WithNoConstructor::class,
            $container->get(WithNoConstructor::class),
        );
    }

    public function testNoConstructorClassResolvesWithoutParameters(): void
    {
        $container = new Container();
        $container->singleton('no_ctor', WithNoConstructor::class);

        self::assertInstanceOf(WithNoConstructor::class, $container->get('no_ctor'));
    }

    public function testNonExistentClassStringThrowsContainerException(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('does not exist');

        /** @phpstan-ignore argument.type */
        $container->singleton('id', 'NonExistent\\Service');
    }

    public function testNullFactoryWithUnknownClassIdentifierThrows(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);

        $container->singleton('not.a.class');
    }

    public function testStringFactoryProducesFreshInstancesInFactoryMode(): void
    {
        $container = new Container();
        $container->factory('logger', Logger::class);

        self::assertNotSame(
            $container->get('logger'),
            $container->get('logger'),
        );
    }

    public function testStringFactoryProducesSharedInstanceInSingletonMode(): void
    {
        $container = new Container();
        $container->singleton('logger', Logger::class);

        self::assertSame(
            $container->get('logger'),
            $container->get('logger'),
        );
    }

    public function testStringFactoryAcceptsConstructorParameters(): void
    {
        $container = new Container();
        $container->singleton('svc', WithScalarParams::class)
            ->addParameter('alpha')
            ->addParameter(7)
            ->addParameter(true);

        $service = $container->get('svc');

        self::assertInstanceOf(WithScalarParams::class, $service);
        self::assertSame('alpha', $service->name());
        self::assertSame(7, $service->count());
        self::assertTrue($service->active());
    }
}
