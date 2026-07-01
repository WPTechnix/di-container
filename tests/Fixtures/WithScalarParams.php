<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class WithScalarParams
{
    public function __construct(
        private string $name,
        private int $count,
        private bool $active,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function active(): bool
    {
        return $this->active;
    }
}
