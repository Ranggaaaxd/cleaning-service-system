<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event_name' => ['required', 'string', 'max:255'],
            'organizer_name' => ['required', 'string', 'max:255'],
            'organizer_contact' => ['required', 'string', 'max:255'],
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('is_active', true)],
            'start_time' => ['required', 'date', 'after_or_equal:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'participant_count' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
