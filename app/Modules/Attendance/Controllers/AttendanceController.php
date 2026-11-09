<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceImport;
use App\Modules\Attendance\Services\AttendanceService;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:'.implode(',', Attendance::STATUSES)],
            'import_id' => ['nullable', 'integer', 'exists:attendance_imports,id'],
        ]);

        $activeImport = isset($validated['import_id'])
            ? AttendanceImport::findOrFail($validated['import_id'])
            : AttendanceImport::query()->where('is_active', true)->orderByDesc('period_year')->orderByDesc('period_month')->orderByDesc('version')->first();

        $attendances = $activeImport
            ? Attendance::query()
                ->with('personnel.position')
                ->where('import_id', $activeImport->id)
                ->when(isset($validated['date']), fn ($query) => $query->whereDate('date', $validated['date']))
                ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
                ->orderBy('date')
                ->orderBy('personnel_id')
                ->paginate(25)
                ->withQueryString()
            : Attendance::query()->whereRaw('1 = 0')->paginate(25);

        $imports = AttendanceImport::query()
            ->with('importedBy')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('version')
            ->get();

        return view('modules.attendance.index', compact('activeImport', 'attendances', 'imports'));
    }

    public function importForm(): View
    {
        $imports = AttendanceImport::query()
            ->with('importedBy')
            ->withCount('errors')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('version')
            ->paginate(15);

        return view('modules.attendance.import', compact('imports'));
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
        ]);

        $import = $this->attendanceService->importFromExcel(
            $validated['file'],
            (int) $validated['month'],
            (int) $validated['year'],
            (int) $request->user()->id,
        );

        return redirect()->route('attendance.index', ['import_id' => $import->id])
            ->with('success', "Impor versi {$import->version} selesai: {$import->total_matched} cocok, {$import->total_unmatched} bermasalah.");
    }

    public function history(): View
    {
        $imports = AttendanceImport::query()
            ->with('importedBy')
            ->withCount('errors')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('version')
            ->paginate(20);

        return view('modules.attendance.history', compact('imports'));
    }

    public function errors(AttendanceImport $attendanceImport): View
    {
        return view('modules.attendance.errors', [
            'import' => $attendanceImport->load('errors'),
        ]);
    }

    public function activate(Request $request, AttendanceImport $attendanceImport): RedirectResponse
    {
        DB::transaction(function () use ($request, $attendanceImport): void {
            $periodImports = AttendanceImport::query()
                ->where('period_month', $attendanceImport->period_month)
                ->where('period_year', $attendanceImport->period_year)
                ->lockForUpdate()
                ->get();

            AttendanceImport::query()
                ->whereIn('id', $periodImports->pluck('id'))
                ->update(['is_active' => false]);

            $attendanceImport->update(['is_active' => true]);
            AuditService::log(
                $request->user(),
                'attendance',
                'activate_version',
                $attendanceImport,
                ['is_active' => false],
                ['is_active' => true],
            );
        });

        return redirect()->route('attendance.index', ['import_id' => $attendanceImport->id])
            ->with('success', 'Versi rekap absensi berhasil diaktifkan.');
    }

    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceTemplateExport,
            'template-absensi.xlsx',
        );
    }
}
