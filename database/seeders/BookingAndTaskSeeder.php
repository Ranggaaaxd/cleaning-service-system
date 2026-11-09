<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Personnel;
use App\Models\RoomBooking;
use App\Models\User;
use App\Modules\Task\Services\TaskService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingAndTaskSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::where('email', 'admin@cleaningservice.local')->firstOrFail();
            $area = Area::query()
                ->where('is_active', true)
                ->where('name', 'Ruang Rapat')
                ->whereHas('floor', fn ($query) => $query->where('floor_number', 2))
                ->firstOrFail();
            $start = now()->addDays(3)->setTime(9, 0);
            $end = $start->copy()->addHours(3);
            $bookingCode = 'BKG-'.$start->format('Ym').'-0001';
            $booking = RoomBooking::firstOrCreate(
                ['booking_code' => $bookingCode],
                [
                    'event_name' => 'Rapat Koordinasi Operasional',
                    'organizer_name' => 'Tim Operasional',
                    'organizer_contact' => '021-5550101',
                    'area_id' => $area->id,
                    'start_time' => $start,
                    'end_time' => $end,
                    'participant_count' => 12,
                    'notes' => 'Contoh booking untuk pengujian modul.',
                    'status' => 'Diajukan',
                    'created_by' => $admin->id,
                ],
            );
            $start = $booking->start_time;
            $end = $booking->end_time;

            if ($booking->wasRecentlyCreated) {
                $booking->statusHistories()->create([
                    'old_status' => '',
                    'new_status' => 'Diajukan',
                    'notes' => 'Booking contoh dari seeder.',
                    'changed_by' => $admin->id,
                ]);
            }

            $personnels = Personnel::query()->where('status', 'Aktif')->orderBy('id')->limit(2)->get();
            if ($personnels->count() < 2) {
                return;
            }

            $taskDefinitions = [
                ['title' => 'Pembersihan rutin area lobby', 'task_type' => 'rutin', 'priority' => 'Sedang', 'status' => 'Belum Dikerjakan'],
                ['title' => 'Persiapan ruang rapat untuk acara', 'task_type' => 'peminjaman', 'priority' => 'Tinggi', 'status' => 'Dijadwalkan', 'source_type' => RoomBooking::class, 'source_id' => $booking->id],
            ];

            foreach ($taskDefinitions as $definition) {
                $exists = \App\Models\Task::query()
                    ->where('title', $definition['title'])
                    ->whereDate('start_date', $start->toDateString())
                    ->exists();
                if ($exists) {
                    continue;
                }

                app(TaskService::class)->createTask([
                    ...$definition,
                    'description' => 'Tugas contoh untuk demonstrasi penugasan petugas.',
                    'area_id' => $definition['task_type'] === 'rutin' ? $personnels[0]->floor->areas()->where('is_active', true)->value('id') : $booking->area_id,
                    'start_date' => $start->toDateString(),
                    'due_date' => $start->toDateString(),
                    'due_time' => '12:00',
                ], $personnels->pluck('id')->all(), $admin->id);
            }
        });
    }
}
