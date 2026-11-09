<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'task_type' => ['required', Rule::in(Task::TYPES)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(Task::STATUSES)],
            'personnel_ids' => ['required', 'array', 'min:1'],
            'personnel_ids.*' => ['required', 'integer', Rule::exists('personnels', 'id')->where('status', 'Aktif')->whereNull('deleted_at')],
        ];
    }
}
