<?php

namespace Tests\Feature;

use App\Models\Todo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoSmartOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_order_query_runs_with_the_configured_test_database(): void
    {
        Todo::factory()->count(2)->create();

        $todos = Todo::query()->smartOrder()->get();

        $this->assertCount(2, $todos);
    }
}
