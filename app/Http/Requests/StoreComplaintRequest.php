<?php

namespace App\Http\Requests;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reporter_name' => ['required', 'string', 'max:255'],
            'reporter_contact' => ['nullable', 'string', 'max:255'],
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('is_active', true)],
            'category_id' => ['required', 'integer', 'exists:complaint_categories,id'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::in(Complaint::PRIORITIES)],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
