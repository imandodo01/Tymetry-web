<?php

namespace App\Enums\Todo;

enum TodoStatus: int
{
    case Pending = 0;
    case Progress = 1;
    case Done = 2;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Progress => 'In Progress',
            self::Done => 'Done',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-secondary',
            self::Progress => 'bg-dark',
            self::Done => 'bg-danger',
        };
    }

    public static function options(): array
    {
        return [
            self::Pending,
            self::Progress,
            self::Done,
        ];
    }
}
