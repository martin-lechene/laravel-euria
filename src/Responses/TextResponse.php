<?php

namespace MartinLechene\Euria\Responses;

use Stringable;

class TextResponse implements Stringable
{
    public readonly string $text;

    public readonly string $model;

    public readonly string $finishReason;

    public readonly array $usage;

    public readonly array $raw;

    public function __construct(array $data)
    {
        $this->raw = $data;
        $this->model = $data['model'] ?? '';
        $this->text = $data['choices'][0]['message']['content'] ?? '';
        $this->finishReason = $data['choices'][0]['finish_reason'] ?? '';
        $this->usage = $data['usage'] ?? [];
    }

    public function __toString(): string
    {
        return $this->text;
    }

    public function toolCalls(): array
    {
        return $this->raw['choices'][0]['message']['tool_calls'] ?? [];
    }
}
