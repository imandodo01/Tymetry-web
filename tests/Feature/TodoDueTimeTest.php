<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoDueTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_time_is_saved_when_creating_a_todo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => 'Prepare report',
                'description' => '',
                'status' => TodoStatus::Pending->value,
                'priority' => TodoPriority::Medium->value,
                'due_date' => '2026-10-21',
                'due_time' => '14:30',
            ])
            ->assertRedirect(route('todo.index'));

        $todo = $user->todos()->where('title', 'Prepare report')->firstOrFail();

        $this->assertSame('14:30', substr((string) $todo->due_time, 0, 5));
    }

    public function test_due_time_must_use_a_valid_24_hour_time(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('todo.create'))
            ->post(route('todo.store'), [
                'title' => 'Prepare report',
                'description' => '',
                'status' => TodoStatus::Pending->value,
                'priority' => TodoPriority::Medium->value,
                'due_time' => '25:99',
            ])
            ->assertRedirect(route('todo.create'))
            ->assertSessionHasErrors('due_time');
    }
}
