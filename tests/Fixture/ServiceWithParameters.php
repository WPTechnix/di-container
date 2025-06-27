<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixture;

class ServiceWithParameters
{
    private $test;

    private string $name;
    private int $value;
    private bool $flag;
    private ?array $options;

    public function __construct(
        TestInterface $test,
        $name,
        null|int|string $union_type,
        ?string $nullable,
        int $value = 0,
        bool $flag = false,
        ?array $options = null,
        int|string $union_type_default = 0,
        ?string $additional_with_type = null,
        $additional = null,
    ) {
        $this->test = $test;
        $this->name = $name;
        $this->value = $value;
        $this->flag = $flag;
        $this->options = $options;
    }

    public function isServiceSet(): bool
    {
        return !empty($this->test) && ($this->test instanceof TestInterface);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getFlag(): bool
    {
        return $this->flag;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }
}
