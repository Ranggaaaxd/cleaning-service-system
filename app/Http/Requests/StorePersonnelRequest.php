<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:255', 'unique:personnels,nik'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'floor_id' => ['nullable', 'integer', Rule::exists('floors', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(\App\Models\Personnel::STATUSES)],
            'join_date' => ['required', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
