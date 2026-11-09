<?php

namespace App\Modules\Schedule\Services;

use App\Models\Floor;
use App\Models\Holiday;
use App\Models\Personnel;
use App\Models\Shift;
use App\Models\User;
use App\Models\WfhSchedule;
use App\Models\WorkSchedule;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleService
{
    public function assignSchedule(array $data, int $userId): WorkSchedule
    {
        return DB::transaction(function () use ($data, $userId): WorkSchedule {
            $personnel = Personnel::query()->where('status', 'Aktif')->findOrFail($data['personnel_id']);
            $shift = Shift::query()->where('is_active', true)->findOrFail($data['shift_id']);
            $floor = Floor::query()->where('is_active', true)->findOrFail($data['floor_id']);
            $date = CarbonImmutable::parse($data['date']);

            if ($date->isPast() && $date->toDateString() !== today()->toDateString()) {
                throw ValidationException::withMessages(['date' => 'Jadwal hanya dapat dibuat atau dikoreksi untuk hari ini dan tanggal mendatang.']);
            }

            $existingWfh = WfhSchedule::query()
                ->where('personnel_id', $personnel->id)
                ->whereDate('date', $date->toDateString())
                ->exists();
            if ($existingWfh) {
                throw ValidationException::withMessages(['personnel_id' => 'Personel sudah memiliki jadwal WFH pada tanggal ini.']);
            }

            [$schedule, $oldValues] = $this->saveSchedule(
                $personnel->id,
                $date->toDateString(),
                $shift->id,
                $floor->id,
                $data['notes'] ?? null,
            );
            AuditService::log(
                User::findOrFail($userId),
                'schedule',
                'assign',
                $schedule,
                oldValues: $oldValues,
                newValues: [
                    'personnel_id' => $personnel->id,
                    'shift_id' => $shift->id,
                    'floor_id' => $floor->id,
                    'date' => $date->toDateString(),
                ],
            );

            return $schedule;
        });
    }

    /**
     * Rotations are keyed by personnel ID; each value contains floor_id and a
     * shift_ids sequence, optionally with ISO weekday numbers in work_days.
     *
     * @param  array<int, array{floor_id: int, shift_ids: array<int, int>, work_days?: array<int, int>}>  $rotations
     * @return int Number of schedules created or corrected.
     */
    public function bulkGenerateMonthlySchedule(int $month, int $year, array $rotations): int
    {
        if (! checkdate($month, 1, $year)) {
            throw ValidationException::withMessages(['month' => 'Bulan dan tahun jadwal tidak valid.']);
        }

        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();
        if ($periodEnd->isPast() && $periodEnd->toDateString() !== today()->toDateString()) {
            throw ValidationException::withMessages(['month' => 'Jadwal otomatis hanya dapat dibuat untuk bulan berjalan atau mendatang.']);
        }

        return DB::transaction(function () use ($rotations, $periodStart, $periodEnd): int {
            $created = 0;
            $preparationStart = $periodStart->subDays(7);
            $holidays = Holiday::query()
                ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
                ->pluck('date')
                ->map(fn ($date) => CarbonImmutable::parse($date)->toDateString())
                ->all();

            foreach ($rotations as $personnelId => $rotation) {
                $personnel = Personnel::query()->where('status', 'Aktif')->findOrFail((int) $personnelId);
                $floor = Floor::query()->where('is_active', true)->findOrFail($rotation['floor_id'] ?? 0);
                $shiftIds = array_values($rotation['shift_ids'] ?? []);

                if ($shiftIds === []) {
                    throw ValidationException::withMessages([
                        'rotations' => 'Setiap rotasi harus memiliki minimal satu shift.',
                    ]);
                }

                $shifts = Shift::query()
                    ->where('is_active', true)
                    ->whereIn('id', $shiftIds)
                    ->get()
                    ->keyBy('id');
                if ($shifts->count() !== count(array_unique($shiftIds))) {
                    throw ValidationException::withMessages([
                        'rotations' => 'Rotasi mencakup shift yang tidak aktif atau tidak ditemukan.',
                    ]);
                }

                $workDays = $rotation['work_days'] ?? range(1, 7);
                foreach ($periodStart->daysUntil($periodEnd->addDay()) as $date) {
                    if (! in_array($date->dayOfWeekIso, $workDays, true) || in_array($date->toDateString(), $holidays, true)) {
                        continue;
                    }

                    if ($date->isPast() && $date->toDateString() !== today()->toDateString()) {
                        continue;
                    }

                    $hasWfh = WfhSchedule::query()
                        ->where('personnel_id', $personnel->id)
                        ->whereDate('date', $date->toDateString())
                        ->exists();
                    if ($hasWfh) {
                        continue;
                    }

                    $rotationOffset = $preparationStart->diffInDays($date);
                    $shiftId = $shiftIds[$rotationOffset % count($shiftIds)];
                    $this->saveSchedule($personnel->id, $date->toDateString(), $shiftId, $floor->id);
                    $created++;
                }
            }

            return $created;
        });
    }

    public function setWfh(int $personnelId, string $date, string $reason, int $userId): WfhSchedule
    {
        return DB::transaction(function () use ($personnelId, $date, $reason, $userId): WfhSchedule {
            $personnel = Personnel::query()->where('status', 'Aktif')->findOrFail($personnelId);
            $workDate = CarbonImmutable::parse($date);

            if ($workDate->isPast() && $workDate->toDateString() !== today()->toDateString()) {
                throw ValidationException::withMessages(['date' => 'WFH hanya dapat ditetapkan untuk hari ini dan tanggal mendatang.']);
            }

            $shiftSchedule = WorkSchedule::query()
                ->where('personnel_id', $personnel->id)
                ->whereDate('date', $workDate->toDateString())
                ->first();
            if ($shiftSchedule) {
                $oldValues = $shiftSchedule->only(['shift_id', 'floor_id', 'date']);
                $shiftSchedule->delete();
                AuditService::log(
                    User::findOrFail($userId),
                    'schedule',
                    'wfh_replace_shift',
                    $shiftSchedule,
                    $oldValues,
                    reason: $reason,
                );
            }

            $wfh = WfhSchedule::query()
                ->where('personnel_id', $personnel->id)
                ->whereDate('date', $workDate->toDateString())
                ->first();
            if ($wfh) {
                $wfh->update(['reason' => $reason, 'approved_by' => $userId]);
            } else {
                $wfh = WfhSchedule::create([
                    'personnel_id' => $personnel->id,
                    'date' => $workDate->toDateString(),
                    'reason' => $reason,
                    'approved_by' => $userId,
                ]);
            }

            AuditService::log(
                User::findOrFail($userId),
                'schedule',
                'set_wfh',
                $wfh,
                newValues: ['personnel_id' => $personnel->id, 'date' => $workDate->toDateString()],
                reason: $reason,
            );

            return $wfh;
        });
    }

    /**
     * @return array{0: WorkSchedule, 1: array<string, mixed>|null}
     */
    private function saveSchedule(int $personnelId, string $date, int $shiftId, int $floorId, ?string $notes = null): array
    {
        $schedule = WorkSchedule::query()
            ->where('personnel_id', $personnelId)
            ->whereDate('date', $date)
            ->first();
        $oldValues = $schedule?->only(['shift_id', 'floor_id', 'notes']);

        if ($schedule) {
            $schedule->update(['shift_id' => $shiftId, 'floor_id' => $floorId, 'notes' => $notes]);
        } else {
            $schedule = WorkSchedule::create([
                'personnel_id' => $personnelId,
                'date' => $date,
                'shift_id' => $shiftId,
                'floor_id' => $floorId,
                'notes' => $notes,
            ]);
        }

        return [$schedule, $oldValues];
    }
}
