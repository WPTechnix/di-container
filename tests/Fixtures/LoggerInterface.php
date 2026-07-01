<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

interface LoggerInterface
{
    public function log(string $message): void;

    /**
     * @return list<string>
     */
    public function messages(): array;
}
