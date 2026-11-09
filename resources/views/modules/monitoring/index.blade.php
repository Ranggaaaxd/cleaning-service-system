@extends('layouts.app')

@section('title', 'Monitoring Kebersihan')
@section('breadcrumb', 'Operasional / Monitoring Kebersihan')
@section('heading', 'Monitoring Kebersihan')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <p class="text-sm text-slate-500">Pantau hasil inspeksi area dan tindak lanjut temuan.</p>
        <a href="{{ route('monitoring.create') }}" class="rounded-xl bg-teal-600 px-4 py-2.5 text-center text-sm font-semibold text-white hover:bg-teal-700">+ Inspeksi Baru</a>
    </div>

    <div class="mb-5 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Temuan terbuka</p>
            <p class="mt-2 text-3xl font-bold text-rose-700">{{ $openFindingsCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Menunggu tindak lanjut</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Dalam proses</p>
            <p class="mt-2 text-3xl font-bold text-amber-700">{{ $inProgressFindingsCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Perlu pemeriksaan ulang</p>
        </div>
    </div>

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label for="area_id" class="mb-1 block text-xs font-medium text-slate-500">Area</label>
            <select id="area_id" name="area_id" class="min-w-52 rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <option value="">Semua area</option>
                @foreach ($areas as $area)
                    <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }} · {{ $area->floor->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="date_from" class="mb-1 block text-xs font-medium text-slate-500">Dari tanggal</label><input id="date_from" name="date_from" type="date" value="{{ request('date_from') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
        <div><label for="date_to" class="mb-1 block text-xs font-medium text-slate-500">Sampai tanggal</label><input id="date_to" name="date_to" type="date" value="{{ request('date_to') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
        <button class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Filter</button>
    </form>

    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Riwayat inspeksi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3.5 font-semibold">Kode / Waktu</th><th class="px-5 py-3.5 font-semibold">Area</th><th class="px-5 py-3.5 font-semibold">Jenis</th><th class="px-5 py-3.5 font-semibold">Inspektor</th><th class="px-5 py-3.5 font-semibold">Hasil</th><th class="px-5 py-3.5 font-semibold">Temuan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($inspections as $inspection)
                        <tr>
                            <td class="px-5 py-4"><a href="{{ route('monitoring.show', $inspection) }}" class="font-semibold text-teal-700 hover:text-teal-900">{{ $inspection->code }}</a><p class="mt-1 text-xs text-slate-500">{{ $inspection->inspection_date->format('d M Y') }} · {{ substr($inspection->inspection_time, 0, 5) }}</p></td>
                            <td class="px-5 py-4"><p class="font-medium text-slate-800">{{ $inspection->area->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $inspection->area->floor->building->name }} · {{ $inspection->area->floor->name }}</p></td>
                            <td class="px-5 py-4 text-slate-600">{{ ucfirst($inspection->inspection_type) }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $inspection->inspector->name }}</td>
                            <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-700' => $inspection->overall_status === 'Sangat Bersih', 'bg-amber-50 text-amber-700' => $inspection->overall_status === 'Cukup', 'bg-rose-50 text-rose-700' => $inspection->overall_status === 'Kotor/Perlu Tindakan'])>{{ $inspection->overall_status }}</span></td>
                            <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-rose-50 text-rose-700' => $inspection->open_findings_count > 0, 'bg-slate-100 text-slate-600' => $inspection->open_findings_count === 0])>{{ $inspection->open_findings_count }} terbuka</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">Belum ada hasil inspeksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($inspections->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $inspections->links() }}</div>@endif
    </section>

    <section class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Temuan terbuka</h2><p class="mt-1 text-xs text-slate-500">Temuan terbaru yang perlu segera ditindaklanjuti.</p></div><span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ $openFindingsCount }} open</span></div>
        <div class="space-y-3">
            @forelse ($openFindings as $finding)
                <a href="{{ route('monitoring.show', $finding->inspection) }}" class="flex flex-col justify-between gap-2 rounded-xl border border-slate-100 p-4 transition hover:bg-slate-50 sm:flex-row sm:items-center">
                    <div><p class="text-sm font-semibold text-slate-800">{{ $finding->description }}</p><p class="mt-1 text-xs text-slate-500">{{ $finding->area->name }} · {{ $finding->inspection->code }}</p></div>
                    <span class="text-xs text-slate-500">{{ $finding->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Tidak ada temuan terbuka.</p>
            @endforelse
        </div>
    </section>
@endsection
