<?php

namespace App\Modules\Complaint\Services;

use App\Models\Area;
use App\Models\Complaint;
use App\Models\ComplaintStatusHistory;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ComplaintService
{
    public function createComplaint(array $data, ?int $userId = null): Complaint
    {
        return DB::transaction(function () use ($data, $userId): Complaint {
            Area::query()->where('is_active', true)->findOrFail($data['area_id']);
            $ticketNumber = $this->generateTicketNumber();
            $complaint = Complaint::create($data + [
                'ticket_number' => $ticketNumber,
                'status' => 'Baru',
            ]);

            $changedBy = $userId ?? User::query()->where('is_active', true)->orderBy('id')->value('id');
            if (! $changedBy) {
                throw ValidationException::withMessages(['complaint' => 'Tidak ada akun aktif untuk mencatat histori komplain.']);
            }

            ComplaintStatusHistory::create([
                'complaint_id' => $complaint->id,
                'old_status' => '',
                'new_status' => 'Baru',
                'notes' => 'Tiket komplain dibuat.',
                'changed_by' => $changedBy,
            ]);

            AuditService::log(
                $userId ? User::findOrFail($userId) : null,
                'complaint',
                'create',
                $complaint,
                newValues: [
                    'ticket_number' => $ticketNumber,
                    'area_id' => $complaint->area_id,
                    'category_id' => $complaint->category_id,
                    'priority' => $complaint->priority,
                    'status' => 'Baru',
                ],
            );

            return $complaint;
        });
    }

    public function updateStatus(Complaint $complaint, string $newStatus, ?string $notes, int $userId): Complaint
    {
        if (! in_array($newStatus, Complaint::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Status komplain tidak valid.']);
        }

        return DB::transaction(function () use ($complaint, $newStatus, $notes, $userId): Complaint {
            $complaint = Complaint::query()->lockForUpdate()->findOrFail($complaint->id);
            $oldStatus = $complaint->status;
            if ($oldStatus === $newStatus) {
                throw ValidationException::withMessages(['status' => 'Status baru harus berbeda dengan status saat ini.']);
            }

            $complaint->status = $newStatus;
            if ($newStatus === 'Diverifikasi' && $complaint->verified_at === null) {
                $complaint->verified_by = $userId;
                $complaint->verified_at = now();
            }
            $complaint->resolved_at = $newStatus === 'Selesai' ? now() : null;
            $complaint->save();

            ComplaintStatusHistory::create([
                'complaint_id' => $complaint->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'notes' => $notes,
                'changed_by' => $userId,
            ]);

            AuditService::log(
                User::findOrFail($userId),
                'complaint',
                'status_change',
                $complaint,
                ['status' => $oldStatus],
                ['status' => $newStatus],
                $notes,
            );

            return $complaint;
        });
    }

    private function generateTicketNumber(): string
    {
        $prefix = 'KMP-'.now()->format('Ym').'-';

        do {
            $ticketNumber = $prefix.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Complaint::where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
