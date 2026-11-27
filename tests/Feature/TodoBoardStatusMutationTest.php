<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoBoardStatusMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_status_mutation_persists_status_and_activity_log(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);

        $this->actingAs($user)
            ->postJson(route('todo.status', $todo->id), [
                'status' => TodoStatus::Done->value,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $todo->refresh();

        $this->assertSame(TodoStatus::Done, $todo->status);
        $this->assertNotNull($todo->completed_at);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_moving_completed_todo_back_to_progress_clears_completion_timestamp(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Done,
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('todo.status', $todo->id), [
                'status' => TodoStatus::Progress->value,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $todo->refresh();

        $this->assertSame(TodoStatus::Progress, $todo->status);
        $this->assertNull($todo->completed_at);
        $this->assertDatabaseCount('activity_logs', 1);
    }
}
