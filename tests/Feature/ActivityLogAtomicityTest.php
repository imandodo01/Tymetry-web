<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoStatus;
use App\Models\ActivityLog;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class ActivityLogAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public function test_todo_creation_rolls_back_when_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();

        $this->failActivityLogWrites();
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('todo.store'), [
                'title' => 'Atomicity check',
                'description' => 'Must not persist without its activity record.',
                'status' => TodoStatus::Pending->value,
                'priority' => 1,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated activity log failure.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('todos', [
            'user_id' => $user->id,
            'title' => 'Atomicity check',
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_todo_status_update_rolls_back_when_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);

        $this->failActivityLogWrites();
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->postJson(route('todo.status', $todo->id), [
                'status' => TodoStatus::Done->value,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated activity log failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'status' => TodoStatus::Pending->value,
            'completed_at' => null,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_todo_priority_update_rolls_back_when_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'priority' => 1,
        ]);

        $this->failActivityLogWrites();
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->patchJson(route('todo.priority', $todo->id), [
                'priority' => 2,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated activity log failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $user->id,
            'priority' => 1,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }


    public function test_todo_update_rolls_back_when_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create(['title' => 'Original title']);

        $this->failActivityLogWrites();
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->put(route('todo.update', $todo->id), [
                'title' => 'Changed title',
                'description' => null,
                'status' => TodoStatus::Pending->value,
                'priority' => 1,
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated activity log failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Original title',
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_todo_archive_rolls_back_when_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();

        $this->failActivityLogWrites();
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->delete(route('todo.destroy', $todo->id));
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated activity log failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_todo_restore_rolls_back_when_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();
        $todo->delete();

        $this->failActivityLogWrites();
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('todo.restore', $todo->id));
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated activity log failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'deleted_at' => $todo->getRawOriginal('deleted_at'),
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_todo_completion_toggle_rolls_back_when_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);

        $this->failActivityLogWrites();
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->postJson(route('todo.complete', $todo->id));
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated activity log failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'status' => TodoStatus::Pending->value,
            'completed_at' => null,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_todo_due_date_update_rolls_back_when_activity_log_creation_fails(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'due_date' => '2026-11-20',
        ]);

        $this->failActivityLogWrites();
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->patchJson(route('todo.due-date', $todo->id), [
                'due_date' => '2026-11-21',
            ]);
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated activity log failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'due_date' => '2026-11-20',
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    private function failActivityLogWrites(): void
    {
        Event::listen('eloquent.creating: ' . ActivityLog::class, static function (): void {
            throw new RuntimeException('Simulated activity log failure.');
        });
    }
}
