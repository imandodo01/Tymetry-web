<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\ActivityLog;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TodoTymxImportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_identifies_proposed_updates_without_mutating_todos(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'title' => 'Original title',
            'description' => 'Original description',
            'status' => TodoStatus::Pending,
            'priority' => TodoPriority::Low,
            'due_date' => '2026-12-01',
            'due_time' => '09:30:00',
        ]);
        $base = $this->snapshot($todo);
        $data = array_merge($base, ['title' => 'Updated from file', 'priority' => 'high']);

        $response = $this->upload($user, $this->document([
            $this->record($todo->public_id, $base, $data),
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('summary.updates', 1)
            ->assertJsonPath('records.0.action', 'update')
            ->assertJsonPath('records.0.changes.0.field', 'title')
            ->assertJsonPath('records.0.changes.0.resolution', 'proposed');

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Original title',
            'priority' => TodoPriority::Low->value,
        ]);
        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_preview_detects_three_way_conflicts_per_field(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create(['title' => 'Base title']);
        $base = $this->snapshot($todo);
        $todo->update(['title' => 'Changed on server']);
        $data = array_merge($base, ['title' => 'Changed in file']);

        $response = $this->upload($user, $this->document([
            $this->record($todo->public_id, $base, $data),
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.conflicts', 1)
            ->assertJsonPath('records.0.action', 'conflict')
            ->assertJsonPath('records.0.changes.0.resolution', 'conflict');

        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'title' => 'Changed on server']);
        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_preview_reports_server_only_changes_without_overwriting_them(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create(['title' => 'Base title']);
        $base = $this->snapshot($todo);
        $todo->update(['description' => 'Changed only on server']);
        $data = array_merge($base, ['title' => 'Base title']);

        $response = $this->upload($user, $this->document([
            $this->record($todo->public_id, $base, $data),
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.unchanged', 1)
            ->assertJsonPath('records.0.action', 'unchanged')
            ->assertJsonPath('records.0.changes.0.field', 'description')
            ->assertJsonPath('records.0.changes.0.resolution', 'server_only');

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'description' => 'Changed only on server',
        ]);
    }

    public function test_preview_marks_a_proposal_already_applied_on_server_as_current(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create(['title' => 'Base title']);
        $base = $this->snapshot($todo);
        $data = array_merge($base, ['title' => 'Updated proposal']);
        $todo->update(['title' => 'Updated proposal']);

        $response = $this->upload($user, $this->document([
            $this->record($todo->public_id, $base, $data),
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.unchanged', 1)
            ->assertJsonPath('records.0.action', 'unchanged')
            ->assertJsonPath('records.0.changes.0.resolution', 'already_current');
    }

    public function test_preview_marks_unknown_and_other_users_public_ids_as_unmatched(): void
    {
        $user = User::factory()->create();
        $foreignTodo = Todo::factory()->for(User::factory()->create())->create();
        $unknownId = '018f0000-0000-7000-8000-000000000001';
        $foreignBase = $this->snapshot($foreignTodo);
        $unknownBase = array_merge($foreignBase, ['title' => 'Unknown task']);

        $response = $this->upload($user, $this->document([
            $this->record($foreignTodo->public_id, $foreignBase, $foreignBase),
            $this->record($unknownId, $unknownBase, $unknownBase),
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.unmatched', 2)
            ->assertJsonPath('records.0.action', 'unmatched')
            ->assertJsonPath('records.1.action', 'unmatched');

        $this->assertSame(1, Todo::query()->whereKey($foreignTodo->id)->count());
        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_preview_matches_public_ids_without_case_sensitivity(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();
        $snapshot = $this->snapshot($todo);

        $response = $this->upload($user, $this->document([
            $this->record(strtoupper($todo->public_id), $snapshot, $snapshot),
        ]));

        $response->assertOk()
            ->assertJsonPath('summary.unmatched', 0)
            ->assertJsonPath('summary.unchanged', 1)
            ->assertJsonPath('records.0.public_id', strtoupper($todo->public_id))
            ->assertJsonPath('records.0.action', 'unchanged');
    }

    public function test_preview_rejects_duplicate_public_ids(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();
        $snapshot = $this->snapshot($todo);

        $this->upload($user, $this->document([
            $this->record($todo->public_id, $snapshot, $snapshot),
            $this->record(strtoupper($todo->public_id), $snapshot, $snapshot),
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_preview_rejects_invalid_json_and_unsupported_schema_versions(): void
    {
        $user = User::factory()->create();

        $this->uploadRaw($user, '{not-json', 'invalid.tymx')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $document = $this->document([]);
        $document['version'] = 2;

        $this->upload($user, $document)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_preview_rejects_invalid_fields_and_unknown_properties(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();
        $snapshot = $this->snapshot($todo);
        $invalid = array_merge($snapshot, ['status' => 'blocked']);
        $document = $this->document([$this->record($todo->public_id, $snapshot, $invalid)]);

        $this->upload($user, $document)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $document = $this->document([$this->record($todo->public_id, $snapshot, $snapshot)]);
        $document['unexpected'] = true;

        $this->upload($user, $document)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_preview_rejects_non_tymx_file_extensions(): void
    {
        $user = User::factory()->create();

        $this->uploadRaw($user, json_encode($this->document([]), JSON_THROW_ON_ERROR), 'tasks.json')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_preview_requires_authentication(): void
    {
        $this->withHeader('Accept', 'application/json')
            ->post(route('todo.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent(
                    'empty.tymx',
                    json_encode($this->document([]), JSON_THROW_ON_ERROR)
                ),
            ])
            ->assertUnauthorized();
    }

    private function upload(User $user, array $document)
    {
        return $this->uploadRaw(
            $user,
            json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'import.tymx'
        );
    }

    private function uploadRaw(User $user, string $contents, string $filename)
    {
        return $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('todo.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent($filename, $contents),
            ]);
    }

    private function document(array $records): array
    {
        return [
            'format' => 'tymx',
            'version' => 1,
            'exported_at' => '2026-11-26T12:00:00Z',
            'source' => ['application' => 'Tymetry'],
            'selection' => 'selected',
            'todos' => $records,
        ];
    }

    private function record(string $publicId, array $base, array $data): array
    {
        return [
            'public_id' => $publicId,
            'base' => $base,
            'data' => $data,
        ];
    }

    private function snapshot(Todo $todo): array
    {
        return [
            'title' => $todo->title,
            'description' => $todo->description,
            'status' => match ($todo->status) {
                TodoStatus::Pending => 'pending',
                TodoStatus::Progress => 'in_progress',
                TodoStatus::Done => 'done',
            },
            'priority' => match ($todo->priority) {
                TodoPriority::Low => 'low',
                TodoPriority::Medium => 'medium',
                TodoPriority::High => 'high',
            },
            'due_date' => $todo->due_date ? \Carbon\Carbon::parse($todo->due_date)->toDateString() : null,
            'due_time' => $todo->due_time ? substr((string) $todo->due_time, 0, 5) : null,
        ];
    }
}
