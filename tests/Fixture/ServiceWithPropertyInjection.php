<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

use WPTechnix\DI\Attributes\Inject;

class ServiceWithPropertyInjection
{
    #[Inject]
    public TestInterface $publicDependency;

    #[Inject(AnotherImplementation::class)]
    public TestInterface $explicitDependency;

    public function getExplicitDependency(): TestInterface
    {
        return $this->explicitDependency;
    }
}
