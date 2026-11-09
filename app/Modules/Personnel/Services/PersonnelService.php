<?php

namespace App\Modules\Personnel\Services;

use App\Models\Floor;
use App\Models\Personnel;
use App\Models\PersonnelChangeRequest;
use App\Models\PersonnelMutationHistory;
use App\Models\PersonnelStatusHistory;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PersonnelService
{
    public function createPersonnel(array $data, int $userId): Personnel
    {
        return DB::transaction(function () use ($data, $userId): Personnel {
            $user = User::findOrFail($userId);
            if (! empty($data['floor_id'])) {
                Floor::query()->where('is_active', true)->findOrFail($data['floor_id']);
            }

            $personnel = Personnel::create($data);

            PersonnelStatusHistory::create([
                'personnel_id' => $personnel->id,
                'old_status' => '',
                'new_status' => $personnel->status,
                'reason' => 'Pencatatan status awal',
                'changed_by' => $userId,
            ]);

            if ($personnel->floor_id !== null) {
                PersonnelMutationHistory::create([
                    'personnel_id' => $personnel->id,
                    'from_floor_id' => null,
                    'to_floor_id' => $personnel->floor_id,
                    'reason' => 'Penempatan awal',
                    'mutated_by' => $userId,
                    'effective_date' => $personnel->join_date,
                ]);
            }

            AuditService::log($user, 'personnel', 'create', $personnel, newValues: $personnel->only([
                'nik', 'name', 'gender', 'phone', 'address', 'position_id', 'floor_id', 'status', 'join_date',
            ]));

            return $personnel;
        });
    }

    public function updatePersonnel(Personnel $personel, array $data, User $currentUser): Personnel
    {
        return DB::transaction(function () use ($personel, $data, $currentUser): Personnel {
            $reason = $data['change_reason'] ?? null;
            unset($data['change_reason']);

            if ($currentUser->isSupervisor()) {
                foreach (['nik', 'name'] as $field) {
                    if (isset($data[$field]) && $data[$field] !== $personel->{$field}) {
                        if (blank($reason)) {
                            throw ValidationException::withMessages([
                                'change_reason' => 'Alasan wajib diisi untuk permohonan perubahan NIK atau nama.',
                            ]);
                        }

                        $pending = PersonnelChangeRequest::query()
                            ->where('personnel_id', $personel->id)
                            ->where('field_name', $field)
                            ->where('status', 'pending')
                            ->exists();

                        if ($pending) {
                            throw ValidationException::withMessages([
                                $field => 'Masih ada permohonan perubahan '.$field.' yang menunggu persetujuan.',
                            ]);
                        }

                        $changeRequest = PersonnelChangeRequest::create([
                            'personnel_id' => $personel->id,
                            'requested_by' => $currentUser->id,
                            'field_name' => $field,
                            'old_value' => $personel->{$field},
                            'new_value' => $data[$field],
                            'reason' => $reason,
                        ]);

                        AuditService::log(
                            $currentUser,
                            'personnel',
                            'change_request',
                            $changeRequest,
                            [$field => $personel->{$field}],
                            [$field => $data[$field]],
                            $reason,
                        );
                    }
                }

                unset($data['nik'], $data['name']);
            }

            $oldStatus = $personel->status;
            $oldFloorId = $personel->floor_id;
            if (array_key_exists('floor_id', $data) && $data['floor_id'] !== null && $data['floor_id'] != $oldFloorId) {
                Floor::query()->where('is_active', true)->findOrFail($data['floor_id']);
            }

            if (array_key_exists('floor_id', $data) && $oldFloorId !== null && $data['floor_id'] === null) {
                throw ValidationException::withMessages([
                    'floor_id' => 'Penempatan personel yang sudah ditetapkan tidak dapat dikosongkan.',
                ]);
            }

            $oldValues = $personel->only(array_keys($data));
            $personel->fill($data);
            $personel->save();

            if ($oldStatus !== $personel->status) {
                PersonnelStatusHistory::create([
                    'personnel_id' => $personel->id,
                    'old_status' => $oldStatus,
                    'new_status' => $personel->status,
                    'reason' => $reason,
                    'changed_by' => $currentUser->id,
                ]);
            }

            if ($oldFloorId !== $personel->floor_id && $personel->floor_id !== null) {
                PersonnelMutationHistory::create([
                    'personnel_id' => $personel->id,
                    'from_floor_id' => $oldFloorId,
                    'to_floor_id' => $personel->floor_id,
                    'reason' => $reason,
                    'mutated_by' => $currentUser->id,
                    'effective_date' => now()->toDateString(),
                ]);
            }

            AuditService::log(
                $currentUser,
                'personnel',
                'update',
                $personel,
                $oldValues,
                $personel->only(array_keys($data)),
                $reason,
            );

            return $personel;
        });
    }

    public function approveChangeRequest(int $requestId, User $admin, ?string $notes = null): PersonnelChangeRequest
    {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($requestId, $admin, $notes): PersonnelChangeRequest {
            $changeRequest = PersonnelChangeRequest::query()
                ->with('personnel')
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($changeRequest->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => 'Permohonan ini sudah ditinjau.',
                ]);
            }

            $personnel = $changeRequest->personnel;
            $oldValue = $personnel->{$changeRequest->field_name};
            if ($oldValue !== $changeRequest->old_value) {
                throw ValidationException::withMessages([
                    'request' => 'Data identitas berubah sejak permohonan dibuat. Permohonan ini tidak dapat disetujui.',
                ]);
            }

            if ($changeRequest->field_name === 'nik' && Personnel::query()
                ->where('nik', $changeRequest->new_value)
                ->whereKeyNot($personnel->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'request' => 'NIK tersebut sudah digunakan oleh personel lain.',
                ]);
            }

            $personnel->{$changeRequest->field_name} = $changeRequest->new_value;
            $personnel->save();

            $changeRequest->update([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            AuditService::log(
                $admin,
                'personnel',
                'approve_change_request',
                $changeRequest,
                [$changeRequest->field_name => $oldValue],
                [$changeRequest->field_name => $changeRequest->new_value],
                $notes,
            );

            return $changeRequest;
        });
    }

    public function rejectChangeRequest(int $requestId, User $admin, string $notes): PersonnelChangeRequest
    {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($requestId, $admin, $notes): PersonnelChangeRequest {
            $changeRequest = PersonnelChangeRequest::query()->lockForUpdate()->findOrFail($requestId);

            if ($changeRequest->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => 'Permohonan ini sudah ditinjau.',
                ]);
            }

            $changeRequest->update([
                'status' => 'rejected',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            AuditService::log(
                $admin,
                'personnel',
                'reject_change_request',
                $changeRequest,
                reason: $notes,
            );

            return $changeRequest;
        });
    }

    public function changeStatus(Personnel $personel, string $newStatus, string $reason, int $userId): Personnel
    {
        if (! in_array($newStatus, Personnel::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Status personel tidak valid.']);
        }

        return DB::transaction(function () use ($personel, $newStatus, $reason, $userId): Personnel {
            $oldStatus = $personel->status;

            if ($oldStatus === $newStatus) {
                throw ValidationException::withMessages(['status' => 'Pilih status yang berbeda dari status saat ini.']);
            }

            $personel->update(['status' => $newStatus]);
            PersonnelStatusHistory::create([
                'personnel_id' => $personel->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'changed_by' => $userId,
            ]);

            AuditService::log(
                User::findOrFail($userId),
                'personnel',
                'status_change',
                $personel,
                ['status' => $oldStatus],
                ['status' => $newStatus],
                $reason,
            );

            return $personel;
        });
    }

    public function mutatePlacement(Personnel $personel, int $newFloorId, string $reason, int $userId): Personnel
    {
        $newFloor = Floor::query()->where('is_active', true)->findOrFail($newFloorId);

        return DB::transaction(function () use ($personel, $newFloor, $reason, $userId): Personnel {
            $oldFloorId = $personel->floor_id;

            if ($oldFloorId === $newFloor->id) {
                throw ValidationException::withMessages(['floor_id' => 'Pilih lantai yang berbeda dari penempatan saat ini.']);
            }

            $personel->update(['floor_id' => $newFloor->id]);
            PersonnelMutationHistory::create([
                'personnel_id' => $personel->id,
                'from_floor_id' => $oldFloorId,
                'to_floor_id' => $newFloor->id,
                'reason' => $reason,
                'mutated_by' => $userId,
                'effective_date' => now()->toDateString(),
            ]);

            AuditService::log(
                User::findOrFail($userId),
                'personnel',
                'placement_change',
                $personel,
                ['floor_id' => $oldFloorId],
                ['floor_id' => $newFloor->id],
                $reason,
            );

            return $personel;
        });
    }
}
