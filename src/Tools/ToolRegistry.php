<?php

namespace MartinLechene\Euria\Tools;

class ToolRegistry
{
    protected static array $tools = [];

    public static function register(Tool $tool): void
    {
        static::$tools[$tool->name()] = $tool;
    }

    public static function get(string $name): ?Tool
    {
        return static::$tools[$name] ?? null;
    }

    public static function all(): array
    {
        return static::$tools;
    }

    public static function clear(): void
    {
        static::$tools = [];
    }
}
