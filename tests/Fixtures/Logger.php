<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class Logger implements LoggerInterface
{
    /** @var list<string> */
    private array $messages = [];

    public function log(string $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * @return list<string>
     */
    public function messages(): array
    {
        return $this->messages;
    }
}
