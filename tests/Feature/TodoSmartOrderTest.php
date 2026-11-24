<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TodoSmartOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_order_uses_the_application_date_instead_of_the_database_server_date(): void
    {
        $originalTimezone = date_default_timezone_get();
        $originalAppTimezone = config('app.timezone');

        try {
            $databaseToday = Carbon::parse(
                DB::selectOne("SELECT date('now') AS today")->today,
                'UTC'
            );
            $applicationTimezone = 'Pacific/Kiritimati';

            config(['app.timezone' => $applicationTimezone]);
            date_default_timezone_set($applicationTimezone);

            $this->travelTo(
                $databaseToday->copy()
                    ->setTime(12, 0)
                    ->setTimezone($applicationTimezone)
            );

            $this->assertSame(
                $databaseToday->copy()->addDay()->toDateString(),
                now()->toDateString()
            );

            $user = User::factory()->create();
            $lowerPriority = Todo::factory()->for($user)->create([
                'status' => TodoStatus::Pending,
                'priority' => TodoPriority::Low,
                'due_date' => $databaseToday->copy()->subDay()->toDateString(),
            ]);
            $higherPriority = Todo::factory()->for($user)->create([
                'status' => TodoStatus::Pending,
                'priority' => TodoPriority::High,
                'due_date' => $databaseToday->toDateString(),
            ]);

            $orderedIds = Todo::query()
                ->where('user_id', $user->id)
                ->smartOrder()
                ->pluck('id')
                ->all();

            $this->assertSame([
                $higherPriority->id,
                $lowerPriority->id,
            ], $orderedIds);
        } finally {
            Carbon::setTestNow();
            date_default_timezone_set($originalTimezone);
            config(['app.timezone' => $originalAppTimezone]);
        }
    }

    public function test_smart_order_query_runs_with_the_configured_test_database(): void
    {
        Todo::factory()->count(2)->create();

        $todos = Todo::query()->smartOrder()->get();

        $this->assertCount(2, $todos);
    }
}
