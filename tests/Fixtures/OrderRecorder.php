<?php

declare(strict_types=1);

namespace WPTechnix\DI\Tests\Fixtures;

final class OrderRecorder
{
    /** @var list<string> */
    public array $events = [];

    public function record(string $event): void
    {
        $this->events[] = $event;
    }
}
