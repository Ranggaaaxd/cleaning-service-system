@extends('layouts.app')

@section('title', 'Error Impor Absensi')
@section('breadcrumb', 'Operasional / Absensi / Error Impor')
@section('heading', 'Detail Baris Bermasalah')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">{{ $import->file_name }} · {{ \Carbon\Carbon::create()->month($import->period_month)->translatedFormat('F') }} {{ $import->period_year }} · Versi {{ $import->version }}</p>
        </div>
        <a href="{{ route('attendance.history') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Kembali ke riwayat</a>
    </div>
    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Total baris</p><p class="mt-1 text-xl font-bold text-slate-900">{{ $import->total_rows }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Berhasil</p><p class="mt-1 text-xl font-bold text-emerald-700">{{ $import->total_matched }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Perlu koreksi</p><p class="mt-1 text-xl font-bold text-rose-700">{{ $import->total_unmatched }}</p></div>
    </div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3.5 font-semibold">Baris file</th><th class="px-5 py-3.5 font-semibold">NIK</th><th class="px-5 py-3.5 font-semibold">Nama dari file</th><th class="px-5 py-3.5 font-semibold">Keterangan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($import->errors as $error)
                        <tr><td class="px-5 py-4 text-slate-600">{{ $error->row_number }}</td><td class="px-5 py-4 font-mono text-slate-800">{{ $error->raw_nik ?: '—' }}</td><td class="px-5 py-4 text-slate-600">{{ $error->raw_name ?: '—' }}</td><td class="px-5 py-4 text-rose-700">{{ $error->error_message }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-slate-500">Tidak ada baris bermasalah pada impor ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
