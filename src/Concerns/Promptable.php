<?php

namespace MartinLechene\Euria\Concerns;

use Illuminate\Foundation\Bus\PendingDispatch;
use MartinLechene\Euria\Agents\AgentRunner;

trait Promptable
{
    public static function make(mixed ...$args): static
    {
        return app(static::class, $args);
    }

    public function prompt(
        string $message,
        ?string $model = null,
        int $timeout = 60,
        array $attachments = [],
    ): mixed {
        return app(AgentRunner::class)->run($this, $message, $model, $timeout, $attachments);
    }

    public function stream(string $message, ?string $model = null): \Generator
    {
        return app(AgentRunner::class)->stream($this, $message, $model);
    }

    public function queue(string $message): PendingDispatch
    {
        return app(AgentRunner::class)->queue($this, $message);
    }
}
