<?php

namespace App\Modules\Monitoring\Services;

use App\Models\Area;
use App\Models\ChecklistItem;
use App\Models\InspectionDetail;
use App\Models\InspectionFinding;
use App\Models\MonitoringInspection;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MonitoringService
{
    /**
     * @param  array<int, array{checklist_item_id?: int|null, item_snapshot_text?: string, status: string, photo_path?: string|null, notes?: string|null}>  $itemsData
     */
    public function storeInspection(array $data, array $itemsData, int $inspectorId): MonitoringInspection
    {
        return DB::transaction(function () use ($data, $itemsData, $inspectorId): MonitoringInspection {
            $area = Area::query()->where('is_active', true)->findOrFail($data['area_id']);
            $inspector = User::findOrFail($inspectorId);
            $inspectionDate = $data['inspection_date'];
            $inspection = MonitoringInspection::create([
                'code' => $this->generateInspectionCode($inspectionDate),
                'area_id' => $area->id,
                'inspector_id' => $inspector->id,
                'inspection_type' => $data['inspection_type'],
                'inspection_date' => $inspectionDate,
                'inspection_time' => $data['inspection_time'],
                'overall_status' => $data['overall_status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsData as $itemData) {
                $item = isset($itemData['checklist_item_id'])
                    ? ChecklistItem::query()->where('is_active', true)->findOrFail($itemData['checklist_item_id'])
                    : null;
                if ($item && $item->template->area_type !== $area->type) {
                    throw ValidationException::withMessages([
                        'items' => 'Item checklist tidak sesuai dengan jenis area yang diperiksa.',
                    ]);
                }

                $snapshot = $item?->task_name ?? $itemData['item_snapshot_text'] ?? null;
                if (blank($snapshot)) {
                    throw ValidationException::withMessages(['items' => 'Teks checklist inspeksi wajib diisi.']);
                }

                $detail = InspectionDetail::create([
                    'inspection_id' => $inspection->id,
                    'checklist_item_id' => $item?->id,
                    'item_snapshot_text' => $snapshot,
                    'status' => $itemData['status'],
                    'photo_path' => $itemData['photo_path'] ?? null,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if (in_array($detail->status, ['Kotor', 'Rusak'], true)) {
                    InspectionFinding::create([
                        'inspection_id' => $inspection->id,
                        'area_id' => $area->id,
                        'description' => $detail->item_snapshot_text.($detail->notes ? ': '.$detail->notes : ' ('.$detail->status.')'),
                        'evidence_photo' => $detail->photo_path,
                        'status' => 'open',
                    ]);
                }
            }

            AuditService::log(
                $inspector,
                'monitoring',
                'create',
                $inspection,
                newValues: [
                    'code' => $inspection->code,
                    'area_id' => $area->id,
                    'inspection_type' => $inspection->inspection_type,
                    'overall_status' => $inspection->overall_status,
                    'checklist_items' => count($itemsData),
                ],
            );

            return $inspection;
        });
    }

    public function recheckFinding(int $findingId, string $status, ?string $notes, int $userId): InspectionFinding
    {
        if (! in_array($status, InspectionFinding::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Status temuan tidak valid.']);
        }

        return DB::transaction(function () use ($findingId, $status, $notes, $userId): InspectionFinding {
            $finding = InspectionFinding::query()->lockForUpdate()->findOrFail($findingId);
            $oldStatus = $finding->status;

            if ($oldStatus === 'resolved' && $status !== 'resolved') {
                throw ValidationException::withMessages(['status' => 'Temuan yang sudah selesai tidak dapat dibuka kembali.']);
            }

            $finding->update([
                'status' => $status,
                'recheck_by' => $userId,
                'recheck_at' => now(),
                'recheck_notes' => $notes,
            ]);

            AuditService::log(
                User::findOrFail($userId),
                'monitoring',
                'finding_recheck',
                $finding,
                ['status' => $oldStatus],
                ['status' => $status],
                $notes,
            );

            return $finding;
        });
    }

    private function generateInspectionCode(string $date): string
    {
        $prefix = 'INS-'.str_replace('-', '', $date).'-';

        do {
            $code = $prefix.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (MonitoringInspection::where('code', $code)->exists());

        return $code;
    }
}
