<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Exception\ContainerException;
use WPTechnix\DI\Exception\DuplicateServiceException;
use WPTechnix\DI\Exception\FrozenContainerException;
use WPTechnix\DI\Exception\ServiceAlreadyResolvedException;
use WPTechnix\DI\Exception\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class ExceptionTest extends TestCase
{
    public function testContainerExceptionImplementsThePsrInterface(): void
    {
        self::assertInstanceOf(
            ContainerExceptionInterface::class,
            new ContainerException("x"),
        );
    }

    public function testServiceNotFoundIsBothAContainerAndNotFoundException(): void
    {
        $exception = ServiceNotFoundException::forId("id");

        self::assertInstanceOf(ContainerExceptionInterface::class, $exception);
        self::assertInstanceOf(NotFoundExceptionInterface::class, $exception);
    }

    public function testEveryCustomExceptionExtendsTheContainerException(): void
    {
        self::assertInstanceOf(
            ContainerException::class,
            ServiceNotFoundException::forId("id"),
        );
        self::assertInstanceOf(
            ContainerException::class,
            DuplicateServiceException::forId("id"),
        );
        self::assertInstanceOf(
            ContainerException::class,
            ServiceAlreadyResolvedException::forId("id"),
        );
        self::assertInstanceOf(
            ContainerException::class,
            FrozenContainerException::alreadyBooted(),
        );
    }

    public function testMessagesIncludeTheIdentifier(): void
    {
        self::assertStringContainsString(
            "logger",
            ServiceNotFoundException::forId("logger")->getMessage(),
        );
        self::assertStringContainsString(
            "logger",
            DuplicateServiceException::forId("logger")->getMessage(),
        );
        self::assertStringContainsString(
            "logger",
            ServiceAlreadyResolvedException::forId("logger")->getMessage(),
        );
    }
}
