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

    public function test_dashboard_prioritizes_open_tasks_and_avoids_repeating_completed_tasks(): void
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
            ->assertSeeInOrder(['Overdue', 'Due Today', 'Upcoming', 'Recent Activity', 'Task Status'])
            ->assertSee('Pending due task')
            ->assertSee('In progress due task')
            ->assertDontSee('Completed due task')
            ->assertSee('No overdue tasks')
            ->assertSee('No open tasks due today')
            ->assertSee('Nothing scheduled after today');
    }
}
