<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoAjaxEnumValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_endpoint_rejects_unknown_status_values(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('todo.status', $todo->id), ['status' => 99])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_priority_endpoint_rejects_unknown_priority_values(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();

        $this->actingAs($user)
            ->patchJson(route('todo.priority', $todo->id), ['priority' => 99])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('priority');
    }

    public function test_status_and_priority_endpoints_accept_defined_enum_values(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();

        $this->actingAs($user)
            ->postJson(route('todo.status', $todo->id), ['status' => TodoStatus::Progress->value])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->patchJson(route('todo.priority', $todo->id), ['priority' => TodoPriority::High->value])
            ->assertOk()
            ->assertJson(['success' => true]);

        $todo->refresh();
        $this->assertSame(TodoStatus::Progress, $todo->status);
        $this->assertSame(TodoPriority::High, $todo->priority);
    }
}
