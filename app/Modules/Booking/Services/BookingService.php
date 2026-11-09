<?php

namespace App\Modules\Booking\Services;

use App\Models\Area;
use App\Models\BookingStatusHistory;
use App\Models\RoomBooking;
use App\Models\User;
use App\Modules\Task\Services\TaskService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function checkScheduleConflict(int $areaId, string $startTime, string $endTime, ?int $excludeBookingId = null): Collection
    {
        return RoomBooking::query()
            ->with(['area.floor.building'])
            ->where('area_id', $areaId)
            ->where('status', '!=', 'Dibatalkan')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($excludeBookingId, fn ($query) => $query->where('id', '!=', $excludeBookingId))
            ->orderBy('start_time')
            ->get();
    }

    public function createBooking(array $data, int $userId): RoomBooking
    {
        return DB::transaction(function () use ($data, $userId): RoomBooking {
            Area::query()->where('is_active', true)->findOrFail($data['area_id']);
            $user = User::findOrFail($userId);
            $booking = RoomBooking::create([
                ...$data,
                'booking_code' => $this->generateBookingCode($data['start_time']),
                'status' => 'Diajukan',
                'created_by' => $userId,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'old_status' => '',
                'new_status' => 'Diajukan',
                'notes' => 'Booking diajukan.',
                'changed_by' => $userId,
            ]);

            AuditService::log($user, 'booking', 'create', $booking, newValues: [
                'booking_code' => $booking->booking_code,
                'area_id' => $booking->area_id,
                'start_time' => $booking->start_time->toDateTimeString(),
                'end_time' => $booking->end_time->toDateTimeString(),
            ]);

            return $booking;
        });
    }

    public function generateTasksForBooking(RoomBooking $booking, array $taskTypes, int $userId): array
    {
        return DB::transaction(function () use ($booking, $taskTypes, $userId): array {
            $booking->loadMissing('area');
            $tasks = [];

            foreach ($taskTypes as $taskType) {
                $title = is_array($taskType) ? $taskType['title'] : (string) $taskType;
                $task = app(TaskService::class)->createTask([
                    'title' => $title,
                    'description' => 'Tugas pendukung untuk '.$booking->event_name.' ('.$booking->booking_code.').',
                    'task_type' => 'peminjaman',
                    'priority' => 'Sedang',
                    'area_id' => $booking->area_id,
                    'start_date' => $booking->start_time->toDateString(),
                    'due_date' => $booking->end_time->toDateString(),
                    'due_time' => $booking->end_time->format('H:i:s'),
                    'status' => 'Dijadwalkan',
                    'source_type' => RoomBooking::class,
                    'source_id' => $booking->id,
                ], [], $userId);
                $tasks[] = $task;
            }

            AuditService::log(
                User::findOrFail($userId),
                'booking',
                'generate_tasks',
                $booking,
                newValues: ['task_ids' => array_map(fn ($task) => $task->id, $tasks)],
            );

            return $tasks;
        });
    }

    private function generateBookingCode(string $startTime): string
    {
        $prefix = 'BKG-'.date('Ym', strtotime($startTime)).'-';

        do {
            $code = $prefix.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (RoomBooking::where('booking_code', $code)->exists());

        return $code;
    }
}
