<?php

namespace App\Models;

use Carbon\Carbon;
use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Todo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Todo $todo): void {
            $todo->public_id = (string) Str::uuid7();
        });

        static::updating(function (Todo $todo): void {
            if ($todo->isDirty('public_id')) {
                $todo->public_id = $todo->getOriginal('public_id');
            }
        });

        static::saving(function (Todo $todo): void {
            if ($todo->status === TodoStatus::Done) {
                $todo->completed_at ??= now();
                return;
            }

            $todo->completed_at = null;
        });
    }

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
        'due_time',
        'completed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function dueStatus(): string
    {
        if (!$this->due_date) return 'none';

        $due = Carbon::parse($this->due_date)->startOfDay();

        if ($this->status === TodoStatus::Done) {
            if (!$this->completed_at) return 'completed';

            $completed = Carbon::parse($this->completed_at)->startOfDay();

            return $completed->gt($due) ? 'completed_late' : 'completed_on_time';
        }

        $today = now()->startOfDay();

        if ($due->lt($today)) return 'overdue';
        if ($due->isSameDay($today)) return 'today';
        if ($due->isSameDay($today->copy()->addDay())) return 'tomorrow';

        return 'upcoming';
    }

    public function dueStatusLabel(): string
    {
        return match ($this->dueStatus()) {
            'none' => 'No due date',
            'overdue' => 'Overdue',
            'today' => 'Due today',
            'tomorrow' => 'Due tomorrow',
            'completed' => 'Completed',
            'completed_on_time' => 'Completed on time',
            'completed_late' => 'Completed late',
            default => 'Upcoming',
        };
    }

    public function dueBadge(): string
    {
        return match ($this->dueStatus()) {
            'overdue' => 'bg-danger',
            'today' => 'bg-dark',
            'tomorrow' => 'bg-secondary',
            'completed' => 'bg-secondary',
            'completed_on_time' => 'bg-success',
            'completed_late' => 'bg-warning text-dark',
            default => 'bg-light text-dark',
        };
    }

    public function dueLabel(): string
    {
        if (!$this->due_date) return 'No due date';

        return Carbon::parse($this->due_date)->diffForHumans();
    }

    public function scopeSmartOrder($query)
    {
        $today = now()->toDateString();

        return $query
            ->orderByRaw(
                'CASE WHEN status = ? THEN 5 WHEN due_date < ? THEN 1 WHEN due_date = ? THEN 2 ELSE 3 END',
                [TodoStatus::Done->value, $today, $today]
            )
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->latest();
    }
}
