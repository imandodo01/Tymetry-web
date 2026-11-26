<?php
namespace Tests\Feature;
use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class TodoTymxExportTest extends TestCase
{
    use RefreshDatabase;
    public function test_selected_export_preserves_utf8_and_uses_public_identity(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create(['title' => 'Review café 🚀', 'description' => "Periksa AI.\nJangan ubah tanggal.", 'status' => TodoStatus::Progress, 'priority' => TodoPriority::High, 'due_date' => '2026-12-01', 'due_time' => '09:30:00']);
        Todo::factory()->for($user)->create(['title' => 'Not selected']);
        $response = $this->actingAs($user)->post(route('todo.export'), ['selection' => 'selected', 'ids' => [$todo->id]]);
        $response->assertOk();
        $this->assertStringContainsString('.tymx', $response->headers->get('content-disposition'));
        $payload = json_decode($response->streamedContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('tymx', $payload['format']);
        $this->assertSame(1, $payload['version']);
        $this->assertCount(1, $payload['todos']);
        $record = $payload['todos'][0];
        $this->assertSame($todo->public_id, $record['public_id']);
        $this->assertSame($record['base'], $record['data']);
        $this->assertSame('Review café 🚀', $record['data']['title']);
        $this->assertSame('in_progress', $record['data']['status']);
        $this->assertSame('high', $record['data']['priority']);
        $this->assertSame('09:30', $record['data']['due_time']);
        foreach (['id', 'user_id', 'completed_at', 'created_at', 'updated_at'] as $key) $this->assertArrayNotHasKey($key, $record);
    }
    public function test_all_active_export_excludes_archived_and_other_users(): void
    {
        $user = User::factory()->create(); $other = User::factory()->create();
        $active = Todo::factory()->for($user)->create(); $archived = Todo::factory()->for($user)->create(); $archived->delete();
        Todo::factory()->for($other)->create();
        $response = $this->actingAs($user)->post(route('todo.export'), ['selection' => 'all_active']);
        $response->assertOk(); $payload = json_decode($response->streamedContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $payload['todos']); $this->assertSame($active->public_id, $payload['todos'][0]['public_id']);
    }
    public function test_selected_export_hides_foreign_todo_ids(): void
    {
        $user = User::factory()->create(); $foreign = Todo::factory()->for(User::factory()->create())->create();
        $this->actingAs($user)->postJson(route('todo.export'), ['selection' => 'selected', 'ids' => [$foreign->id]])->assertNotFound();
    }
    public function test_selected_export_requires_ids(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('todo.export'), ['selection' => 'selected', 'ids' => []])->assertUnprocessable()->assertJsonValidationErrors('ids');
    }
    public function test_export_requires_authentication(): void
    {
        $this->postJson(route('todo.export'), ['selection' => 'all_active'])->assertUnauthorized();
    }
}
