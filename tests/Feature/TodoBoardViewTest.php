<?php

namespace Tests\\Feature;

use App\\Enums\\Todo\\TodoPriority;
use App\\Enums\\Todo\\TodoStatus;
use App\\Models\\Todo;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class TodoBoardViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_data_returns_only_the_authenticated_users_todos_in_smart_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Todo::factory()->for($user)->create([
            'title' => 'Low priority task',
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::Low,
            'due_date' => null,
        ]);
        Todo::factory()->for($user)->create([
            'title' => 'High priority task',
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::High,
            'due_date' => null,
        ]);
        Todo::factory()->for($otherUser)->create([
            'title' => 'Private task',
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::High,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('todo.board-data'));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'High priority task')
            ->assertJsonPath('data.0.status', TodoStatus::Pending->value)
            ->assertJsonPath('data.0.priority_label', 'High');

        $this->assertNotContains(
            'Private task',
            collect($response->json('data'))->pluck('title')->all()
        );
    }

    public function test_board_data_applies_the_same_title_status_and_priority_filters(): void
    {
        $user = User::factory()->create();

        Todo::factory()->for($user)->create([
            'title' => 'Monthly report',
            'status' => TodoStatus::Progress,
            'priority' => TodoPriority::High,
        ]);
        Todo::factory()->for($user)->create([
            'title' => 'Weekly report',
            'status' => TodoStatus::Progress,
            'priority' => TodoPriority::High,
        ]);
        Todo::factory()->for($user)->create([
            'title' => 'Monthly planning',
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::High,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('todo.board-data', [
                'search_title' => 'Monthly',
                'status' => TodoStatus::Progress->value,
                'priority' => TodoPriority::High->value,
            ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Monthly report');
    }

    public function test_board_view_is_available_from_the_todo_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('todo.index'))
            ->assertOk()
            ->assertSee('id="showTodoBoard"', false)
            ->assertSee('id="todoBoardColumns"', false)
            ->assertSee('Pending')
            ->assertSee('In Progress')
            ->assertSee('Done');
    }
}
