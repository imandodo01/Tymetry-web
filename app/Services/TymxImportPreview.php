<?php

namespace App\Services;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use JsonException;
use stdClass;

class TymxImportPreview
{
    public const MAX_FILE_BYTES = 5 * 1024 * 1024;

    public const MAX_RECORDS = 2000;

    private const FIELDS = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'due_time',
    ];

    /**
     * Parse and validate a TYMX v1 document, then compare it with the
     * authenticated user's active Todos. This method is read-only.
     *
     * @return array<string, mixed>
     */
    public function preview(string $contents, User $user): array
    {
        if (strlen($contents) > self::MAX_FILE_BYTES) {
            $this->invalid('The TYMX file exceeds the 5 MB size limit.');
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $this->invalid('The TYMX file must be valid UTF-8.');
        }

        try {
            $document = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->invalid('The TYMX file must contain valid JSON.');
        }

        if (! $document instanceof stdClass) {
            $this->invalid('The TYMX document must be a JSON object.');
        }

        $this->validateDocument($document);

        $publicIds = array_map(
            static fn (stdClass $record): string => strtolower($record->public_id),
            $document->todos
        );

        $todos = $user->todos()
            ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(public_id)'), $publicIds)
            ->get()
            ->keyBy(static fn (Todo $todo): string => strtolower($todo->public_id));

        $records = [];
        $summary = [
            'total' => count($document->todos),
            'updates' => 0,
            'unchanged' => 0,
            'conflicts' => 0,
            'unmatched' => 0,
        ];

        foreach ($document->todos as $record) {
            $todo = $todos->get(strtolower($record->public_id));

            if (! $todo) {
                $records[] = [
                    'public_id' => $record->public_id,
                    'title' => $record->data->title,
                    'action' => 'unmatched',
                    'changes' => [],
                ];
                $summary['unmatched']++;

                continue;
            }

            $current = $this->snapshot($todo);
            $changes = [];
            $hasEligibleChange = false;
            $hasConflict = false;

            foreach (self::FIELDS as $field) {
                $base = $record->base->{$field};
                $proposed = $record->data->{$field};
                $server = $current[$field];

                if ($proposed === $base) {
                    if ($server !== $base) {
                        $changes[] = [
                            'field' => $field,
                            'base' => $base,
                            'current' => $server,
                            'proposed' => $proposed,
                            'resolution' => 'server_only',
                        ];
                    }

                    continue;
                }

                if ($server === $base) {
                    $resolution = 'proposed';
                    $hasEligibleChange = true;
                } elseif ($server === $proposed) {
                    $resolution = 'already_current';
                } else {
                    $resolution = 'conflict';
                    $hasConflict = true;
                }

                $changes[] = [
                    'field' => $field,
                    'base' => $base,
                    'current' => $server,
                    'proposed' => $proposed,
                    'resolution' => $resolution,
                ];
            }

            $action = $hasConflict
                ? 'conflict'
                : ($hasEligibleChange ? 'update' : 'unchanged');

            $summary[$action === 'update' ? 'updates' : ($action === 'conflict' ? 'conflicts' : 'unchanged')]++;

            $records[] = [
                'public_id' => $record->public_id,
                'title' => $record->data->title,
                'action' => $action,
                'changes' => $changes,
            ];
        }

        return [
            'format' => 'tymx',
            'version' => 1,
            'summary' => $summary,
            'records' => $records,
        ];
    }

    private function validateDocument(stdClass $document): void
    {
        $this->assertExactKeys(
            $document,
            ['format', 'version', 'exported_at', 'source', 'selection', 'todos'],
            'The TYMX document does not match the v1 envelope.'
        );

        if ($document->format !== 'tymx' || $document->version !== 1) {
            $this->invalid('Only TYMX format version 1 is supported.');
        }

        if (! is_string($document->exported_at) || ! $this->isRfc3339DateTime($document->exported_at)) {
            $this->invalid('The exported_at value must be a valid RFC 3339 date-time.');
        }

        if (! $document->source instanceof stdClass) {
            $this->invalid('The source field must be an object.');
        }

        $this->assertExactKeys($document->source, ['application'], 'The source object is invalid.');

        if ($document->source->application !== 'Tymetry') {
            $this->invalid('The TYMX source application is not supported.');
        }

        if (! in_array($document->selection, ['selected', 'all_active'], true)) {
            $this->invalid('The TYMX selection value is invalid.');
        }

        if (! is_array($document->todos) || ! array_is_list($document->todos)) {
            $this->invalid('The todos field must be a JSON array.');
        }

        if (count($document->todos) > self::MAX_RECORDS) {
            $this->invalid('The TYMX file may contain no more than 2,000 Todo records.');
        }

        $seen = [];

        foreach ($document->todos as $index => $record) {
            if (! $record instanceof stdClass) {
                $this->invalid('Todo record ' . ($index + 1) . ' must be a JSON object.');
            }

            $this->assertExactKeys(
                $record,
                ['public_id', 'base', 'data'],
                'Todo record ' . ($index + 1) . ' does not match the v1 schema.'
            );

            if (! is_string($record->public_id) || ! preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD',
                $record->public_id
            )) {
                $this->invalid('Todo record ' . ($index + 1) . ' has an invalid public_id.');
            }

            $normalizedId = strtolower($record->public_id);

            if (isset($seen[$normalizedId])) {
                $this->invalid('The TYMX file contains duplicate public_id values.');
            }

            $seen[$normalizedId] = true;

            $this->validateSnapshot($record->base, $index + 1, 'base');
            $this->validateSnapshot($record->data, $index + 1, 'data');
        }
    }

    private function validateSnapshot(mixed $snapshot, int $recordNumber, string $name): void
    {
        if (! $snapshot instanceof stdClass) {
            $this->invalid('Todo record ' . $recordNumber . ' ' . $name . ' must be an object.');
        }

        $this->assertExactKeys(
            $snapshot,
            self::FIELDS,
            'Todo record ' . $recordNumber . ' ' . $name . ' has missing or unsupported fields.'
        );

        if (! is_string($snapshot->title)
            || mb_strlen($snapshot->title) < 1
            || mb_strlen($snapshot->title) > 255) {
            $this->invalid('Todo record ' . $recordNumber . ' has an invalid title.');
        }

        if ($snapshot->description !== null && ! is_string($snapshot->description)) {
            $this->invalid('Todo record ' . $recordNumber . ' has an invalid description.');
        }

        if (! in_array($snapshot->status, ['pending', 'in_progress', 'done'], true)) {
            $this->invalid('Todo record ' . $recordNumber . ' has an unsupported status.');
        }

        if (! in_array($snapshot->priority, ['low', 'medium', 'high'], true)) {
            $this->invalid('Todo record ' . $recordNumber . ' has an unsupported priority.');
        }

        if ($snapshot->due_date !== null && ! $this->isDate($snapshot->due_date)) {
            $this->invalid('Todo record ' . $recordNumber . ' has an invalid due_date.');
        }

        if ($snapshot->due_time !== null && (
            ! is_string($snapshot->due_time)
            || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $snapshot->due_time)
        )) {
            $this->invalid('Todo record ' . $recordNumber . ' has an invalid due_time.');
        }
    }

    private function assertExactKeys(stdClass $object, array $expected, string $message): void
    {
        $actual = array_keys(get_object_vars($object));
        sort($actual);
        sort($expected);

        if ($actual !== $expected) {
            $this->invalid($message);
        }
    }

    private function isDate(mixed $value): bool
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function isRfc3339DateTime(string $value): bool
    {
        if (! preg_match(
            '/^(\\d{4})-(\\d{2})-(\\d{2})T(\\d{2}):(\\d{2}):(\\d{2})(?:\\.\\d+)?(?<offset>Z|[+-](?<offset_hour>\\d{2}):(?<offset_minute>\\d{2}))$/D',
            $value,
            $matches,
            PREG_UNMATCHED_AS_NULL
        )) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])
            && (int) $matches[4] <= 23
            && (int) $matches[5] <= 59
            && (int) $matches[6] <= 60
            && ($matches['offset_hour'] === null
                || ((int) $matches['offset_hour'] <= 23 && (int) $matches['offset_minute'] <= 59));
    }

    /**
     * @return array<string, string|null>
     */
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
            'due_date' => $todo->due_date
                ? \Carbon\Carbon::parse($todo->due_date)->toDateString()
                : null,
            'due_time' => $todo->due_time ? substr((string) $todo->due_time, 0, 5) : null,
        ];
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
