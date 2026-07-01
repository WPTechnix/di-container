<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class WithServiceParam
{
    public function __construct(private Logger $logger)
    {
    }

    public function logger(): Logger
    {
        return $this->logger;
    }
}
