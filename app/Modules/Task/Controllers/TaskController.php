<?php

namespace App\Modules\Task\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Area;
use App\Models\Building;
use App\Models\Personnel;
use App\Models\Task;
use App\Modules\Task\Services\TaskService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', Task::STATUSES)],
            'task_type' => ['nullable', 'in:'.implode(',', Task::TYPES)],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
        ]);

        $tasks = Task::query()
            ->with(['area.floor.building', 'assignees'])
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['task_type']), fn ($query) => $query->where('task_type', $validated['task_type']))
            ->when(isset($validated['area_id']), fn ($query) => $query->where('area_id', $validated['area_id']))
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('modules.task.index', [
            'tasks' => $tasks,
            'statuses' => Task::STATUSES,
            'types' => Task::TYPES,
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $buildings = Building::with(['floors' => function ($query) {
            $query->where('is_active', true)->with(['areas' => function ($areaQuery) {
                $areaQuery->where('is_active', true);
            }]);
        }])->where('is_active', true)->get();

        return view('modules.task.create', [
            'buildings' => $buildings,
            'personnels' => Personnel::where('status', 'Aktif')->orderBy('name')->get(),
            'types' => Task::TYPES,
            'priorities' => Task::PRIORITIES,
            'statuses' => Task::STATUSES,
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('personnel_ids');
        $task = $this->taskService->createTask($data, $request->validated('personnel_ids'), (int) $request->user()->id);

        return redirect()->route('task.show', $task)->with('success', 'Tugas berhasil dibuat.');
    }

    public function show(Task $task): View
    {
        $task->load(['area.floor.building', 'assignees.position', 'statusHistories', 'inspections', 'createdBy', 'cancelledBy', 'source']);

        return view('modules.task.show', [
            'task' => $task,
            'statuses' => Task::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Task::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->taskService->updateStatus($task, $validated['status'], $validated['notes'] ?? null, (int) $request->user()->id);

        return back()->with('success', 'Status tugas berhasil diperbarui.');
    }

    public function cancel(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $this->taskService->cancelTask($task, $validated['reason'], (int) $request->user()->id);

        return back()->with('success', 'Tugas berhasil dibatalkan.');
    }

    public function inspect(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'is_approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'evidence_photo' => ['nullable', 'image', 'max:5120'],
        ]);
        $photoPath = $request->file('evidence_photo')?->store('task-inspections', 'public');

        $this->taskService->submitInspection(
            $task,
            (bool) $validated['is_approved'],
            $validated['notes'] ?? null,
            $photoPath,
            (int) $request->user()->id,
        );

        return back()->with('success', 'Hasil pemeriksaan tugas berhasil dicatat.');
    }
}
