<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTableFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_and_priority_filters_are_applied_together(): void
    {
        $user = User::factory()->create();

        Todo::factory()->for($user)->create([
            'title' => 'High priority in progress',
            'status' => TodoStatus::Progress,
            'priority' => TodoPriority::High,
        ]);
        Todo::factory()->for($user)->create([
            'title' => 'Low priority in progress',
            'status' => TodoStatus::Progress,
            'priority' => TodoPriority::Low,
        ]);
        Todo::factory()->for($user)->create([
            'title' => 'High priority pending',
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::High,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('todo.index', [
                'status' => TodoStatus::Progress->value,
                'priority' => TodoPriority::High->value,
            ]));

        $response->assertOk();

        $rows = $response->json('data');

        $this->assertCount(1, $rows);
        $this->assertSame('High priority in progress', $rows[0]['title']);
    }

    public function test_title_search_can_be_combined_with_status_and_priority_filters(): void
    {
        $user = User::factory()->create();

        Todo::factory()->for($user)->create([
            'title' => 'Prepare monthly report',
            'status' => TodoStatus::Progress,
            'priority' => TodoPriority::High,
        ]);
        Todo::factory()->for($user)->create([
            'title' => 'Prepare weekly report',
            'status' => TodoStatus::Progress,
            'priority' => TodoPriority::High,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('todo.index', [
                'search_title' => 'monthly',
                'status' => TodoStatus::Progress->value,
                'priority' => TodoPriority::High->value,
            ]));

        $response->assertOk();

        $rows = $response->json('data');

        $this->assertCount(1, $rows);
        $this->assertSame('Prepare monthly report', $rows[0]['title']);
    }

    public function test_task_filter_toolbar_provides_a_clear_filters_action(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertSee('id="resetTodoFilters"', false)
            ->assertSee('Clear filters')
            ->assertSee('id="selectedTodoCount"', false)
            ->assertSee('id="bulkDone" disabled', false)
            ->assertSee('id="bulkArchive" disabled', false)
            ->assertSee('id="todoTableLoading"', false)
            ->assertSee('id="todoTableError"', false)
            ->assertSee('id="retryTodoTable"', false)
            ->assertSee('role="status"', false)
            ->assertSee('role="alert"', false);
    }
}
