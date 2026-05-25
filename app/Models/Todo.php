<?php

namespace App\Models;

use Carbon\Carbon;
use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Todo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'status' => TodoStatus::class,
        'priority' => TodoPriority::class,
    ];

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'completed_at',
    ];

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function dueStatus(): string
    {
        if (!$this->due_date) return 'none';

        $today = now()->startOfDay();
        $due = \Carbon\Carbon::parse($this->due_date)->startOfDay();

        if ($due->lt($today)) return 'overdue';
        if ($due->isSameDay($today)) return 'today';
        if ($due->isSameDay($today->copy()->addDay())) return 'tomorrow';

        return 'upcoming';
    }

    public function dueBadge(): string
    {
        return match ($this->dueStatus()) {
            'overdue' => 'bg-danger',
            'today' => 'bg-dark',
            'tomorrow' => 'bg-secondary',
            default => 'bg-light text-dark',
        };
    }
    public function dueLabel(): string
    {
        if (!$this->due_date) return 'No due date';
        return \Carbon\Carbon::parse($this->due_date)->diffForHumans();
    }

    public function scopeSmartOrder($query)
    {
        return $query
            ->orderByRaw("CASE
                WHEN status = 2 THEN 5
                WHEN due_date < CURDATE()
                    THEN 1
                WHEN due_date = CURDATE()
                    THEN 2
                ELSE 3
            END")
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->latest();
    }
}
