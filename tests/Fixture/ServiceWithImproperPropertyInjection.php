<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

use WPTechnix\DI\Attributes\Inject;

class ServiceWithImproperPropertyInjection
{
    #[Inject]
    public $improperDep;
}
