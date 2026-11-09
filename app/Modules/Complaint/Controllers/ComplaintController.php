<?php

namespace App\Modules\Complaint\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Area;
use App\Models\Building;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Modules\Complaint\Services\ComplaintService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function __construct(private readonly ComplaintService $complaintService) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', Complaint::STATUSES)],
            'priority' => ['nullable', 'in:'.implode(',', Complaint::PRIORITIES)],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $complaints = Complaint::query()
            ->with(['area.floor.building', 'category'])
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['priority']), fn ($query) => $query->where('priority', $validated['priority']))
            ->when(isset($validated['area_id']), fn ($query) => $query->where('area_id', $validated['area_id']))
            ->when(isset($validated['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $validated['date_from']))
            ->when(isset($validated['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $validated['date_to']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('modules.complaint.index', [
            'complaints' => $complaints,
            'areas' => Area::with('floor.building')->where('is_active', true)->orderBy('name')->get(),
            'statuses' => Complaint::STATUSES,
            'priorities' => Complaint::PRIORITIES,
        ]);
    }

    public function create(): View
    {
        return view('modules.complaint.create', [
            'buildings' => Building::with(['floors' => function ($query) {
                $query->where('is_active', true)->with(['areas' => function ($areaQuery) {
                    $areaQuery->where('is_active', true);
                }]);
            }])->where('is_active', true)->get(),
            'categories' => ComplaintCategory::orderBy('name')->get(),
            'priorities' => Complaint::PRIORITIES,
        ]);
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('photo');
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('complaints', 'public');
        }

        $complaint = $this->complaintService->createComplaint($data, (int) $request->user()->id);

        return redirect()->route('complaint.show', $complaint)->with('success', 'Komplain berhasil dibuat dengan tiket '.$complaint->ticket_number.'.');
    }

    public function show(Complaint $complaint): View
    {
        $complaint->load(['area.floor.building', 'category', 'verifiedBy', 'statusHistories']);

        return view('modules.complaint.show', [
            'complaint' => $complaint,
            'statuses' => Complaint::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Complaint::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->complaintService->updateStatus(
            $complaint,
            $validated['status'],
            $validated['notes'] ?? null,
            (int) $request->user()->id,
        );

        return back()->with('success', 'Status komplain berhasil diperbarui.');
    }
}
