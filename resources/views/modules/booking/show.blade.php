@extends('layouts.app')

@section('title', $booking->booking_code)
@section('breadcrumb', 'Operasional / Peminjaman / Detail')
@section('heading', 'Detail Peminjaman')

@section('content')
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div><p class="text-sm font-semibold text-teal-700">{{ $booking->booking_code }}</p><h2 class="mt-1 text-2xl font-bold text-slate-900">{{ $booking->event_name }}</h2><p class="mt-2 text-sm text-slate-500">Diajukan oleh {{ $booking->createdBy->name }}</p></div>
                <span class="w-fit rounded-full bg-sky-50 px-3 py-1.5 text-sm font-semibold text-sky-700">{{ $booking->status }}</span>
            </div>
            <dl class="mt-7 grid gap-5 border-t border-slate-100 pt-6 sm:grid-cols-2 lg:grid-cols-3">
                <div><dt class="text-xs font-semibold uppercase text-slate-400">Lokasi</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $booking->area->floor->building->name }} · {{ $booking->area->floor->name }} · {{ $booking->area->name }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-400">Mulai</dt><dd class="mt-1 text-sm text-slate-800">{{ $booking->start_time->format('d M Y, H:i') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-400">Selesai</dt><dd class="mt-1 text-sm text-slate-800">{{ $booking->end_time->format('d M Y, H:i') }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-400">Penyelenggara</dt><dd class="mt-1 text-sm text-slate-800">{{ $booking->organizer_name }} · {{ $booking->organizer_contact }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-400">Jumlah peserta</dt><dd class="mt-1 text-sm text-slate-800">{{ $booking->participant_count ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-400">Catatan</dt><dd class="mt-1 text-sm text-slate-800">{{ $booking->notes ?: '—' }}</dd></div>
            </dl>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h2 class="font-semibold text-slate-900">Tugas operasional pendukung</h2><p class="mt-1 text-sm text-slate-500">Buat tugas persiapan dan pembersihan untuk acara ini.</p></div>
                <form method="POST" action="{{ route('booking.generate-tasks', $booking) }}" class="flex flex-wrap gap-2">@csrf<input type="hidden" name="task_types[0][title]" value="Persiapan ruangan - {{ $booking->event_name }}"><input type="hidden" name="task_types[1][title]" value="Pembersihan setelah acara - {{ $booking->event_name }}"><button class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Generate Tugas Operasional</button></form>
            </div>
            <div class="mt-5 divide-y divide-slate-100">
                @forelse ($booking->tasks as $task)
                    <a href="{{ route('task.show', $task) }}" class="flex flex-col justify-between gap-2 py-4 sm:flex-row sm:items-center"><div><p class="font-semibold text-teal-700">{{ $task->task_code }} · {{ $task->title }}</p><p class="mt-1 text-sm text-slate-500">{{ $task->assignees->pluck('name')->join(', ') ?: 'Belum ada petugas' }}</p></div><span class="w-fit rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $task->status }}</span></a>
                @empty
                    <p class="py-6 text-sm text-slate-500">Belum ada tugas yang dibuat untuk peminjaman ini.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h2 class="font-semibold text-slate-900">Riwayat status</h2><div class="mt-4 space-y-4">@foreach ($booking->statusHistories as $history)<div class="border-l-2 border-teal-200 pl-4"><p class="text-sm font-semibold text-slate-800">{{ $history->new_status }}</p><p class="mt-1 text-xs text-slate-500">{{ $history->changedBy->name }} · {{ $history->created_at->format('d M Y H:i') }}</p>@if ($history->notes)<p class="mt-1 text-sm text-slate-600">{{ $history->notes }}</p>@endif</div>@endforeach</div></section>
    </div>
@endsection
