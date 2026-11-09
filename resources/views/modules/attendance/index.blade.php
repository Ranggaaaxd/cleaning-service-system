@extends('layouts.app')

@section('title', 'Absensi')
@section('breadcrumb', 'Operasional / Absensi')
@section('heading', 'Rekap Absensi')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Data kehadiran berdasarkan versi impor yang dipilih.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('attendance.history') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Riwayat versi</a>
            <a href="{{ route('attendance.import-form') }}" class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Impor absensi</a>
        </div>
    </div>

    @if ($activeImport)
        <div class="mb-5 flex flex-col justify-between gap-3 rounded-2xl border border-teal-200 bg-teal-50 p-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-semibold text-teal-900">Periode {{ \Carbon\Carbon::create()->month($activeImport->period_month)->translatedFormat('F') }} {{ $activeImport->period_year }}</p>
                <p class="mt-1 text-xs text-teal-800">{{ $activeImport->file_name }} · diimpor {{ $activeImport->created_at->format('d M Y H:i') }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800">Versi {{ $activeImport->version }} - {{ $activeImport->is_active ? 'Aktif' : 'Riwayat' }}</span>
        </div>

        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Total baris</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $activeImport->total_rows }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Berhasil dicocokkan</p><p class="mt-1 text-2xl font-bold text-emerald-700">{{ $activeImport->total_matched }}</p></div>
            <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Baris bermasalah</p><p class="mt-1 text-2xl font-bold text-rose-700">{{ $activeImport->total_unmatched }}</p></div>
        </div>
    @else
        <div class="mb-5 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
            <p class="font-semibold text-slate-800">Belum ada rekap absensi</p>
            <p class="mt-1 text-sm text-slate-500">Unggah file dari HR untuk mulai melihat data kehadiran.</p>
        </div>
    @endif

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div>
            <label for="import_id" class="mb-1 block text-xs font-medium text-slate-500">Versi rekap</label>
            <select id="import_id" name="import_id" class="min-w-56 rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                @foreach ($imports as $import)
                    <option value="{{ $import->id }}" @selected(($activeImport?->id ?? null) == $import->id)>{{ \Carbon\Carbon::create()->month($import->period_month)->translatedFormat('M') }} {{ $import->period_year }} · Versi {{ $import->version }}{{ $import->is_active ? ' - Aktif' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="date" class="mb-1 block text-xs font-medium text-slate-500">Tanggal</label><input id="date" name="date" type="date" value="{{ request('date') }}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></div>
        <div>
            <label for="status" class="mb-1 block text-xs font-medium text-slate-500">Status</label>
            <select id="status" name="status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <option value="">Semua status</option>
                @foreach (\App\Models\Attendance::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach
            </select>
        </div>
        <button class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Terapkan</button>
        @if ($activeImport && $activeImport->total_unmatched > 0)
            <a href="{{ route('attendance.errors', $activeImport) }}" class="ml-auto text-sm font-semibold text-rose-700 hover:text-rose-900">Lihat {{ $activeImport->total_unmatched }} baris bermasalah</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3.5 font-semibold">Tanggal</th><th class="px-5 py-3.5 font-semibold">Personel</th><th class="px-5 py-3.5 font-semibold">Jam masuk</th><th class="px-5 py-3.5 font-semibold">Jam pulang</th><th class="px-5 py-3.5 font-semibold">Terlambat</th><th class="px-5 py-3.5 font-semibold">Pulang awal</th><th class="px-5 py-3.5 font-semibold">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td class="px-5 py-4 text-slate-600">{{ $attendance->date->format('d M Y') }}</td>
                            <td class="px-5 py-4"><p class="font-medium text-slate-900">{{ $attendance->personnel->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $attendance->personnel->nik }} · {{ $attendance->personnel->position->name }}</p></td>
                            <td class="px-5 py-4 text-slate-600">{{ $attendance->check_in ? substr($attendance->check_in, 0, 5) : '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $attendance->check_out ? substr($attendance->check_out, 0, 5) : '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $attendance->late_minutes ? $attendance->late_minutes.' menit' : '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $attendance->early_leave_minutes ? $attendance->early_leave_minutes.' menit' : '—' }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700">{{ $attendance->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">Tidak ada data absensi untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($attendances, 'hasPages') && $attendances->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $attendances->links() }}</div>
        @endif
    </div>
@endsection
