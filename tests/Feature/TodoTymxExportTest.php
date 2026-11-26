<?php

namespace Tests\Feature;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoTymxExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_export_preserves_utf8_and_uses_public_identity(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'title' => 'Review café 🚀',
            'description' => "Periksa AI.\nJangan ubah tanggal.",
            'status' => TodoStatus::Progress,
            'priority' => TodoPriority::High,
            'due_date' => '2026-12-01',
            'due_time' => '09:30:00',
        ]);
        Todo::factory()->for($user)->create(['title' => 'Not selected']);

        $response = $this->actingAs($user)->post(route('todo.export'), [
            'selection' => 'selected',
            'ids' => [$todo->id],
        ]);

        $response->assertOk();
        $this->assertStringContainsString('.tymx', $response->headers->get('content-disposition'));

        $payload = json_decode($response->streamedContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTymxPayloadMatchesV1SchemaContract($payload);
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

        foreach (['id', 'user_id', 'completed_at', 'created_at', 'updated_at'] as $key) {
            $this->assertArrayNotHasKey($key, $record);
        }
    }

    public function test_all_active_export_excludes_archived_and_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $active = Todo::factory()->for($user)->create();
        $archived = Todo::factory()->for($user)->create();
        $archived->delete();
        Todo::factory()->for($other)->create();

        $response = $this->actingAs($user)->post(route('todo.export'), ['selection' => 'all_active']);

        $response->assertOk();
        $payload = json_decode($response->streamedContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTymxPayloadMatchesV1SchemaContract($payload);
        $this->assertCount(1, $payload['todos']);
        $this->assertSame($active->public_id, $payload['todos'][0]['public_id']);
    }

    public function test_export_schema_contract_preserves_explicit_null_optional_values(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create([
            'description' => null,
            'due_date' => null,
            'due_time' => null,
        ]);

        $response = $this->actingAs($user)->post(route('todo.export'), ['selection' => 'all_active']);

        $response->assertOk();
        $payload = json_decode($response->streamedContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTymxPayloadMatchesV1SchemaContract($payload);
        $this->assertSame($todo->public_id, $payload['todos'][0]['public_id']);
        $this->assertNull($payload['todos'][0]['data']['description']);
        $this->assertNull($payload['todos'][0]['data']['due_date']);
        $this->assertNull($payload['todos'][0]['data']['due_time']);
    }

    public function test_selected_export_hides_foreign_todo_ids(): void
    {
        $user = User::factory()->create();
        $foreign = Todo::factory()->for(User::factory()->create())->create();

        $this->actingAs($user)
            ->postJson(route('todo.export'), ['selection' => 'selected', 'ids' => [$foreign->id]])
            ->assertNotFound();
    }

    public function test_selected_export_requires_ids(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('todo.export'), ['selection' => 'selected', 'ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');
    }

    public function test_export_requires_authentication(): void
    {
        $this->postJson(route('todo.export'), ['selection' => 'all_active'])
            ->assertUnauthorized();
    }

    public function test_selected_export_rejects_duplicate_ids(): void
    {
        $todo = Todo::factory()->create();

        $this->actingAs($todo->user)
            ->postJson(route('todo.export'), [
                'selection' => 'selected',
                'ids' => [$todo->id, $todo->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids.1');
    }

    public function test_selected_export_rejects_more_than_one_hundred_ids(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('todo.export'), [
                'selection' => 'selected',
                'ids' => range(1, 101),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');
    }

    public function test_export_rejects_unsupported_selection(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('todo.export'), ['selection' => 'everything'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('selection');
    }

    /**
     * Assert the exported document against the constraints declared by the
     * committed TYMX v1 JSON Schema, without adding a runtime dependency.
     *
     * @param  array<string, mixed>  $payload
     */
    private function assertTymxPayloadMatchesV1SchemaContract(array $payload): void
    {
        $schemaPath = base_path('docs/schemas/tymx-v1.schema.json');
        $this->assertFileExists($schemaPath);

        $schemaContents = file_get_contents($schemaPath);
        $this->assertIsString($schemaContents);
        $this->assertTrue(mb_check_encoding($schemaContents, 'UTF-8'));

        $schema = json_decode($schemaContents, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('https://json-schema.org/draft/2020-12/schema', $schema['$schema']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertSame(
            ['format', 'version', 'exported_at', 'source', 'selection', 'todos'],
            $schema['required']
        );
        $this->assertSame($schema['required'], array_keys($payload));
        $this->assertSame('tymx', $payload['format']);
        $this->assertSame(1, $payload['version']);
        $this->assertContains($payload['selection'], $schema['properties']['selection']['enum']);

        $this->assertSame('date-time', $schema['properties']['exported_at']['format']);
        $exportedAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $payload['exported_at']);
        $this->assertInstanceOf(DateTimeImmutable::class, $exportedAt);
        $this->assertSame($payload['exported_at'], $exportedAt->format(DateTimeInterface::ATOM));

        $sourceSchema = $schema['properties']['source'];
        $this->assertFalse($sourceSchema['additionalProperties']);
        $this->assertSame($sourceSchema['required'], array_keys($payload['source']));
        $this->assertSame('Tymetry', $payload['source']['application']);

        $todosSchema = $schema['properties']['todos'];
        $this->assertSame('array', $todosSchema['type']);
        $todoSchema = $todosSchema['items'];
        $this->assertFalse($todoSchema['additionalProperties']);

        foreach ($payload['todos'] as $todo) {
            $this->assertSame($todoSchema['required'], array_keys($todo));
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $todo['public_id']
            );

            foreach (['base', 'data'] as $snapshotName) {
                $snapshot = $todo[$snapshotName];
                $fieldsSchema = $schema['$defs']['fields'];

                $this->assertFalse($fieldsSchema['additionalProperties']);
                $this->assertSame($fieldsSchema['required'], array_keys($snapshot));

                $title = $snapshot['title'];
                $titleSchema = $fieldsSchema['properties']['title'];
                $this->assertIsString($title);
                $this->assertGreaterThanOrEqual($titleSchema['minLength'], mb_strlen($title));
                $this->assertLessThanOrEqual($titleSchema['maxLength'], mb_strlen($title));

                $this->assertTrue(
                    $snapshot['description'] === null || is_string($snapshot['description'])
                );
                $this->assertContains(
                    $snapshot['status'],
                    $fieldsSchema['properties']['status']['enum']
                );
                $this->assertContains(
                    $snapshot['priority'],
                    $fieldsSchema['properties']['priority']['enum']
                );

                $dueDate = $snapshot['due_date'];
                $this->assertTrue($dueDate === null || is_string($dueDate));
                if ($dueDate !== null) {
                    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dueDate);
                    $this->assertInstanceOf(DateTimeImmutable::class, $parsedDate);
                    $this->assertSame($dueDate, $parsedDate->format('Y-m-d'));
                }

                $dueTime = $snapshot['due_time'];
                $this->assertTrue($dueTime === null || is_string($dueTime));
                if ($dueTime !== null) {
                    $this->assertMatchesRegularExpression(
                        '/' . $fieldsSchema['properties']['due_time']['pattern'] . '/',
                        $dueTime
                    );
                }
            }

            $this->assertSame($todo['base'], $todo['data']);
        }
    }
}
