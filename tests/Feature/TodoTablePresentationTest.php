<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTablePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_rendered_as_a_badge_and_completion_remains_an_action(): void
    {
        $user = User::factory()->create();
        Todo::factory()->for($user)->create([
            'status' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('todo.index'));

        $response->assertOk();

        $rows = $response->json('data');

        $this->assertCount(1, $rows);
        $this->assertSame(
            '<span class="badge bg-secondary">Pending</span>',
            $rows[0]['status_badge']
        );
        $this->assertStringContainsString('toggleCompleteBtn', $rows[0]['action']);
    }
}
