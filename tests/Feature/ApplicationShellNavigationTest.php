<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationShellNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_workspace_shows_only_available_navigation_destinations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Tymetry')
            ->assertSee('Dashboard')
            ->assertSee('My Tasks')
            ->assertSee('Archive')
            ->assertSee('Profile')
            ->assertSee('New task')
            ->assertSee('Log out')
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('Inbox')
            ->assertDontSee('My Projects')
            ->assertDontSee('ADMIN');
    }

    public function test_task_navigation_remains_active_on_task_creation_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('todo.create'))
            ->assertOk()
            ->assertSee('My Tasks')
            ->assertSee('aria-current="page"', false);
    }
}
