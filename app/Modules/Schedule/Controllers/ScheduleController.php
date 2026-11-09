<?php

namespace App\Modules\Schedule\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use App\Models\Holiday;
use App\Models\Personnel;
use App\Models\Shift;
use App\Models\WfhSchedule;
use App\Models\WorkSchedule;
use App\Modules\Schedule\Services\ScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function __construct(private readonly ScheduleService $scheduleService) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2020,2100'],
            'floor_id' => ['nullable', 'integer', 'exists:floors,id'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'date' => ['nullable', 'date'],
        ]);
        $month = (int) ($validated['month'] ?? now()->month);
        $year = (int) ($validated['year'] ?? now()->year);
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();
        $filterDate = $validated['date'] ?? null;

        $schedules = WorkSchedule::query()
            ->with(['personnel.position', 'shift', 'floor.building'])
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->when(isset($validated['floor_id']), fn ($query) => $query->where('floor_id', $validated['floor_id']))
            ->when(isset($validated['shift_id']), fn ($query) => $query->where('shift_id', $validated['shift_id']))
            ->when($filterDate, fn ($query) => $query->whereDate('date', $filterDate))
            ->orderBy('date')
            ->get();

        $wfhSchedules = WfhSchedule::query()
            ->with(['personnel.position', 'approvedBy'])
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->when($filterDate, fn ($query) => $query->whereDate('date', $filterDate))
            ->get();

        return view('modules.schedule.index', [
            'schedules' => $schedules,
            'wfhSchedules' => $wfhSchedules,
            'schedulesByDate' => $schedules->groupBy(fn ($schedule) => $schedule->date->toDateString()),
            'wfhByDate' => $wfhSchedules->groupBy(fn ($schedule) => $schedule->date->toDateString()),
            'holidays' => Holiday::whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])->get()->keyBy(fn ($holiday) => $holiday->date->toDateString()),
            'month' => $month,
            'year' => $year,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'floors' => Floor::with('building')->where('is_active', true)->orderBy('floor_number')->get(),
            'shifts' => Shift::where('is_active', true)->orderBy('start_time')->get(),
            'personnels' => Personnel::where('status', 'Aktif')->orderBy('name')->get(),
        ]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'shift_id' => ['required', 'integer', Rule::exists('shifts', 'id')->where('is_active', true)],
            'floor_id' => ['required', 'integer', Rule::exists('floors', 'id')->where('is_active', true)],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->scheduleService->assignSchedule($validated, (int) $request->user()->id);

        return back()->with('success', 'Jadwal shift berhasil disimpan.');
    }

    public function setWfh(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->scheduleService->setWfh(
            (int) $validated['personnel_id'],
            $validated['date'],
            $validated['reason'] ?? '',
            (int) $request->user()->id,
        );

        return back()->with('success', 'Jadwal WFH berhasil ditetapkan.');
    }

    public function bulkGenerate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2020,2100'],
            'personnel_ids' => ['required', 'array', 'min:1'],
            'personnel_ids.*' => ['required', 'integer', 'exists:personnels,id'],
            'floor_id' => ['required', 'integer', Rule::exists('floors', 'id')->where('is_active', true)],
            'shift_ids' => ['required', 'array', 'min:1'],
            'shift_ids.*' => ['required', 'integer', Rule::exists('shifts', 'id')->where('is_active', true)],
            'work_days' => ['nullable', 'array'],
            'work_days.*' => ['integer', 'between:1,7'],
        ]);

        $rotations = [];
        foreach ($validated['personnel_ids'] as $personnelId) {
            $rotations[$personnelId] = [
                'floor_id' => (int) $validated['floor_id'],
                'shift_ids' => array_map('intval', $validated['shift_ids']),
                'work_days' => array_map('intval', $validated['work_days'] ?? range(1, 7)),
            ];
        }

        $count = $this->scheduleService->bulkGenerateMonthlySchedule(
            (int) $validated['month'],
            (int) $validated['year'],
            $rotations,
        );

        return back()->with('success', $count.' jadwal berhasil dibuat atau diperbarui.');
    }
}
