<?php

namespace App\Enums\Todo;

enum TodoPriority: int
{
    case Low = 0;
    case Medium = 1;
    case High = 2;

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Low => 'bg-secondary',
            self::Medium => 'bg-dark',
            self::High => 'bg-danger',
        };
    }
    public static function options(): array
    {
        return [
            self::Low,
            self::Medium,
            self::High,
        ];
    }
}
