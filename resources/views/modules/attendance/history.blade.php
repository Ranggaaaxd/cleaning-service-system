@extends('layouts.app')

@section('title', 'Riwayat Versi Absensi')
@section('breadcrumb', 'Operasional / Absensi / Riwayat')
@section('heading', 'Riwayat Versi Absensi')

@section('content')
    <div class="mb-6 flex justify-between gap-3">
        <p class="text-sm text-slate-500">Setiap periode memiliki versi rekap. Aktivasi versi mengganti rekap aktif untuk periode tersebut.</p>
        <a href="{{ route('attendance.import-form') }}" class="shrink-0 text-sm font-semibold text-teal-700 hover:text-teal-900">Impor baru</a>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3.5 font-semibold">Periode</th><th class="px-5 py-3.5 font-semibold">Versi</th><th class="px-5 py-3.5 font-semibold">File</th><th class="px-5 py-3.5 font-semibold">Impor oleh</th><th class="px-5 py-3.5 font-semibold">Baris / cocok / error</th><th class="px-5 py-3.5 font-semibold">Status</th><th class="px-5 py-3.5 font-semibold">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($imports as $import)
                        <tr>
                            <td class="px-5 py-4 font-medium text-slate-800">{{ \Carbon\Carbon::create()->month($import->period_month)->translatedFormat('F') }} {{ $import->period_year }}</td>
                            <td class="px-5 py-4 text-slate-600">Versi {{ $import->version }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $import->file_name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $import->importedBy->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $import->total_rows }} / {{ $import->total_matched }} / {{ $import->total_unmatched }}</td>
                            <td class="px-5 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-700' => $import->is_active, 'bg-slate-100 text-slate-600' => ! $import->is_active])>{{ $import->is_active ? 'Aktif' : 'Arsip' }}</span></td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('attendance.errors', $import) }}" class="font-medium text-teal-700 hover:text-teal-900">Error</a>
                                    @unless ($import->is_active)
                                        <form method="POST" action="{{ route('attendance.activate', $import) }}">@csrf<button class="font-medium text-slate-600 hover:text-slate-900">Aktifkan</button></form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">Belum ada versi impor absensi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($imports->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $imports->links() }}</div>@endif
    </div>
@endsection
