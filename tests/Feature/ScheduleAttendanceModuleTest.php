<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceImport;
use App\Models\Floor;
use App\Models\Personnel;
use App\Models\Shift;
use App\Models\User;
use App\Models\WfhSchedule;
use App\Models\WorkSchedule;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Schedule\Services\ScheduleService;
use App\Exports\AttendanceTemplateExport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Tests\TestCase;

class ScheduleAttendanceModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_schedule_seeder_is_repeatable(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(3, Shift::count());
        $this->assertSame(5 * 7, WorkSchedule::count());
        $this->assertSame('06:00:00', Shift::where('code', 'P')->firstOrFail()->start_time);
    }

    public function test_schedule_assignment_corrects_same_personnel_date_and_audits(): void
    {
        $this->seed();
        $personnel = Personnel::firstOrFail();
        $user = User::where('role', 'admin')->firstOrFail();
        $date = today()->addDays(10)->toDateString();
        $floor = Floor::firstOrFail();
        $shifts = Shift::orderBy('id')->get();
        $service = app(ScheduleService::class);

        $service->assignSchedule([
            'personnel_id' => $personnel->id,
            'shift_id' => $shifts[0]->id,
            'floor_id' => $floor->id,
            'date' => $date,
        ], $user->id);
        $service->assignSchedule([
            'personnel_id' => $personnel->id,
            'shift_id' => $shifts[1]->id,
            'floor_id' => $floor->id,
            'date' => $date,
        ], $user->id);

        $this->assertSame(1, WorkSchedule::where('personnel_id', $personnel->id)->whereDate('date', $date)->count());
        $this->assertSame($shifts[1]->id, WorkSchedule::where('personnel_id', $personnel->id)->whereDate('date', $date)->value('shift_id'));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'module' => 'schedule', 'action' => 'assign']);
    }

    public function test_wfh_replaces_normal_shift_and_bulk_rotation_preserves_wfh(): void
    {
        $this->seed();
        $personnel = Personnel::firstOrFail();
        $user = User::where('role', 'admin')->firstOrFail();
        $floor = Floor::firstOrFail();
        $shiftIds = Shift::orderBy('id')->pluck('id')->all();
        $firstDate = CarbonImmutable::create(now()->year, now()->month, 1)->addMonth();

        app(ScheduleService::class)->setWfh($personnel->id, $firstDate->toDateString(), 'Pekerjaan administratif', $user->id);
        $count = app(ScheduleService::class)->bulkGenerateMonthlySchedule(
            $firstDate->month,
            $firstDate->year,
            [$personnel->id => ['floor_id' => $floor->id, 'shift_ids' => $shiftIds, 'work_days' => range(1, 7)]],
        );

        $this->assertGreaterThan(0, $count);
        $this->assertTrue(WfhSchedule::where('personnel_id', $personnel->id)->whereDate('date', $firstDate->toDateString())->exists());
        $this->assertFalse(WorkSchedule::where('personnel_id', $personnel->id)->whereDate('date', $firstDate->toDateString())->exists());
    }

    public function test_attendance_import_creates_version_errors_and_schedule_metrics(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $personnel = Personnel::firstOrFail();
        $shift = Shift::where('code', 'P')->firstOrFail();
        $date = today()->toDateString();
        $schedule = WorkSchedule::where('personnel_id', $personnel->id)->whereDate('date', $date)->first();
        if ($schedule) {
            $schedule->update(['shift_id' => $shift->id, 'floor_id' => $personnel->floor_id]);
        } else {
            WorkSchedule::create([
                'personnel_id' => $personnel->id,
                'date' => $date,
                'shift_id' => $shift->id,
                'floor_id' => $personnel->floor_id,
            ]);
        }

        $file = UploadedFile::fake()->createWithContent(
            'rekap.csv',
            "NIK,Tanggal,Jam Masuk,Jam Pulang,Status,Nama\n{$personnel->nik},{$date},06:10,14:50,Hadir,Petugas Valid\n9999999999999999,{$date},06:00,15:00,Hadir,NIK Tidak Ada\n",
        );
        $import = app(AttendanceService::class)->importFromExcel($file, (int) now()->month, (int) now()->year, $user->id);

        $this->assertSame(2, $import->total_rows);
        $this->assertSame(1, $import->total_matched);
        $this->assertSame(1, $import->total_unmatched);
        $this->assertSame(10, Attendance::where('import_id', $import->id)->value('late_minutes'));
        $this->assertDatabaseHas('attendance_import_errors', [
            'import_id' => $import->id,
            'raw_nik' => '9999999999999999',
            'row_number' => 3,
        ]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'module' => 'attendance', 'action' => 'import']);

        $secondFile = UploadedFile::fake()->createWithContent(
            'rekap-v2.csv',
            "NIK,Tanggal,Jam Masuk,Jam Pulang,Status\n{$personnel->nik},{$date},06:00,15:00,Hadir\n",
        );
        $secondImport = app(AttendanceService::class)->importFromExcel($secondFile, (int) now()->month, (int) now()->year, $user->id);

        $this->assertSame(2, $secondImport->version);
        $this->assertFalse($import->fresh()->is_active);
        $this->assertTrue($secondImport->fresh()->is_active);
        $this->assertSame(2, AttendanceImport::count());

        $this->actingAs($user)
            ->post(route('attendance.activate', $import))
            ->assertRedirect(route('attendance.index', ['import_id' => $import->id]));
        $this->assertTrue($import->fresh()->is_active);
        $this->assertFalse($secondImport->fresh()->is_active);
        $this->get(route('attendance.errors', $import))
            ->assertOk()
            ->assertSee('NIK tidak ditemukan');
    }

    public function test_invalid_import_header_rolls_back_version_activation(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $personnel = Personnel::firstOrFail();
        $valid = UploadedFile::fake()->createWithContent(
            'valid.csv',
            "NIK,Tanggal,Jam Masuk,Jam Pulang,Status\n{$personnel->nik},".today()->toDateString().",06:00,15:00,Hadir\n",
        );
        $import = app(AttendanceService::class)->importFromExcel($valid, (int) now()->month, (int) now()->year, $user->id);
        $invalid = UploadedFile::fake()->createWithContent('invalid.csv', "NIK,Kolom Salah\nfoo,bar\n");

        try {
            app(AttendanceService::class)->importFromExcel($invalid, (int) now()->month, (int) now()->year, $user->id);
            $this->fail('Invalid headers should stop the import.');
        } catch (ValidationException) {
            $this->assertTrue($import->fresh()->is_active);
            $this->assertSame(1, AttendanceImport::count());
        }
    }

    public function test_xlsx_file_is_read_and_excel_date_cells_are_imported(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $personnel = Personnel::firstOrFail();
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['NIK', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status', 'Nama'],
            [$personnel->nik, ExcelDate::PHPToExcel(now()->startOfDay()), '06:00', '15:00', 'Hadir', $personnel->name],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'attendance-xlsx-');
        (new Xlsx($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);
        $spreadsheet->disconnectWorksheets();

        $file = UploadedFile::fake()->createWithContent('attendance.xlsx', $contents);
        $import = app(AttendanceService::class)->importFromExcel($file, (int) now()->month, (int) now()->year, $user->id);

        $this->assertSame(1, $import->total_matched);
        $this->assertSame(0, $import->total_unmatched);
        $this->assertSame(1, Attendance::where('import_id', $import->id)->count());
    }

    public function test_attendance_template_is_a_formatted_xlsx_and_keeps_nik_as_text(): void
    {
        $bytes = \Maatwebsite\Excel\Facades\Excel::raw(new AttendanceTemplateExport, \Maatwebsite\Excel\Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'attendance-template-');
        file_put_contents($path, $bytes);
        $spreadsheet = IOFactory::load($path);
        unlink($path);

        $sheet = $spreadsheet->getActiveSheet();
        $this->assertSame([
            'NIK',
            'Nama Petugas',
            'Tanggal (YYYY-MM-DD)',
            'Jam Masuk (HH:MM)',
            'Jam Pulang (HH:MM)',
            'Status Kehadiran',
        ], $sheet->rangeToArray('A1:F1')[0]);
        $this->assertSame('1234567890123456', $sheet->getCell('A2')->getValue());
        $this->assertSame(NumberFormat::FORMAT_TEXT, $sheet->getStyle('A')->getNumberFormat()->getFormatCode());
        $this->assertSame('FF334155', $sheet->getStyle('A1')->getFill()->getStartColor()->getARGB());
        $this->assertTrue($sheet->getStyle('A1')->getFont()->getBold());
        $this->assertSame(3, $sheet->getHighestDataRow());
        $spreadsheet->disconnectWorksheets();

        $this->seed();
        $this->actingAs(User::where('role', 'supervisor')->firstOrFail())
            ->get(route('attendance.template'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=template-absensi.xlsx');
    }

    public function test_attendance_import_reads_template_heading_columns_and_long_nik_exactly(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $personnels = Personnel::orderBy('id')->take(2)->get();
        $personnels[0]->update(['nik' => '1234567890123456']);
        $personnels[1]->update(['nik' => '1234567890123457']);

        $bytes = \Maatwebsite\Excel\Facades\Excel::raw(new AttendanceTemplateExport, \Maatwebsite\Excel\Excel::XLSX);
        $file = UploadedFile::fake()->createWithContent('template.xlsx', $bytes);
        $import = app(AttendanceService::class)->importFromExcel($file, 11, 2026, $user->id);

        $this->assertSame(2, $import->total_rows);
        $this->assertSame(2, $import->total_matched);
        $this->assertSame(0, $import->total_unmatched);
        $firstAttendance = Attendance::where('import_id', $import->id)
            ->where('personnel_id', $personnels[0]->id)
            ->firstOrFail();
        $secondAttendance = Attendance::where('import_id', $import->id)
            ->where('personnel_id', $personnels[1]->id)
            ->firstOrFail();

        $this->assertSame('2026-11-09', $firstAttendance->date->toDateString());
        $this->assertSame('06:00:00', $firstAttendance->check_in);
        $this->assertSame('15:00:00', $firstAttendance->check_out);
        $this->assertSame('Hadir', $firstAttendance->status);
        $this->assertSame('2026-11-09', $secondAttendance->date->toDateString());
        $this->assertSame('13:00:00', $secondAttendance->check_in);
        $this->assertSame('22:00:00', $secondAttendance->check_out);
    }

    public function test_schedule_and_attendance_pages_require_authentication(): void
    {
        $this->get(route('schedule.index'))->assertRedirect(route('login'));
        $this->get(route('attendance.index'))->assertRedirect(route('login'));

        $this->seed();
        $this->actingAs(User::where('role', 'supervisor')->firstOrFail());
        $this->get(route('schedule.index'))->assertOk();
        $this->get(route('attendance.index'))->assertOk();
        $this->get(route('attendance.import-form'))->assertOk()->assertSee('Unduh template contoh');
        $this->get(route('attendance.template'))->assertOk();
    }

    public function test_wfh_does_not_allow_a_second_primary_shift_on_same_day(): void
    {
        $this->seed();
        $personnel = Personnel::firstOrFail();
        $user = User::where('role', 'admin')->firstOrFail();
        $date = today()->addDays(14)->toDateString();
        $scheduleService = app(ScheduleService::class);
        $scheduleService->setWfh($personnel->id, $date, 'WFH', $user->id);

        try {
            $scheduleService->assignSchedule([
                'personnel_id' => $personnel->id,
                'shift_id' => Shift::firstOrFail()->id,
                'floor_id' => Floor::firstOrFail()->id,
                'date' => $date,
            ], $user->id);
            $this->fail('A shift cannot be assigned on a WFH date.');
        } catch (ValidationException) {
            $this->assertSame(1, WfhSchedule::where('personnel_id', $personnel->id)->whereDate('date', $date)->count());
        }
    }
}
