<?php

namespace MartinLechene\Euria\Events;

class StreamChunkReceived
{
    public function __construct(
        public readonly array $raw,
    ) {}
}
