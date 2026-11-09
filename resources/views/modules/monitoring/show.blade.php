@extends('layouts.app')

@section('title', $inspection->code)
@section('breadcrumb', 'Operasional / Monitoring / Laporan Inspeksi')
@section('heading', 'Laporan Inspeksi')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center print:hidden">
        <div>
            <p class="font-mono text-sm font-semibold text-teal-700">{{ $inspection->code }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $inspection->inspection_date->format('d F Y') }} · {{ substr($inspection->inspection_time, 0, 5) }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('monitoring.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
            <button type="button" onclick="window.print()" class="rounded-xl bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">Cetak laporan</button>
        </div>
    </div>

    <article class="space-y-6 print:space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm print:rounded-none print:shadow-none">
            <div class="flex flex-col justify-between gap-4 sm:flex-row">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lokasi pemeriksaan</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $inspection->area->name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $inspection->area->floor->building->name }} · {{ $inspection->area->floor->name }}</p>
                </div>
                <div class="sm:text-right">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kesimpulan</p>
                    <span @class(['mt-2 inline-flex rounded-full px-3 py-1.5 text-sm font-semibold', 'bg-emerald-50 text-emerald-700' => $inspection->overall_status === 'Sangat Bersih', 'bg-amber-50 text-amber-700' => $inspection->overall_status === 'Cukup', 'bg-rose-50 text-rose-700' => $inspection->overall_status === 'Kotor/Perlu Tindakan'])>{{ $inspection->overall_status }}</span>
                </div>
            </div>
            <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-3">
                <div><dt class="text-xs text-slate-400">Jenis inspeksi</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ ucfirst($inspection->inspection_type) }}</dd></div>
                <div><dt class="text-xs text-slate-400">Inspektor</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $inspection->inspector->name }}</dd></div>
                <div><dt class="text-xs text-slate-400">Kategori area</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ ucfirst($inspection->area->type) }}</dd></div>
            </dl>
            @if ($inspection->notes)<div class="mt-5 border-t border-slate-100 pt-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan umum</p><p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $inspection->notes }}</p></div>@endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm print:rounded-none print:shadow-none">
            <div class="border-b border-slate-100 px-6 py-4"><h2 class="font-semibold text-slate-900">Hasil checklist</h2></div>
            <div class="divide-y divide-slate-100">
                @foreach ($inspection->details as $detail)
                    <div class="flex flex-col gap-4 px-6 py-4 sm:flex-row sm:items-start">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-slate-800">{{ $detail->item_snapshot_text }}</p>
                            @if ($detail->notes)<p class="mt-1 whitespace-pre-line text-sm text-slate-500">{{ $detail->notes }}</p>@endif
                        </div>
                        <span @class(['w-fit rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $detail->status === 'Bersih', 'bg-rose-50 text-rose-700' => $detail->status === 'Kotor', 'bg-amber-50 text-amber-700' => $detail->status === 'Rusak', 'bg-slate-100 text-slate-600' => $detail->status === 'Tidak Berlaku'])>{{ $detail->status }}</span>
                        @if ($detail->photo_path)
                            <a href="{{ asset('storage/'.$detail->photo_path) }}" target="_blank" rel="noopener" class="print:hidden"><img src="{{ asset('storage/'.$detail->photo_path) }}" alt="Bukti {{ $detail->item_snapshot_text }}" class="h-20 w-28 rounded-lg object-cover"></a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm print:rounded-none print:shadow-none">
            <div class="flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Temuan dan tindak lanjut</h2><p class="mt-1 text-xs text-slate-500">Item kotor atau rusak akan dibuat sebagai temuan otomatis.</p></div><span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ $inspection->findings->count() }} temuan</span></div>
            <div class="mt-5 space-y-4">
                @forelse ($inspection->findings as $finding)
                    <div class="rounded-xl border border-slate-100 p-4">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row">
                            <div>
                                <p class="font-medium text-slate-800">{{ $finding->description }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $finding->area->name }} · dibuat {{ $finding->created_at->format('d M Y H:i') }}</p>
                            </div>
                            <span @class(['h-fit rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-rose-50 text-rose-700' => $finding->status === 'open', 'bg-amber-50 text-amber-700' => $finding->status === 'in_progress', 'bg-emerald-50 text-emerald-700' => $finding->status === 'resolved'])>{{ str_replace('_', ' ', ucfirst($finding->status)) }}</span>
                        </div>
                        @if ($finding->evidence_photo)<a href="{{ asset('storage/'.$finding->evidence_photo) }}" target="_blank" rel="noopener" class="mt-3 inline-block print:hidden"><img src="{{ asset('storage/'.$finding->evidence_photo) }}" alt="Foto temuan" class="h-28 rounded-lg object-cover"></a>@endif
                        @if ($finding->recheck_notes)<p class="mt-3 text-sm text-slate-600">Pemeriksaan ulang: {{ $finding->recheck_notes }} <span class="text-xs text-slate-400">· {{ $finding->recheckedBy?->name }} {{ $finding->recheck_at?->format('d M Y H:i') }}</span></p>@endif
                        @if ($finding->status !== 'resolved')
                            <form method="POST" action="{{ route('monitoring.findings.recheck', $finding) }}" class="mt-4 grid gap-2 sm:grid-cols-[10rem_1fr_auto] print:hidden">
                                @csrf
                                <select name="status" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm"><option value="in_progress" @selected($finding->status === 'in_progress')>Dalam proses</option><option value="resolved">Selesai</option><option value="open">Buka</option></select>
                                <input name="notes" value="{{ old('notes') }}" placeholder="Catatan pemeriksaan ulang" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <button class="rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700">Perbarui</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Tidak ada temuan dari inspeksi ini.</p>
                @endforelse
            </div>
        </section>
    </article>
@endsection
