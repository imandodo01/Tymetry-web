<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoStatus;
use App\Models\ActivityLog;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoCompletionConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_done_sets_completion_timestamp_and_activity_log(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);

        $this->actingAs($user)
            ->postJson(route('todo.bulk.done'), ['ids' => [$todo->id]])
            ->assertOk()
            ->assertJson(['success' => true]);

        $todo->refresh();
        $this->assertSame(TodoStatus::Done, $todo->status);
        $this->assertNotNull($todo->completed_at);
        $this->assertDatabaseHas('activity_logs', [
            'todo_id' => $todo->id,
            'user_id' => $user->id,
            'action' => 'status_updated',
        ]);
    }

    public function test_toggling_done_todo_clears_completion_timestamp(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Done,
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('todo.complete', $todo->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $todo->refresh();
        $this->assertSame(TodoStatus::Pending, $todo->status);
        $this->assertNull($todo->completed_at);
    }


    public function test_bulk_archive_soft_deletes_todos_and_records_activity(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('todo.bulk.archive'), ['ids' => [$todo->id]])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('todos', ['id' => $todo->id]);
        $this->assertDatabaseHas('activity_logs', [
            'todo_id' => $todo->id,
            'user_id' => $user->id,
            'action' => 'archived',
        ]);
    }

    public function test_bulk_done_requires_a_non_empty_array_of_distinct_integer_ids(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('todo.bulk.done'), ['ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');
    }
}
