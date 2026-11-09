<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\BookingStatusHistory;
use App\Models\Personnel;
use App\Models\RoomBooking;
use App\Models\Task;
use App\Models\User;
use App\Modules\Booking\Services\BookingService;
use App\Modules\Task\Services\TaskService;
use Database\Seeders\BookingAndTaskSeeder;
use Database\Seeders\LocationAndPersonnelSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingTaskModuleTest extends TestCase
{
    use RefreshDatabase;

    private function seedLocationsAndUsers(): void
    {
        $this->seed([UserSeeder::class, LocationAndPersonnelSeeder::class]);
    }

    public function test_booking_creation_records_status_history_and_non_blocking_conflicts(): void
    {
        $this->seedLocationsAndUsers();
        $user = User::where('role', 'admin')->firstOrFail();
        $area = Area::where('name', 'Ruang Rapat')->firstOrFail();
        $start = now()->addDays(2)->setTime(10, 0);
        $end = $start->copy()->addHours(2);
        $service = app(BookingService::class);
        $booking = $service->createBooking([
            'event_name' => 'Rapat Pertama',
            'organizer_name' => 'Tim A',
            'organizer_contact' => '0811111111',
            'area_id' => $area->id,
            'start_time' => $start->toDateTimeString(),
            'end_time' => $end->toDateTimeString(),
        ], $user->id);

        $conflicts = $service->checkScheduleConflict(
            $area->id,
            $start->copy()->addMinutes(30)->toDateTimeString(),
            $end->copy()->addMinutes(30)->toDateTimeString(),
        );

        $this->assertMatchesRegularExpression('/^BKG-\d{6}-\d{4}$/', $booking->booking_code);
        $this->assertSame('Diajukan', $booking->status);
        $this->assertSame(1, $booking->statusHistories()->count());
        $this->assertTrue($conflicts->contains('id', $booking->id));
        $this->assertDatabaseHas('audit_logs', ['module' => 'booking', 'action' => 'create']);
    }

    public function test_booking_routes_render_cascading_form_and_allow_conflicting_booking(): void
    {
        $this->seedLocationsAndUsers();
        $user = User::where('role', 'supervisor')->firstOrFail();
        $area = Area::where('name', 'Ruang Rapat')->firstOrFail();
        $start = now()->addDays(2)->setTime(10, 0);
        $end = $start->copy()->addHours(2);
        app(BookingService::class)->createBooking([
            'event_name' => 'Acara Bentrok',
            'organizer_name' => 'Tim A',
            'organizer_contact' => '0811111111',
            'area_id' => $area->id,
            'start_time' => $start->toDateTimeString(),
            'end_time' => $end->toDateTimeString(),
        ], $user->id);
        $this->actingAs($user);

        $this->get(route('booking.create'))->assertOk()
            ->assertViewHas('buildings')
            ->assertSee('Pilih Gedung')
            ->assertSee('Pilih Lantai')
            ->assertSee('Pilih Area / Ruangan')
            ->assertSee('conflict');
        $this->get(route('booking.conflicts', [
            'area_id' => $area->id,
            'start_time' => $start->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            'end_time' => $end->format('Y-m-d H:i:s'),
        ]))->assertOk()->assertJsonCount(1);

        $response = $this->post(route('booking.store'), [
            'event_name' => 'Acara Kedua',
            'organizer_name' => 'Tim B',
            'organizer_contact' => '0822222222',
            'area_id' => $area->id,
            'start_time' => $start->copy()->addMinutes(30)->format('Y-m-d\TH:i'),
            'end_time' => $end->copy()->addMinutes(30)->format('Y-m-d\TH:i'),
        ]);
        $booking = RoomBooking::where('event_name', 'Acara Kedua')->firstOrFail();
        $response->assertRedirect(route('booking.show', $booking))->assertSessionHas('warning');
        $this->get(route('booking.index'))->assertOk()->assertSee($booking->booking_code);
        $this->get(route('booking.show', $booking))->assertOk()->assertSee('Generate Tugas Operasional');
    }

    public function test_booking_can_generate_linked_operational_tasks(): void
    {
        $this->seedLocationsAndUsers();
        $user = User::where('role', 'admin')->firstOrFail();
        $area = Area::where('name', 'Ruang Rapat')->firstOrFail();
        $booking = app(BookingService::class)->createBooking([
            'event_name' => 'Town Hall',
            'organizer_name' => 'HR',
            'organizer_contact' => '0800000000',
            'area_id' => $area->id,
            'start_time' => now()->addDays(2)->setTime(9, 0)->toDateTimeString(),
            'end_time' => now()->addDays(2)->setTime(12, 0)->toDateTimeString(),
        ], $user->id);

        $tasks = app(BookingService::class)->generateTasksForBooking($booking, [
            ['title' => 'Siapkan ruangan'],
            ['title' => 'Bersihkan setelah acara'],
        ], $user->id);

        $this->assertCount(2, $tasks);
        $this->assertSame(2, $booking->tasks()->count());
        $this->assertSame(RoomBooking::class, $tasks[0]->source_type);
        $this->assertSame('peminjaman', $tasks[0]->task_type);
        $this->assertSame(1, $tasks[0]->statusHistories()->count());
    }

    public function test_task_creation_status_inspection_and_cancellation_preserve_history(): void
    {
        Storage::fake('public');
        $this->seedLocationsAndUsers();
        $user = User::where('role', 'supervisor')->firstOrFail();
        $area = Area::where('is_active', true)->firstOrFail();
        $personnel = Personnel::where('status', 'Aktif')->limit(2)->get();
        $task = app(TaskService::class)->createTask([
            'title' => 'Bersihkan area',
            'task_type' => 'rutin',
            'priority' => 'Sedang',
            'area_id' => $area->id,
            'start_date' => today()->toDateString(),
            'due_date' => today()->toDateString(),
            'due_time' => '18:00',
        ], $personnel->pluck('id')->all(), $user->id);
        $this->assertSame(2, $task->assignees()->count());
        $this->assertMatchesRegularExpression('/^TSK-\d{8}-\d{4}$/', $task->task_code);

        $service = app(TaskService::class);
        $service->updateStatus($task, 'Proses', 'Mulai bekerja', $user->id);
        $service->updateStatus($task, 'Pemeriksaan', 'Pekerjaan diajukan untuk diperiksa', $user->id);
        $service->submitInspection($task, false, 'Masih ada bagian kotor', null, $user->id);
        $this->assertSame('Proses', $task->fresh()->status);
        $service->updateStatus($task, 'Pemeriksaan', 'Pekerjaan diperbaiki', $user->id);
        $inspection = $service->submitInspection($task, true, 'Sudah sesuai', 'task-inspections/evidence.jpg', $user->id);
        $this->assertTrue($inspection->is_approved);
        $this->assertSame('Selesai', $task->fresh()->status);
        $this->assertSame(6, $task->statusHistories()->count());

        $cancelledTask = $service->createTask([
            'title' => 'Tugas dibatalkan',
            'task_type' => 'komplain',
            'priority' => 'Rendah',
            'area_id' => $area->id,
            'start_date' => today()->toDateString(),
        ], [$personnel[0]->id], $user->id);
        $historyCount = $cancelledTask->statusHistories()->count();
        $service->cancelTask($cancelledTask, 'Tidak diperlukan', $user->id);
        $this->assertTrue($cancelledTask->fresh()->is_cancelled);
        $this->assertSame($historyCount, $cancelledTask->statusHistories()->count());
        $this->assertSame('Tidak diperlukan', $cancelledTask->fresh()->cancellation_reason);
    }

    public function test_task_form_routes_create_multiple_assignees_and_auth_protection(): void
    {
        $this->seedLocationsAndUsers();
        $this->get(route('booking.index'))->assertRedirect(route('login'));
        $this->get(route('task.index'))->assertRedirect(route('login'));
        $user = User::where('role', 'admin')->firstOrFail();
        $area = Area::where('is_active', true)->firstOrFail();
        $personnels = Personnel::where('status', 'Aktif')->limit(2)->get();
        $this->actingAs($user);

        $this->get(route('task.create'))->assertOk()
            ->assertSee('Pilih Gedung')
            ->assertSee('Petugas pelaksana');
        $response = $this->post(route('task.store'), [
            'title' => 'Tugas dari form',
            'task_type' => 'rutin',
            'priority' => 'Tinggi',
            'area_id' => $area->id,
            'start_date' => today()->toDateString(),
            'status' => 'Belum Dikerjakan',
            'personnel_ids' => $personnels->pluck('id')->all(),
        ]);
        $task = Task::where('title', 'Tugas dari form')->firstOrFail();
        $response->assertRedirect(route('task.show', $task));
        $this->assertSame(2, $task->assignees()->count());
        $this->get(route('task.index'))->assertOk()->assertSee($task->task_code);
        $this->get(route('task.show', $task))->assertOk()->assertSee('Timeline status')->assertSee('Konfirmasi pembatalan');
        $this->post(route('task.cancel', $task), ['reason' => 'Jadwal berubah'])->assertRedirect();
        $this->assertTrue($task->fresh()->is_cancelled);
    }

    public function test_booking_task_seeder_is_repeatable(): void
    {
        $this->seedLocationsAndUsers();
        $this->seed(BookingAndTaskSeeder::class);
        $this->seed(BookingAndTaskSeeder::class);

        $this->assertSame(1, RoomBooking::count());
        $this->assertSame(2, Task::count());
        $this->assertSame(2, Task::firstOrFail()->assignees()->count());
        $this->assertSame(1, BookingStatusHistory::count());
    }
}
