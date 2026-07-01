<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class CachedLogger implements LoggerInterface
{
    public function __construct(private LoggerInterface $inner)
    {
    }

    public function inner(): LoggerInterface
    {
        return $this->inner;
    }

    public function log(string $message): void
    {
        $this->inner->log("[cached] " . $message);
    }

    /**
     * @return list<string>
     */
    public function messages(): array
    {
        return $this->inner->messages();
    }
}
