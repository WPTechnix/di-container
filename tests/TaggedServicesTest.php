<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests;

use WPTechnix\DI\Container;
use WPTechnix\DI\Tests\Fixtures\AdminPage;
use WPTechnix\DI\Tests\Fixtures\DashboardWidget;
use WPTechnix\DI\Tests\Fixtures\SettingsPage;
use PHPUnit\Framework\TestCase;

final class TaggedServicesTest extends TestCase
{
    public function testTaggedReturnsEveryServiceCarryingTheTag(): void
    {
        $container = new Container();
        $container
            ->singleton(
                AdminPage::class,
                static fn(): AdminPage => new AdminPage(),
            )
            ->tag("admin");
        $container
            ->singleton(
                SettingsPage::class,
                static fn(): SettingsPage => new SettingsPage(),
            )
            ->tag("admin");
        $container->singleton(
            DashboardWidget::class,
            static fn(): DashboardWidget => new DashboardWidget(),
        );

        $tagged = $container->tagged("admin");

        self::assertCount(2, $tagged);
        self::assertInstanceOf(AdminPage::class, $tagged[0]);
        self::assertInstanceOf(SettingsPage::class, $tagged[1]);
    }

    public function testTaggedPreservesRegistrationOrder(): void
    {
        $container = new Container();
        $container
            ->singleton(
                SettingsPage::class,
                static fn(): SettingsPage => new SettingsPage(),
            )
            ->tag("admin");
        $container
            ->singleton(
                AdminPage::class,
                static fn(): AdminPage => new AdminPage(),
            )
            ->tag("admin");

        $tagged = $container->tagged("admin");

        self::assertInstanceOf(SettingsPage::class, $tagged[0]);
        self::assertInstanceOf(AdminPage::class, $tagged[1]);
    }

    public function testAServiceCanCarryMultipleTags(): void
    {
        $container = new Container();
        $container
            ->singleton(
                AdminPage::class,
                static fn(): AdminPage => new AdminPage(),
            )
            ->tag("admin", "menu");

        self::assertCount(1, $container->tagged("admin"));
        self::assertCount(1, $container->tagged("menu"));
    }

    public function testDuplicateTagsAreIgnored(): void
    {
        $container = new Container();
        $definition = $container->singleton(
            AdminPage::class,
            static fn(): AdminPage => new AdminPage(),
        );
        $definition->tag("admin")->tag("admin");

        self::assertSame(["admin"], $definition->getTags());
        self::assertCount(1, $container->tagged("admin"));
    }

    public function testTaggedReturnsAnEmptyArrayForAnUnknownTag(): void
    {
        $container = new Container();
        $container
            ->singleton(
                AdminPage::class,
                static fn(): AdminPage => new AdminPage(),
            )
            ->tag("admin");

        self::assertSame([], $container->tagged("frontend"));
    }

    public function testTaggedSingletonsAreResolvedOnce(): void
    {
        $container = new Container();
        $container
            ->singleton(
                AdminPage::class,
                static fn(): AdminPage => new AdminPage(),
            )
            ->tag("admin");

        self::assertSame(
            $container->tagged("admin")[0],
            $container->get(AdminPage::class),
        );
    }
}
