<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Container;
use WPTechnix\DI\Exception\ContainerException;
use WPTechnix\DI\Tests\Fixtures\ServiceA;
use WPTechnix\DI\Tests\Fixtures\ServiceB;
use PHPUnit\Framework\TestCase;

final class CircularDependencyTest extends TestCase
{
    public function testCircularDependenciesAreDetected(): void
    {
        $container = new Container();
        $container->singleton(ServiceA::class, static function (
            Container $c,
        ): ServiceA {
            return new ServiceA($c->get(ServiceB::class));
        });
        $container->singleton(ServiceB::class, static function (
            Container $c,
        ): ServiceB {
            return new ServiceB($c->get(ServiceA::class));
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Circular dependency detected");

        $container->get(ServiceA::class);
    }

    public function testTheResolutionStackIsClearedAfterAFailure(): void
    {
        $container = new Container();
        $container->singleton("boom", static function (): void {
            throw new \LogicException("factory failure");
        });

        try {
            $container->get("boom");
        } catch (\LogicException) {
            // Expected: the factory threw.
        }

        // A later, unrelated resolution must not see a stale resolution stack.
        $container->singleton("ok", static fn(): string => "value");
        self::assertSame("value", $container->get("ok"));
    }
}
