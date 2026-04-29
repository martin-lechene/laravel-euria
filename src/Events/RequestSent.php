<?php

namespace MartinLechene\Euria\Events;

class RequestSent
{
    public readonly float $timestamp;

    public function __construct(
        public readonly string $endpoint,
        public readonly array $payload,
        ?float $timestamp = null,
    ) {
        $this->timestamp = $timestamp ?? (defined('LARAVEL_START') ? LARAVEL_START : 0.0);
    }
}
