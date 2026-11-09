<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePersonnelRequest;
use App\Http\Requests\UpdatePersonnelRequest;
use App\Models\Floor;
use App\Models\Personnel;
use App\Models\PersonnelChangeRequest;
use App\Models\Position;
use App\Modules\Personnel\Services\PersonnelService;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PersonnelController extends Controller
{
    public function __construct(private readonly PersonnelService $personnelService) {}

    public function index(Request $request): View
    {
        $personnels = Personnel::query()
            ->with(['position', 'floor.building'])
            ->when($request->filled('floor_id'), fn ($query) => $query->where('floor_id', $request->integer('floor_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('position_id'), fn ($query) => $query->where('position_id', $request->integer('position_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn ($query) => $query->where('nik', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('modules.personnel.index', [
            'personnels' => $personnels,
            'floors' => Floor::with('building')->where('is_active', true)->orderBy('floor_number')->get(),
            'positions' => Position::orderBy('name')->get(),
            'statuses' => Personnel::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('modules.personnel.create', $this->formOptions());
    }

    public function store(StorePersonnelRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('personnels', 'public');
        }

        $personnel = $this->personnelService->createPersonnel($data, (int) $request->user()->id);

        return redirect()->route('personnel.show', $personnel)->with('success', 'Data personel berhasil ditambahkan.');
    }

    public function show(Personnel $personnel): View
    {
        $personnel->load([
            'position',
            'floor.building',
            'statusHistories.changedBy',
            'mutationHistories',
            'changeRequests.requestedBy',
            'changeRequests.reviewedBy',
        ]);

        return view('modules.personnel.show', [
            'personnel' => $personnel,
            'floors' => Floor::with('building')->where('is_active', true)->orderBy('floor_number')->get(),
            'statuses' => Personnel::STATUSES,
        ]);
    }

    public function edit(Personnel $personnel): View
    {
        return view('modules.personnel.edit', [
            ...$this->formOptions(),
            'personnel' => $personnel->load(['position', 'floor']),
        ]);
    }

    public function update(UpdatePersonnelRequest $request, Personnel $personnel): RedirectResponse
    {
        $data = $request->validated();
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            if ($personnel->photo_path) {
                Storage::disk('public')->delete($personnel->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('personnels', 'public');
        }

        $this->personnelService->updatePersonnel($personnel, $data, $request->user());

        return redirect()->route('personnel.show', $personnel)->with('success', 'Data personel berhasil diperbarui.');
    }

    public function destroy(Request $request, Personnel $personnel): RedirectResponse
    {
        AuditService::log(
            $request->user(),
            'personnel',
            'delete',
            $personnel,
            $personnel->only(['nik', 'name', 'status', 'floor_id']),
            reason: 'Soft delete personel',
        );

        $personnel->delete();

        return redirect()->route('personnel.index')->with('success', 'Data personel berhasil diarsipkan.');
    }

    public function changeStatus(Request $request, Personnel $personnel): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Personnel::STATUSES)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->personnelService->changeStatus(
            $personnel,
            $validated['status'],
            $validated['reason'],
            (int) $request->user()->id,
        );

        return back()->with('success', 'Status personel berhasil diperbarui.');
    }

    public function mutate(Request $request, Personnel $personnel): RedirectResponse
    {
        $validated = $request->validate([
            'floor_id' => ['required', 'integer', 'exists:floors,id'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->personnelService->mutatePlacement(
            $personnel,
            (int) $validated['floor_id'],
            $validated['reason'],
            (int) $request->user()->id,
        );

        return back()->with('success', 'Penempatan lantai personel berhasil diperbarui.');
    }

    public function changeRequests(): View
    {
        $requests = PersonnelChangeRequest::query()
            ->with(['personnel', 'requestedBy'])
            ->latest()
            ->paginate(15);

        return view('modules.personnel.requests', compact('requests'));
    }

    public function approveChangeRequest(Request $request, PersonnelChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate(['review_notes' => ['nullable', 'string', 'max:2000']]);

        $this->personnelService->approveChangeRequest(
            $changeRequest->id,
            $request->user(),
            $validated['review_notes'] ?? null,
        );

        return back()->with('success', 'Permohonan perubahan data disetujui.');
    }

    public function rejectChangeRequest(Request $request, PersonnelChangeRequest $changeRequest): RedirectResponse
    {
        $validated = $request->validate(['review_notes' => ['required', 'string', 'max:2000']]);

        $this->personnelService->rejectChangeRequest(
            $changeRequest->id,
            $request->user(),
            $validated['review_notes'],
        );

        return back()->with('success', 'Permohonan perubahan data ditolak.');
    }

    /**
     * @return array{floors: \Illuminate\Database\Eloquent\Collection, positions: \Illuminate\Database\Eloquent\Collection, statuses: array<int, string>}
     */
    private function formOptions(): array
    {
        return [
            'floors' => Floor::with('building')->where('is_active', true)->orderBy('floor_number')->get(),
            'positions' => Position::orderBy('name')->get(),
            'statuses' => Personnel::STATUSES,
        ];
    }
}
