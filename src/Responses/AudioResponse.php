<?php

namespace MartinLechene\Euria\Responses;

use Stringable;

class AudioResponse implements Stringable
{
    public readonly string $text;

    public readonly ?string $language;

    public readonly ?float $duration;

    public function __construct(array $data)
    {
        $this->text = $data['text'] ?? '';
        $this->language = $data['language'] ?? null;
        $this->duration = $data['duration'] ?? null;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
