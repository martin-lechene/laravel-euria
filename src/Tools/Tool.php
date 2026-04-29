<?php

namespace MartinLechene\Euria\Tools;

abstract class Tool
{
    abstract public function name(): string;

    abstract public function description(): string;

    abstract public function parameters(): array;

    public function handle(array $arguments): mixed
    {
        return null;
    }

    public function toArray(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => $this->description(),
                'parameters' => $this->parameters(),
            ],
        ];
    }
}
