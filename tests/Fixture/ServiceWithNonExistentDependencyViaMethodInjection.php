<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ServiceWithNonExistentDependencyViaMethodInjection
{
    private OrphanInterface $orphan;

    public function setOrphan(OrphanInterface $orphan): void
    {
        $this->orphan = $orphan;
    }
}
