<?php

namespace App\Http\Requests;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TodoRequest extends FormRequest
{
    /**
     * Normalize user-entered text before validation and persistence.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (is_string($this->input('title'))) {
            $normalized['title'] = trim($this->input('title'));
        }

        if (is_string($this->input('description')) && trim($this->input('description')) === '') {
            $normalized['description'] = null;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_map(fn (TodoStatus $status) => $status->value, TodoStatus::cases()))],
            'priority' => ['required', Rule::in(array_map(fn (TodoPriority $priority) => $priority->value, TodoPriority::cases()))],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
