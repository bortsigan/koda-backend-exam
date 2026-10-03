<?php

namespace App\Http\Requests;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clientName' => ['required', 'string', 'max:255'],
            'projectName' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(ProjectPriority::class)],
            'startDate' => ['required', 'date'],
            'dueDate' => ['required', 'date', 'after_or_equal:startDate'],
        ];
    }

    public function messages(): array
    {
        return [
            'clientName.required' => 'Client name is required.',
            'projectName.required' => 'Project name is required.',
            'status.enum' => 'Status must be one of: '.implode(', ', ProjectStatus::values()).'.',
            'priority.enum' => 'Priority must be one of: '.implode(', ', ProjectPriority::values()).'.',
            'dueDate.after_or_equal' => 'Due date cannot be earlier than start date.',
        ];
    }

    public function mapped(): array
    {
        return [
            'client_name' => $this->validated('clientName'),
            'project_name' => $this->validated('projectName'),
            'description' => $this->validated('description'),
            'status' => $this->validated('status'),
            'priority' => $this->validated('priority'),
            'start_date' => $this->validated('startDate'),
            'due_date' => $this->validated('dueDate'),
        ];
    }
}
