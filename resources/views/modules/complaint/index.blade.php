@extends('layouts.app')

@section('title', 'Data Komplain')
@section('breadcrumb', 'Operasional / Data Komplain')
@section('heading', 'Data Komplain')

@section('content')
    @php
        $priorityStyles = ['Rendah' => 'bg-slate-100 text-slate-700', 'Sedang' => 'bg-blue-50 text-blue-700', 'Tinggi' => 'bg-amber-50 text-amber-800', 'Darurat' => 'bg-rose-50 text-rose-700'];
        $statusStyles = ['Baru' => 'bg-blue-50 text-blue-700', 'Diverifikasi' => 'bg-violet-50 text-violet-700', 'Diproses' => 'bg-amber-50 text-amber-800', 'Menunggu Pemeriksaan' => 'bg-orange-50 text-orange-800', 'Selesai' => 'bg-emerald-50 text-emerald-700', 'Ditolak/Dibatalkan' => 'bg-slate-100 text-slate-600'];
    @endphp
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <p class="text-sm text-slate-500">Pantau, verifikasi, dan tindak lanjuti laporan kebersihan.</p>
        <a href="{{ route('complaint.create') }}" class="rounded-xl bg-teal-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-teal-700">+ Buat Komplain</a>
    </div>

    <form method="GET" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        <select name="status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="">Semua status</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select>
        <select name="priority" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="">Semua prioritas</option>@foreach ($priorities as $priority)<option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}</option>@endforeach</select>
        <select name="area_id" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><option value="">Semua area</option>@foreach ($areas as $area)<option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }} · {{ $area->floor->name }}</option>@endforeach</select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Dari tanggal" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
        <div class="flex gap-2"><input type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Sampai tanggal" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2.5 text-sm"><button class="rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button></div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3.5 font-semibold">Tiket</th><th class="px-5 py-3.5 font-semibold">Keluhan</th><th class="px-5 py-3.5 font-semibold">Area</th><th class="px-5 py-3.5 font-semibold">Prioritas</th><th class="px-5 py-3.5 font-semibold">Status</th><th class="px-5 py-3.5 font-semibold">Dilaporkan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($complaints as $complaint)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4"><a href="{{ route('complaint.show', $complaint) }}" class="font-mono font-semibold text-teal-700 hover:text-teal-900">{{ $complaint->ticket_number }}</a><p class="mt-1 text-xs text-slate-500">{{ $complaint->reporter_name }}</p></td>
                            <td class="max-w-xs px-5 py-4"><p class="truncate font-medium text-slate-800">{{ $complaint->category->name }}</p><p class="mt-1 truncate text-xs text-slate-500">{{ $complaint->description }}</p></td>
                            <td class="px-5 py-4"><p class="text-slate-700">{{ $complaint->area->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $complaint->area->floor->name }}</p></td>
                            <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', $priorityStyles[$complaint->priority] ?? 'bg-slate-100 text-slate-700'])>{{ $complaint->priority }}</span></td>
                            <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', $statusStyles[$complaint->status] ?? 'bg-slate-100 text-slate-700'])>{{ $complaint->status }}</span></td>
                            <td class="px-5 py-4 text-slate-500">{{ $complaint->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Tidak ada tiket komplain yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($complaints->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $complaints->links() }}</div>@endif
    </div>
@endsection
