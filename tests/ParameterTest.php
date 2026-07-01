<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Container;
use WPTechnix\DI\Tests\Fixtures\Logger;
use WPTechnix\DI\Tests\Fixtures\WithMixedParams;
use WPTechnix\DI\Tests\Fixtures\WithScalarParams;
use WPTechnix\DI\Tests\Fixtures\WithServiceParam;
use PHPUnit\Framework\TestCase;

final class ParameterTest extends TestCase
{
    public function testParametersArePassedToConstructorInOrder(): void
    {
        $container = new Container();
        $container->singleton('svc', WithScalarParams::class)
            ->addParameter('beta')
            ->addParameter(42)
            ->addParameter(false);

        $service = $container->get('svc');

        self::assertInstanceOf(WithScalarParams::class, $service);
        self::assertSame('beta', $service->name());
        self::assertSame(42, $service->count());
        self::assertFalse($service->active());
    }

    public function testAddParameterAppendsSequentially(): void
    {
        $container = new Container();
        $definition = $container->singleton('svc', WithScalarParams::class)
            ->addParameter('one')
            ->addParameter('two')
            ->addParameter('three');

        self::assertSame(
            [
                ['value' => 'one', 'resolve' => false],
                ['value' => 'two', 'resolve' => false],
                ['value' => 'three', 'resolve' => false],
            ],
            $definition->getParameters(),
        );
    }

    public function testAddParametersReplacesAllPreviousParameters(): void
    {
        $container = new Container();
        $definition = $container->singleton('svc', WithScalarParams::class)
            ->addParameter('discarded')
            ->addParameter('also discarded')
            ->addParameters(['final', 99, true]);

        self::assertSame(
            [
                ['value' => 'final', 'resolve' => false],
                ['value' => 99, 'resolve' => false],
                ['value' => true, 'resolve' => false],
            ],
            $definition->getParameters(),
        );

        $service = $container->get('svc');

        self::assertInstanceOf(WithScalarParams::class, $service);
        self::assertSame('final', $service->name());
        self::assertSame(99, $service->count());
        self::assertTrue($service->active());
    }

    public function testGetParametersReturnsStoredData(): void
    {
        $container = new Container();
        $definition = $container->singleton('svc', WithScalarParams::class);

        self::assertSame([], $definition->getParameters());

        $definition->addParameter('x', resolve: true);

        self::assertSame(
            [['value' => 'x', 'resolve' => true]],
            $definition->getParameters(),
        );
    }

    public function testCallableFactoryIgnoresParametersButStillResolves(): void
    {
        $container = new Container();
        $container->singleton('logger', static fn(): Logger => new Logger())
            ->addParameter('unused')
            ->addParameter(123);

        self::assertInstanceOf(Logger::class, $container->get('logger'));
    }

    public function testCallableFactoryReceivesParametersAsSecondArgument(): void
    {
        $container = new Container();
        $captured = null;

        $container->singleton(
            'svc',
            static function (Container $c, array $params) use (&$captured): Logger {
                $captured = $params;
                return new Logger();
            },
        )->addParameter('a')->addParameter('b');

        $container->get('svc');

        self::assertSame(['a', 'b'], $captured);
    }

    public function testResolveTrueWithStringResolvesService(): void
    {
        $container = new Container();
        $container->singleton(Logger::class);
        $container->singleton('svc', WithServiceParam::class)
            ->addParameter(Logger::class, resolve: true);

        $service = $container->get('svc');

        self::assertInstanceOf(WithServiceParam::class, $service);
        self::assertSame($container->get(Logger::class), $service->logger());
    }

    public function testResolveTrueWithClosureInvokesClosureWithContainer(): void
    {
        $container = new Container();
        $container->singleton(Logger::class);
        $received = null;

        $container->singleton('svc', WithServiceParam::class)
            ->addParameter(
                static function (Container $c) use (&$received): Logger {
                    $received = $c;
                    return $c->get(Logger::class);
                },
                resolve: true,
            );

        $service = $container->get('svc');

        self::assertInstanceOf(WithServiceParam::class, $service);
        self::assertSame($container, $received);
        self::assertSame($container->get(Logger::class), $service->logger());
    }

    public function testResolveFalsePassesRawValueUnchanged(): void
    {
        $container = new Container();
        $logger = new Logger();

        $container->singleton('svc', WithServiceParam::class)
            ->addParameter($logger, resolve: false);

        $service = $container->get('svc');

        self::assertInstanceOf(WithServiceParam::class, $service);
        self::assertSame($logger, $service->logger());
    }

    public function testResolveFalseDoesNotTreatStringAsService(): void
    {
        $container = new Container();
        $container->singleton('svc', WithScalarParams::class)
            ->addParameter('Logger', resolve: false)
            ->addParameter(1)
            ->addParameter(true);

        $service = $container->get('svc');

        self::assertInstanceOf(WithScalarParams::class, $service);
        self::assertSame('Logger', $service->name());
    }

    public function testMixedResolveFlagsInOneChain(): void
    {
        $container = new Container();
        $container->singleton(Logger::class);

        $container->singleton('svc', WithMixedParams::class)
            ->addParameter('prefix-value', resolve: false)
            ->addParameter(Logger::class, resolve: true)
            ->addParameter(['debug' => true], resolve: false);

        $service = $container->get('svc');

        self::assertInstanceOf(WithMixedParams::class, $service);
        self::assertSame('prefix-value', $service->prefix());
        self::assertSame($container->get(Logger::class), $service->logger());
        self::assertSame(['debug' => true], $service->config());
    }
}
