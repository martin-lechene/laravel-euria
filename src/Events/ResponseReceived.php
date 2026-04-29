<?php

namespace MartinLechene\Euria\Events;

class ResponseReceived
{
    public function __construct(
        public readonly string $endpoint,
        public readonly array $response,
        public readonly float $duration,
    ) {}
}
