<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'todo_id',
        'action',
        'description',
    ];

    public static function add(string $action, ?Todo $todo = null, ?string $description = null): void
    {
        self::create([
            'user_id' => auth()->id(),
            'todo_id' => $todo?->id,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
