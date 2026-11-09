@extends('layouts.app')

@section('title', 'Jadwal Kerja')
@section('breadcrumb', 'Operasional / Jadwal Kerja')
@section('heading', 'Jadwal Kerja')

@section('content')
    @php
        $monthLabel = $periodStart->translatedFormat('F Y');
        $gridStart = $periodStart->startOfWeek();
        $gridEnd = $periodEnd->endOfWeek();
        $colorStyles = [
            'blue' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        ];
    @endphp
    <div class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-sm text-slate-500">Atur shift operasional dan penugasan WFH petugas.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="$dispatch('open-schedule-modal', 'shift')" class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">+ Jadwal Shift</button>
            <button type="button" @click="$dispatch('open-schedule-modal', 'wfh')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tetapkan WFH</button>
            <button type="button" @click="$dispatch('open-schedule-modal', 'bulk')" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Rotasi Bulanan</button>
        </div>
    </div>

    <form method="GET" action="{{ route('schedule.index') }}" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label for="month" class="mb-1 block text-xs font-medium text-slate-500">Bulan</label>
            <select id="month" name="month" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                @foreach (range(1, 12) as $monthNumber)
                    <option value="{{ $monthNumber }}" @selected($month === $monthNumber)>{{ \Carbon\Carbon::create()->month($monthNumber)->translatedFormat('F') }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="year" class="mb-1 block text-xs font-medium text-slate-500">Tahun</label>
            <input id="year" name="year" type="number" min="2020" max="2100" value="{{ $year }}" class="w-24 rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
        </div>
        <div>
            <label for="floor_filter" class="mb-1 block text-xs font-medium text-slate-500">Lantai</label>
            <select id="floor_filter" name="floor_id" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <option value="">Semua lantai</option>
                @foreach ($floors as $floor)
                    <option value="{{ $floor->id }}" @selected(request('floor_id') == $floor->id)>{{ $floor->building->name }} · {{ $floor->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="shift_filter" class="mb-1 block text-xs font-medium text-slate-500">Shift</label>
            <select id="shift_filter" name="shift_id" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <option value="">Semua shift</option>
                @foreach ($shifts as $shift)
                    <option value="{{ $shift->id }}" @selected(request('shift_id') == $shift->id)>{{ $shift->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date_filter" class="mb-1 block text-xs font-medium text-slate-500">Tanggal spesifik</label>
            <input id="date_filter" name="date" type="date" value="{{ request('date') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
        </div>
        <button class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Terapkan filter</button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center">
            <div>
                <h2 class="font-semibold text-slate-900">{{ $monthLabel }}</h2>
                <p class="mt-1 text-xs text-slate-500">Kalender jadwal shift dan WFH</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                @foreach ($shifts as $shift)
                    <span @class(['rounded-full px-2.5 py-1 font-medium ring-1 ring-inset', $colorStyles[$shift->color_badge] ?? 'bg-slate-100 text-slate-700'])>{{ $shift->name }}</span>
                @endforeach
                <span class="rounded-full bg-violet-50 px-2.5 py-1 font-medium text-violet-700 ring-1 ring-inset ring-violet-600/20">WFH</span>
            </div>
        </div>

        <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-500 sm:text-xs">
            @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)
                <div class="px-1 py-3 sm:px-3">{{ $weekday }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7">
            @for ($weekStart = $gridStart; $weekStart <= $gridEnd; $weekStart = $weekStart->addWeek())
                @for ($dayOffset = 0; $dayOffset < 7; $dayOffset++)
                    @php
                        $date = $weekStart->addDays($dayOffset);
                        $dateKey = $date->toDateString();
                        $daySchedules = $schedulesByDate->get($dateKey, collect());
                        $dayWfh = $wfhByDate->get($dateKey, collect());
                        $holiday = $holidays->get($dateKey);
                    @endphp
                    <div @class([
                        'min-h-32 border-b border-r border-slate-100 p-1.5 sm:min-h-40 sm:p-2',
                        'bg-slate-50/60' => $date->month !== $month,
                        'bg-white' => $date->month === $month,
                    ])>
                        <div class="flex items-center justify-between">
                            <span @class([
                                'flex size-6 items-center justify-center rounded-full text-xs font-semibold sm:size-7',
                                'bg-teal-600 text-white' => $date->toDateString() === today()->toDateString(),
                                'text-slate-700' => $date->toDateString() !== today()->toDateString() && $date->month === $month,
                                'text-slate-300' => $date->month !== $month,
                            ])>{{ $date->day }}</span>
                            @if ($holiday)
                                <span class="truncate text-[9px] font-medium text-rose-600 sm:text-[10px]" title="{{ $holiday->name }}">{{ $holiday->name }}</span>
                            @endif
                        </div>
                        <div class="mt-1.5 space-y-1">
                            @foreach ($daySchedules->take(3) as $schedule)
                                <div @class([
                                    'truncate rounded-md px-1.5 py-1 text-[9px] font-medium ring-1 ring-inset sm:text-[11px]',
                                    $colorStyles[$schedule->shift->color_badge] ?? 'bg-slate-100 text-slate-700',
                                ]) title="{{ $schedule->personnel->name }} · {{ $schedule->shift->name }} · {{ $schedule->floor->name }}">
                                    <span class="hidden sm:inline">{{ $schedule->shift->code }} · </span>{{ $schedule->personnel->name }}
                                </div>
                            @endforeach
                            @foreach ($dayWfh->take(max(0, 3 - $daySchedules->count())) as $wfh)
                                <div class="truncate rounded-md bg-violet-50 px-1.5 py-1 text-[9px] font-medium text-violet-700 ring-1 ring-inset ring-violet-600/20 sm:text-[11px]" title="{{ $wfh->personnel->name }} · WFH">
                                    <span class="hidden sm:inline">WFH · </span>{{ $wfh->personnel->name }}
                                </div>
                            @endforeach
                            @if ($daySchedules->count() + $dayWfh->count() > 3)
                                <p class="px-1 text-[9px] text-slate-400">+{{ $daySchedules->count() + $dayWfh->count() - 3 }} jadwal</p>
                            @endif
                        </div>
                    </div>
                @endfor
            @endfor
        </div>
        <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">{{ $schedules->count() }} jadwal shift · {{ $wfhSchedules->count() }} jadwal WFH</div>
    </section>

    <div
        x-data="{ modal: null }"
        @open-schedule-modal.window="modal = $event.detail"
        @keydown.escape.window="modal = null"
        x-cloak
        x-show="modal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
        @click.self="modal = null"
    >
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
            <div class="mb-5 flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900" x-text="modal === 'shift' ? 'Tetapkan Jadwal Shift' : (modal === 'wfh' ? 'Tetapkan WFH' : 'Buat Rotasi Bulanan')"></h2>
                    <p class="mt-1 text-sm text-slate-500">Jadwal yang sudah ada akan dikoreksi untuk personel dan tanggal yang sama.</p>
                </div>
                <button type="button" @click="modal = null" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Tutup">×</button>
            </div>

            <form x-show="modal === 'shift'" method="POST" action="{{ route('schedule.assign') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Personel</label>
                    <select name="personnel_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        @foreach ($personnels as $personnel)<option value="{{ $personnel->id }}">{{ $personnel->name }} · {{ $personnel->nik }}</option>@endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Shift</label>
                        <select name="shift_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            @foreach ($shifts as $shift)<option value="{{ $shift->id }}">{{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }})</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Lantai tugas</label>
                        <select name="floor_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                            @foreach ($floors as $floor)<option value="{{ $floor->id }}">{{ $floor->building->name }} · {{ $floor->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                    <input name="date" type="date" required min="{{ today()->toDateString() }}" value="{{ request('date', today()->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <textarea name="notes" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></textarea>
                <button class="w-full rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Simpan jadwal</button>
            </form>

            <form x-show="modal === 'wfh'" method="POST" action="{{ route('schedule.wfh') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Personel</label>
                    <select name="personnel_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        @foreach ($personnels as $personnel)<option value="{{ $personnel->id }}">{{ $personnel->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label><input name="date" type="date" required min="{{ today()->toDateString() }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-slate-700">Alasan</label><textarea name="reason" rows="3" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></textarea></div>
                <button class="w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700">Simpan WFH</button>
            </form>

            <form x-show="modal === 'bulk'" method="POST" action="{{ route('schedule.bulk-generate') }}" class="space-y-4">
                @csrf
                <p class="text-xs text-slate-500">Rotasi shift dimulai dari H-7 sebelum bulan terpilih. Pilih personel, lantai tugas, dan urutan rotasi shift.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Bulan</label><select name="month" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">@foreach (range(1, 12) as $monthNumber)<option value="{{ $monthNumber }}" @selected($monthNumber === $month)>{{ \Carbon\Carbon::create()->month($monthNumber)->translatedFormat('F') }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Tahun</label><input name="year" type="number" value="{{ $year }}" min="2020" max="2100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Personel</label>
                    <select name="personnel_ids[]" multiple required size="5" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($personnels as $personnel)<option value="{{ $personnel->id }}">{{ $personnel->name }} · {{ $personnel->nik }}</option>@endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Gunakan Ctrl/Cmd untuk memilih beberapa personel.</p>
                </div>
                <div><label class="mb-1 block text-sm font-medium text-slate-700">Lantai tugas</label><select name="floor_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">@foreach ($floors as $floor)<option value="{{ $floor->id }}">{{ $floor->building->name }} · {{ $floor->name }}</option>@endforeach</select></div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Urutan rotasi shift</label>
                    <select name="shift_ids[]" multiple required size="{{ min(max($shifts->count(), 2), 4) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        @foreach ($shifts as $shift)<option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->code }})</option>@endforeach
                    </select>
                </div>
                <button class="w-full rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Generate jadwal bulanan</button>
            </form>
        </div>
    </div>
@endsection
