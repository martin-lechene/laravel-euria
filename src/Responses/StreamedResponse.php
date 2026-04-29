<?php

namespace MartinLechene\Euria\Responses;

class StreamedResponse
{
    public readonly array $raw;

    public function __construct(array $chunk)
    {
        $this->raw = $chunk;
    }

    public function content(): ?string
    {
        return $this->raw['choices'][0]['delta']['content'] ?? null;
    }
}
