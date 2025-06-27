<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

use WPTechnix\DI\Attributes\Inject;

class NonExistentServicePropertyInjection
{
    #[Inject('NonExistentService')]
    public $nonExistentDep;
}
