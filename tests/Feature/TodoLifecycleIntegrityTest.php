<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoLifecycleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_done_todo_sets_its_completion_timestamp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Already completed task',
                'description' => '',
                'status' => TodoStatus::Done->value,
                'priority' => 1,
            ])
            ->assertRedirect(route('todo.index'));

        $todo = $user->todos()->where('title', 'Already completed task')->firstOrFail();

        $this->assertSame(TodoStatus::Done, $todo->status);
        $this->assertNotNull($todo->completed_at);
    }

    public function test_editing_status_to_done_sets_and_reopening_clears_completion_timestamp(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);

        $payload = [
            'title' => $todo->title,
            'description' => $todo->description,
            'status' => TodoStatus::Done->value,
            'priority' => $todo->priority->value,
        ];

        $this->actingAs($user)
            ->put(route('todo.update', $todo->id), $payload)
            ->assertRedirect(route('todo.index'));

        $todo->refresh();
        $this->assertSame(TodoStatus::Done, $todo->status);
        $this->assertNotNull($todo->completed_at);

        $payload['status'] = TodoStatus::Pending->value;

        $this->put(route('todo.update', $todo->id), $payload)
            ->assertRedirect(route('todo.index'));

        $todo->refresh();
        $this->assertSame(TodoStatus::Pending, $todo->status);
        $this->assertNull($todo->completed_at);
    }

    public function test_status_endpoint_preserves_original_completion_time_and_clears_it_when_reopened(): void
    {
        $user = User::factory()->create();
        $completedAt = now()->subDays(2)->startOfSecond();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Done,
            'completed_at' => $completedAt,
        ]);

        $this->actingAs($user)
            ->postJson(route('todo.status', $todo->id), ['status' => TodoStatus::Done->value])
            ->assertOk();

        $todo->refresh();
        $this->assertEquals($completedAt, $todo->completed_at);

        $this->postJson(route('todo.status', $todo->id), ['status' => TodoStatus::Progress->value])
            ->assertOk();

        $todo->refresh();
        $this->assertNull($todo->completed_at);
    }

    public function test_single_archive_and_restore_preserve_completion_state_and_record_activity(): void
    {
        $user = User::factory()->create();
        $completedAt = now()->subDay()->startOfSecond();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Done,
            'completed_at' => $completedAt,
        ]);

        $this->actingAs($user)
            ->delete(route('todo.destroy', $todo->id))
            ->assertRedirect(route('todo.index'));

        $this->assertSoftDeleted('todos', ['id' => $todo->id]);
        $this->assertDatabaseHas('activity_logs', [
            'todo_id' => $todo->id,
            'user_id' => $user->id,
            'action' => 'archived',
        ]);

        $this->post(route('todo.restore', $todo->id))
            ->assertRedirect(route('todo.archive'));

        $todo->refresh();
        $this->assertSame(TodoStatus::Done, $todo->status);
        $this->assertEquals($completedAt, $todo->completed_at);
        $this->assertDatabaseHas('activity_logs', [
            'todo_id' => $todo->id,
            'user_id' => $user->id,
            'action' => 'restored',
        ]);
    }

    public function test_user_cannot_restore_another_users_archived_todo(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->for($owner)->create();
        $todo->delete();

        $this->actingAs($otherUser)
            ->post(route('todo.restore', $todo->id))
            ->assertNotFound();

        $this->assertSoftDeleted('todos', ['id' => $todo->id]);
    }

    public function test_inline_due_date_update_can_clear_date_and_is_logged(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'due_date' => '2026-11-20',
        ]);

        $this->actingAs($user)
            ->patchJson(route('todo.due-date', $todo->id), ['due_date' => null])
            ->assertOk()
            ->assertJson(['success' => true]);

        $todo->refresh();
        $this->assertNull($todo->due_date);
        $this->assertDatabaseHas('activity_logs', [
            'todo_id' => $todo->id,
            'user_id' => $user->id,
            'action' => 'due_date_updated',
        ]);
    }
}
