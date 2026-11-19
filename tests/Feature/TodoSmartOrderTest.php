<?php

namespace Tests\Feature;

use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoSmartOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_order_uses_the_application_date_instead_of_the_database_server_date(): void
    {
        $this->travelTo(Carbon::parse('2026-12-31 00:30:00', 'UTC'));

        $user = User::factory()->create();
        $overdue = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::Medium,
            'due_date' => '2026-12-30',
        ]);
        $today = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::Medium,
            'due_date' => '2026-12-31',
        ]);
        $upcoming = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::Medium,
            'due_date' => '2027-01-01',
        ]);
        $completedOverdue = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Done,
            'priority' => TodoPriority::Medium,
            'due_date' => '2026-12-29',
        ]);

        $orderedIds = Todo::query()
            ->where('user_id', $user->id)
            ->smartOrder()
            ->pluck('id')
            ->all();

        $this->assertSame([
            $overdue->id,
            $today->id,
            $upcoming->id,
            $completedOverdue->id,
        ], $orderedIds);
    }

    public function test_smart_order_query_runs_with_the_configured_test_database(): void
    {
        Todo::factory()->count(2)->create();

        $todos = Todo::query()->smartOrder()->get();

        $this->assertCount(2, $todos);
    }
}
