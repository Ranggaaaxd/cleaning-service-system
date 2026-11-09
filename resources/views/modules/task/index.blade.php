@extends('layouts.app')

@section('title', 'Penugasan Operasional')
@section('breadcrumb', 'Operasional / Penugasan')
@section('heading', 'Penugasan Operasional')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><p class="text-sm text-slate-500">Pantau pekerjaan dan petugas pelaksana.</p><a href="{{ route('task.create') }}" class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Buat tugas</a></div>
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-4">
            <select name="status" class="rounded-xl border-slate-300 text-sm"><option value="">Semua status</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select>
            <select name="task_type" class="rounded-xl border-slate-300 text-sm"><option value="">Semua tipe</option>@foreach ($types as $type)<option value="{{ $type }}" @selected(request('task_type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>@endforeach</select>
            <select name="area_id" class="rounded-xl border-slate-300 text-sm"><option value="">Semua area</option>@foreach ($areas as $area)<option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>@endforeach</select>
            <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700">Terapkan filter</button>
        </form>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Tugas</th><th class="px-5 py-3">Lokasi / Petugas</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Prioritas</th><th class="px-5 py-3">Status</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@forelse ($tasks as $task)<tr class="hover:bg-slate-50/70"><td class="px-5 py-4"><a href="{{ route('task.show', $task) }}" class="font-semibold text-teal-700">{{ $task->task_code }}</a><p class="mt-1 text-slate-700">{{ $task->title }}</p><p class="mt-1 text-xs text-slate-400">{{ str_replace('_', ' ', ucfirst($task->task_type)) }}</p></td><td class="px-5 py-4 text-slate-600">{{ $task->area->floor->building->name }} · {{ $task->area->floor->name }} · {{ $task->area->name }}<p class="mt-1">{{ $task->assignees->pluck('name')->join(', ') ?: 'Belum ditugaskan' }}</p></td><td class="px-5 py-4 text-slate-600">{{ $task->start_date->format('d M Y') }}@if ($task->due_date)<p class="mt-1">Tenggat {{ $task->due_date->format('d M Y') }} {{ $task->due_time ? substr($task->due_time, 0, 5) : '' }}</p>@endif</td><td class="px-5 py-4 text-slate-600">{{ $task->priority }}</td><td class="px-5 py-4">@if ($task->is_cancelled)<span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700">Dibatalkan</span>@else<span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700">{{ $task->status }}</span>@endif</td></tr>@empty<tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">Belum ada penugasan.</td></tr>@endforelse</tbody>
        </table></div><div class="border-t border-slate-100 px-5 py-4">{{ $tasks->links() }}</div></div>
    </div>
@endsection
