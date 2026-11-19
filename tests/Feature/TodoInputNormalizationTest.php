<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoInputNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_trims_title_and_normalizes_blank_description_to_null(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => '  Prepare release notes  ',
                'description' => " \t  ",
                'status' => 0,
                'priority' => 1,
            ])
            ->assertRedirect(route('todo.index'));

        $todo = Todo::query()->where('user_id', $user->id)->sole();

        $this->assertSame('Prepare release notes', $todo->title);
        $this->assertNull($todo->description);
    }

    public function test_update_trims_title_and_preserves_non_empty_description_text(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'title' => 'Old title',
            'description' => null,
        ]);

        $this->actingAs($user)
            ->put(route('todo.update', $todo->id), [
                'title' => "  New title  ",
                'description' => '  Keep this text  ',
                'status' => 0,
                'priority' => 1,
            ])
            ->assertRedirect(route('todo.index'));

        $todo->refresh();

        $this->assertSame('New title', $todo->title);
        $this->assertSame('  Keep this text  ', $todo->description);
    }

    public function test_whitespace_only_title_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => " \t\n ",
                'description' => null,
                'status' => 0,
                'priority' => 1,
            ])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('todos', 0);
    }

    public function test_title_limit_counts_unicode_code_points_not_utf16_code_units(): void
    {
        $user = User::factory()->create();
        $validTitle = str_repeat('😀', 255);

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => $validTitle,
                'description' => null,
                'status' => 0,
                'priority' => 1,
            ])
            ->assertRedirect(route('todo.index'));

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => $validTitle,
        ]);

        $this->actingAs($user)
            ->post(route('todo.store'), [
                'title' => str_repeat('😀', 256),
                'description' => null,
                'status' => 0,
                'priority' => 1,
            ])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('todos', 1);
    }
}
