<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_users_todo_edit_form(): void
    {
        [$owner, $otherUser, $todo] = $this->makeOwnedTodo();

        $this->actingAs($otherUser)
            ->get(route('todo.edit', $todo->id))
            ->assertNotFound();
    }

    public function test_user_cannot_update_another_users_todo(): void
    {
        [$owner, $otherUser, $todo] = $this->makeOwnedTodo();

        $this->actingAs($otherUser)
            ->put(route('todo.update', $todo->id), $this->validPayload($todo, [
                'title' => 'Unauthorized change',
                'status' => TodoStatus::Done->value,
            ]))
            ->assertNotFound();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $owner->id,
            'title' => $todo->title,
            'status' => TodoStatus::Pending->value,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_user_cannot_archive_another_users_todo(): void
    {
        [$owner, $otherUser, $todo] = $this->makeOwnedTodo();

        $this->actingAs($otherUser)
            ->delete(route('todo.destroy', $todo->id))
            ->assertNotFound();

        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'deleted_at' => null]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_user_cannot_change_another_users_todo_status(): void
    {
        [$owner, $otherUser, $todo] = $this->makeOwnedTodo();

        $this->actingAs($otherUser)
            ->postJson(route('todo.status', $todo->id), [
                'status' => TodoStatus::Done->value,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $owner->id,
            'status' => TodoStatus::Pending->value,
            'completed_at' => null,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_user_cannot_toggle_completion_for_another_users_todo(): void
    {
        [$owner, $otherUser, $todo] = $this->makeOwnedTodo();

        $this->actingAs($otherUser)
            ->postJson(route('todo.complete', $todo->id))
            ->assertNotFound();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $owner->id,
            'status' => TodoStatus::Pending->value,
            'completed_at' => null,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_user_cannot_change_another_users_todo_priority(): void
    {
        [$owner, $otherUser, $todo] = $this->makeOwnedTodo();

        $this->actingAs($otherUser)
            ->patchJson(route('todo.priority', $todo->id), [
                'priority' => TodoPriority::High->value,
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $owner->id,
            'priority' => TodoPriority::Low->value,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_user_cannot_change_another_users_due_date(): void
    {
        [$owner, $otherUser, $todo] = $this->makeOwnedTodo();

        $this->actingAs($otherUser)
            ->patchJson(route('todo.due-date', $todo->id), [
                'due_date' => '2026-12-31',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'user_id' => $owner->id,
            'due_date' => null,
        ]);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_archive_page_only_lists_the_authenticated_users_archived_todos(): void
    {
        [$owner, $otherUser, $ownedTodo] = $this->makeOwnedTodo('Owned archived task');
        [, , $otherTodo] = $this->makeOwnedTodo('Other users archived task', $otherUser);

        $ownedTodo->delete();
        $otherTodo->delete();

        $this->actingAs($owner)
            ->get(route('todo.archive'))
            ->assertOk()
            ->assertSeeText('Owned archived task')
            ->assertDontSeeText('Other users archived task');
    }

    public function test_ajax_todo_index_only_returns_the_authenticated_users_todos(): void
    {
        [$owner, $otherUser] = $this->makeOwnedTodo('Visible owned task');
        $this->makeOwnedTodo('Hidden other task', $otherUser);

        $response = $this->actingAs($owner)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('todo.index'));

        $response->assertOk();

        $titles = collect($response->json('data'))->pluck('title');

        $this->assertContains('Visible owned task', $titles);
        $this->assertNotContains('Hidden other task', $titles);
    }

    private function makeOwnedTodo(string $title = 'Authorization test task', ?User $owner = null): array
    {
        $owner ??= User::factory()->create();
        $otherUser = User::factory()->create();

        $todo = Todo::factory()->for($owner)->create([
            'title' => $title,
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::Low,
            'due_date' => null,
            'due_time' => null,
            'completed_at' => null,
        ]);

        return [$owner, $otherUser, $todo];
    }

    private function validPayload(Todo $todo, array $overrides = []): array
    {
        return array_merge([
            'title' => $todo->title,
            'description' => $todo->description,
            'status' => TodoStatus::Pending->value,
            'priority' => TodoPriority::Low->value,
            'due_date' => null,
            'due_time' => null,
        ], $overrides);
    }
}
