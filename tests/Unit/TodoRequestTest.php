<?php

namespace Tests\Unit;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Http\Requests\TodoRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TodoRequestTest extends TestCase
{
    public function test_supported_status_values_are_accepted(): void
    {
        $rules = (new TodoRequest())->rules();

        foreach (TodoStatus::cases() as $status) {
            $validator = Validator::make(
                ['status' => (string) $status->value],
                ['status' => $rules['status']],
            );

            $this->assertTrue($validator->passes(), "Status {$status->value} should be accepted.");
        }
    }

    public function test_unsupported_status_values_are_rejected(): void
    {
        $rules = (new TodoRequest())->rules();

        foreach (['3', '-1', 'done'] as $value) {
            $validator = Validator::make(
                ['status' => $value],
                ['status' => $rules['status']],
            );

            $this->assertFalse($validator->passes(), "Status {$value} should be rejected.");
        }
    }

    public function test_supported_priority_values_are_accepted(): void
    {
        $rules = (new TodoRequest())->rules();

        foreach (TodoPriority::cases() as $priority) {
            $validator = Validator::make(
                ['priority' => (string) $priority->value],
                ['priority' => $rules['priority']],
            );

            $this->assertTrue($validator->passes(), "Priority {$priority->value} should be accepted.");
        }
    }

    public function test_unsupported_priority_values_are_rejected(): void
    {
        $rules = (new TodoRequest())->rules();

        foreach (['3', '-1', 'urgent'] as $value) {
            $validator = Validator::make(
                ['priority' => $value],
                ['priority' => $rules['priority']],
            );

            $this->assertFalse($validator->passes(), "Priority {$value} should be rejected.");
        }
    }
}
