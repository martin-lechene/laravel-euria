<?php

namespace MartinLechene\Euria\Events;

class TokensUsed
{
    public function __construct(
        public readonly int $promptTokens,
        public readonly int $completionTokens,
        public readonly int $totalTokens,
        public readonly string $model,
    ) {}
}
