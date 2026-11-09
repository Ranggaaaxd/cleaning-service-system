<?php

namespace App\Http\Requests;

use App\Models\InspectionDetail;
use App\Models\MonitoringInspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('is_active', true)],
            'inspection_type' => ['required', Rule::in(['rutin', 'mendadak'])],
            'inspection_date' => ['required', 'date'],
            'inspection_time' => ['required', 'date_format:H:i'],
            'overall_status' => ['required', Rule::in(MonitoringInspection::OVERALL_STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.checklist_item_id' => ['nullable', 'integer', 'exists:checklist_items,id'],
            'items.*.item_snapshot_text' => ['required', 'string', 'max:255'],
            'items.*.status' => ['required', Rule::in(InspectionDetail::STATUSES)],
            'items.*.photo' => ['nullable', 'image', 'max:5120'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
