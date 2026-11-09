<?php

namespace App\Modules\Booking\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Area;
use App\Models\Building;
use App\Models\RoomBooking;
use App\Modules\Booking\Services\BookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $bookings = RoomBooking::query()
            ->with(['area.floor.building', 'createdBy'])
            ->when(isset($validated['area_id']), fn ($query) => $query->where('area_id', $validated['area_id']))
            ->when(isset($validated['date_from']), fn ($query) => $query->whereDate('start_time', '>=', $validated['date_from']))
            ->when(isset($validated['date_to']), fn ($query) => $query->whereDate('start_time', '<=', $validated['date_to']))
            ->orderByDesc('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('modules.booking.index', [
            'bookings' => $bookings,
            'areas' => Area::with('floor.building')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $buildings = Building::with(['floors' => function ($query) {
            $query->where('is_active', true)->with(['areas' => function ($areaQuery) {
                $areaQuery->where('is_active', true);
            }]);
        }])->where('is_active', true)->get();

        return view('modules.booking.create', compact('buildings'));
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $booking = $this->bookingService->createBooking(
            $request->safe()->except(['conflict_acknowledged']),
            (int) $request->user()->id,
        );

        $conflicts = $this->bookingService->checkScheduleConflict(
            $booking->area_id,
            $booking->start_time->toDateTimeString(),
            $booking->end_time->toDateTimeString(),
            $booking->id,
        );

        $redirect = redirect()->route('booking.show', $booking)->with('success', 'Peminjaman berhasil diajukan dengan kode '.$booking->booking_code.'.');
        if ($conflicts->isNotEmpty()) {
            $redirect->with('warning', 'Perhatian: terdapat booking lain yang waktunya bertabrakan. Mohon koordinasikan jadwal ruangan.');
        }

        return $redirect;
    }

    public function conflicts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);
        $conflicts = $this->bookingService->checkScheduleConflict(
            (int) $validated['area_id'],
            $validated['start_time'],
            $validated['end_time'],
        );

        return response()->json($conflicts->map(fn (RoomBooking $booking) => [
            'booking_code' => $booking->booking_code,
            'event_name' => $booking->event_name,
            'start_time' => $booking->start_time->format('d/m/Y H:i'),
            'end_time' => $booking->end_time->format('d/m/Y H:i'),
            'status' => $booking->status,
        ]));
    }

    public function show(RoomBooking $booking): View
    {
        $booking->load(['area.floor.building', 'createdBy', 'statusHistories', 'tasks.assignees']);

        return view('modules.booking.show', compact('booking'));
    }

    public function generateTasks(Request $request, RoomBooking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'task_types' => ['required', 'array', 'min:1'],
            'task_types.*.title' => ['required', 'string', 'max:255'],
        ]);

        $tasks = $this->bookingService->generateTasksForBooking(
            $booking,
            $validated['task_types'],
            (int) $request->user()->id,
        );

        return back()->with('success', count($tasks).' tugas operasional berhasil dibuat.');
    }
}
