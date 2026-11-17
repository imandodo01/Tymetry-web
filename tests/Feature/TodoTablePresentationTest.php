<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTablePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_rendered_as_a_badge_and_completion_remains_an_action(): void
    {
        $user = User::factory()->create();
        Todo::factory()->for($user)->create([
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('todo.index'));

        $response->assertOk();

        $rows = $response->json('data');

        $this->assertCount(1, $rows);
        $this->assertSame(
            '<span class="badge bg-secondary">Pending</span>',
            $rows[0]['status_badge']
        );
        $this->assertStringContainsString('toggleCompleteBtn', $rows[0]['action']);
    }
    public function test_priority_action_is_returned_as_renderable_html(): void
    {
        $user = User::factory()->create();
        Todo::factory()->for($user)->create([
            'priority' => 2,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('todo.index'));

        $response->assertOk();

        $priorityAction = $response->json('data.0.priority_action');

        $this->assertStringContainsString('<select', $priorityAction);
        $this->assertStringContainsString('class="form-select form-select-sm inlinePriority"', $priorityAction);
        $this->assertStringContainsString('<option value="2" selected>', $priorityAction);
        $this->assertStringNotContainsString('&lt;select', $priorityAction);
    }


    public function test_task_table_controls_have_accessible_names(): void
    {
        $user = User::factory()->create();
        Todo::factory()->for($user)->create([
            'title' => 'Prepare weekly report',
            'status' => 0,
            'priority' => 2,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('todo.index'));

        $response->assertOk();

        $row = $response->json('data.0');

        $this->assertStringContainsString('aria-label="Select task: Prepare weekly report"', $row['checkbox']);
        $this->assertStringContainsString('aria-label="Change status for task: Prepare weekly report"', $row['status_action']);
        $this->assertStringContainsString('aria-label="Change priority for task: Prepare weekly report"', $row['priority_action']);
        $this->assertStringContainsString('aria-label="Change due date for task: Prepare weekly report"', $row['due_action']);
        $this->assertStringContainsString('aria-label="Mark task complete: Prepare weekly report"', $row['action']);
        $this->assertStringContainsString('aria-label="Edit task: Prepare weekly report"', $row['action']);
        $this->assertStringContainsString('aria-label="Archive task: Prepare weekly report"', $row['action']);
    }

    public function test_task_table_headers_describe_their_controls(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('todo.index'));

        $response->assertOk()
            ->assertSee('Completion')
            ->assertSee('Change Status')
            ->assertSee('aria-label="Search tasks by title"', false)
            ->assertSee('aria-label="Filter tasks by status"', false)
            ->assertSee('aria-label="Filter tasks by priority"', false)
            ->assertSee('aria-label="Select all tasks on this page"', false);
    }

}
