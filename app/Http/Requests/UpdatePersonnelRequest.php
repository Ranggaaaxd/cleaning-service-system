<?php

namespace App\Http\Requests;

use App\Models\Personnel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Personnel $personnel */
        $personnel = $this->route('personnel');

        return [
            'nik' => ['required', 'string', 'max:255', Rule::unique('personnels', 'nik')->ignore($personnel->id)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'floor_id' => ['nullable', 'integer', Rule::exists('floors', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(Personnel::STATUSES)],
            'join_date' => ['required', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'change_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->user()?->isSupervisor()) {
                    return;
                }

                /** @var Personnel $personnel */
                $personnel = $this->route('personnel');
                $changedIdentity = $this->input('nik') !== $personnel->nik
                    || $this->input('name') !== $personnel->name;

                if ($changedIdentity && blank($this->input('change_reason'))) {
                    $validator->errors()->add('change_reason', 'Alasan wajib diisi untuk perubahan NIK atau nama.');
                }
            },
        ];
    }
}
