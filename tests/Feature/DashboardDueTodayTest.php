<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDueTodayTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_today_tasks_are_displayed_separately_by_status(): void
    {
        $user = User::factory()->create();

        foreach ([
            ['Pending due task', TodoStatus::Pending],
            ['In progress due task', TodoStatus::Progress],
            ['Completed due task', TodoStatus::Done],
        ] as [$title, $status]) {
            Todo::factory()->for($user)->create([
                'title' => $title,
                'status' => $status,
                'priority' => TodoPriority::Medium,
                'due_date' => today()->toDateString(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Due Today')
            ->assertSee('Pending')
            ->assertSee('In Progress')
            ->assertSee('Done')
            ->assertSee('Pending due task')
            ->assertSee('In progress due task')
            ->assertSee('Completed due task');
    }
}
