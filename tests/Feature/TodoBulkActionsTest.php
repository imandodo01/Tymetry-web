<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_done_updates_only_selected_todos_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selectedTodo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);
        $unselectedTodo = Todo::factory()->for($user)->create([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);
        $otherUsersTodo = Todo::factory()->for($otherUser)->create([
            'status' => TodoStatus::Pending,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('todo.bulk.done'), [
            'ids' => [$selectedTodo->id, $otherUsersTodo->id],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('todos', [
            'id' => $selectedTodo->id,
            'status' => TodoStatus::Done->value,
        ]);
        $this->assertDatabaseHas('todos', [
            'id' => $unselectedTodo->id,
            'status' => TodoStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('todos', [
            'id' => $otherUsersTodo->id,
            'status' => TodoStatus::Pending->value,
        ]);
    }

    public function test_bulk_archive_archives_only_selected_todos_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selectedTodo = Todo::factory()->for($user)->create();
        $unselectedTodo = Todo::factory()->for($user)->create();
        $otherUsersTodo = Todo::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->postJson(route('todo.bulk.archive'), [
            'ids' => [$selectedTodo->id, $otherUsersTodo->id],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertSoftDeleted('todos', ['id' => $selectedTodo->id]);
        $this->assertDatabaseHas('todos', ['id' => $unselectedTodo->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('todos', ['id' => $otherUsersTodo->id, 'deleted_at' => null]);
    }

    public function test_bulk_actions_reject_empty_or_duplicate_selection(): void
    {
        $user = User::factory()->create();

        $todo = Todo::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('todo.bulk.done'), ['ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');

        $this->postJson(route('todo.bulk.done'), ['ids' => [$todo->id, $todo->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids.1');
    }

    public function test_bulk_restore_restores_only_selected_todos_owned_by_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedArchivedTodo = Todo::factory()->for($user)->create();
        $ownedArchivedTodo->delete();

        $otherUsersArchivedTodo = Todo::factory()->for($otherUser)->create();
        $otherUsersArchivedTodo->delete();

        $response = $this->actingAs($user)->postJson(route('todo.bulk.restore'), [
            'ids' => [$ownedArchivedTodo->id, $otherUsersArchivedTodo->id],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('todos', [
            'id' => $ownedArchivedTodo->id,
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('todos', ['id' => $otherUsersArchivedTodo->id]);
    }

    public function test_bulk_restore_rejects_malformed_selection(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('todo.bulk.restore'), ['ids' => 'not-an-array'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');

        $this->postJson(route('todo.bulk.restore'), ['ids' => ['invalid-id']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids.0');
    }
}
