<?php

namespace App\Modules\Monitoring\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInspectionRequest;
use App\Models\Area;
use App\Models\Building;
use App\Models\ChecklistTemplate;
use App\Models\InspectionFinding;
use App\Models\MonitoringInspection;
use App\Modules\Monitoring\Services\MonitoringService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function __construct(private readonly MonitoringService $monitoringService) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $inspections = MonitoringInspection::query()
            ->with(['area.floor.building', 'inspector'])
            ->withCount(['findings as open_findings_count' => fn ($query) => $query->where('status', 'open')])
            ->when(isset($validated['area_id']), fn ($query) => $query->where('area_id', $validated['area_id']))
            ->when(isset($validated['date_from']), fn ($query) => $query->whereDate('inspection_date', '>=', $validated['date_from']))
            ->when(isset($validated['date_to']), fn ($query) => $query->whereDate('inspection_date', '<=', $validated['date_to']))
            ->latest('inspection_date')
            ->latest('inspection_time')
            ->paginate(15)
            ->withQueryString();

        return view('modules.monitoring.index', [
            'inspections' => $inspections,
            'areas' => Area::with('floor.building')->where('is_active', true)->orderBy('name')->get(),
            'openFindings' => InspectionFinding::query()->with(['area.floor.building', 'inspection'])->where('status', 'open')->latest()->limit(8)->get(),
            'openFindingsCount' => InspectionFinding::where('status', 'open')->count(),
            'inProgressFindingsCount' => InspectionFinding::where('status', 'in_progress')->count(),
        ]);
    }

    public function create(): View
    {
        return view('modules.monitoring.create', [
            'buildings' => Building::with(['floors' => function ($query) {
                $query->where('is_active', true)->with(['areas' => function ($areaQuery) {
                    $areaQuery->where('is_active', true);
                }]);
            }])->where('is_active', true)->get(),
            'templates' => ChecklistTemplate::with(['items' => function ($query) {
                $query->where('is_active', true)->orderBy('order');
            }])->where('is_active', true)->get(),
            'statuses' => MonitoringInspection::OVERALL_STATUSES,
        ]);
    }

    public function templates(Area $area): JsonResponse
    {
        abort_unless($area->is_active, 404);

        $templates = ChecklistTemplate::query()
            ->where('area_type', $area->type)
            ->where('is_active', true)
            ->with(['items' => fn ($query) => $query->where('is_active', true)->orderBy('order')])
            ->get();

        return response()->json($templates->flatMap(fn ($template) => $template->items)->values());
    }

    public function store(StoreInspectionRequest $request): RedirectResponse
    {
        $itemsData = [];
        foreach ($request->validated('items') as $index => $itemData) {
            $itemsData[] = [
                'checklist_item_id' => $itemData['checklist_item_id'] ?? null,
                'item_snapshot_text' => $itemData['item_snapshot_text'],
                'status' => $itemData['status'],
                'photo_path' => $request->file("items.{$index}.photo")?->store('inspections', 'public'),
                'notes' => $itemData['notes'] ?? null,
            ];
        }

        $inspection = $this->monitoringService->storeInspection(
            $request->safe()->except('items'),
            $itemsData,
            (int) $request->user()->id,
        );

        return redirect()->route('monitoring.show', $inspection)->with('success', 'Inspeksi berhasil disimpan.');
    }

    public function show(MonitoringInspection $monitoring): View
    {
        $monitoring->load([
            'area.floor.building',
            'inspector',
            'details',
            'findings.recheckedBy',
        ]);

        return view('modules.monitoring.show', ['inspection' => $monitoring]);
    }

    public function recheck(Request $request, InspectionFinding $finding): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->monitoringService->recheckFinding(
            $finding->id,
            $validated['status'],
            $validated['notes'] ?? null,
            (int) $request->user()->id,
        );

        return back()->with('success', 'Status temuan berhasil diperbarui.');
    }
}
