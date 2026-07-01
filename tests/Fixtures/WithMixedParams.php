<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class WithMixedParams
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private string $prefix,
        private Logger $logger,
        private array $config,
    ) {
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function logger(): Logger
    {
        return $this->logger;
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->config;
    }
}
