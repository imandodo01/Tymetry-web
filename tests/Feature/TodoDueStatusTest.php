<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TodoDueStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-27 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_past_due_incomplete_todo_is_overdue(): void
    {
        $todo = Todo::factory()->for(User::factory())->create([
            'status' => TodoStatus::Progress,
            'due_date' => '2026-11-26',
            'completed_at' => null,
        ]);

        $this->assertSame('overdue', $todo->dueStatus());
        $this->assertSame('Overdue', $todo->dueStatusLabel());
        $this->assertSame('bg-danger', $todo->dueBadge());
    }

    public function test_completed_todo_finished_before_its_due_date_is_on_time(): void
    {
        $todo = Todo::factory()->for(User::factory())->create([
            'status' => TodoStatus::Done,
            'due_date' => '2026-11-26',
            'completed_at' => '2026-11-25 18:30:00',
        ]);

        $this->assertSame('completed_on_time', $todo->dueStatus());
        $this->assertSame('Completed on time', $todo->dueStatusLabel());
        $this->assertSame('bg-success', $todo->dueBadge());
    }

    public function test_completed_todo_finished_on_its_due_date_is_on_time(): void
    {
        $todo = Todo::factory()->for(User::factory())->create([
            'status' => TodoStatus::Done,
            'due_date' => '2026-11-26',
            'completed_at' => '2026-11-26 23:59:00',
        ]);

        $this->assertSame('completed_on_time', $todo->dueStatus());
    }

    public function test_completed_todo_finished_after_its_due_date_is_late_not_overdue(): void
    {
        $todo = Todo::factory()->for(User::factory())->create([
            'status' => TodoStatus::Done,
            'due_date' => '2026-11-25',
            'completed_at' => '2026-11-26 08:00:00',
        ]);

        $this->assertSame('completed_late', $todo->dueStatus());
        $this->assertSame('Completed late', $todo->dueStatusLabel());
        $this->assertSame('bg-warning text-dark', $todo->dueBadge());
    }

    public function test_legacy_completed_todo_without_completion_timestamp_is_not_marked_overdue(): void
    {
        $todo = Todo::factory()->for(User::factory())->create([
            'status' => TodoStatus::Done,
            'due_date' => '2026-11-25',
            'completed_at' => null,
        ]);

        DB::table('todos')->where('id', $todo->id)->update(['completed_at' => null]);
        $todo->refresh();

        $this->assertSame('completed', $todo->dueStatus());
        $this->assertSame('Completed', $todo->dueStatusLabel());
    }

    public function test_completed_todo_without_due_date_has_no_due_indicator(): void
    {
        $todo = Todo::factory()->for(User::factory())->create([
            'status' => TodoStatus::Done,
            'due_date' => null,
            'completed_at' => '2026-11-26 08:00:00',
        ]);

        $this->assertSame('none', $todo->dueStatus());
    }

    public function test_due_today_and_tomorrow_remain_unchanged_for_incomplete_todos(): void
    {
        $today = Todo::factory()->for(User::factory())->create([
            'status' => TodoStatus::Pending,
            'due_date' => '2026-11-27',
            'completed_at' => null,
        ]);
        $tomorrow = Todo::factory()->for(User::factory())->create([
            'status' => TodoStatus::Pending,
            'due_date' => '2026-11-28',
            'completed_at' => null,
        ]);

        $this->assertSame('today', $today->dueStatus());
        $this->assertSame('tomorrow', $tomorrow->dueStatus());
    }
    public function test_board_data_exposes_the_completed_late_indicator(): void
    {
        $user = User::factory()->create();

        Todo::factory()->for($user)->create([
            'status' => TodoStatus::Done,
            'due_date' => '2026-11-25',
            'completed_at' => '2026-11-26 08:00:00',
        ]);

        $this->actingAs($user)
            ->getJson(route('todo.board-data'))
            ->assertOk()
            ->assertJsonPath('data.0.due_status', 'completed_late')
            ->assertJsonPath('data.0.due_status_label', 'Completed late')
            ->assertJsonPath('data.0.due_badge', 'bg-warning text-dark');
    }

}
