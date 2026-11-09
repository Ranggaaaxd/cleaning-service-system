@extends('layouts.app')

@section('title', 'Impor Absensi')
@section('breadcrumb', 'Operasional / Absensi / Impor')
@section('heading', 'Impor Absensi HR')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Unggah rekap Excel. Setiap impor membuat versi baru dan menyimpan histori sebelumnya.</p>
        </div>
        <a href="{{ route('attendance.template') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Unduh template contoh</a>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form method="POST" action="{{ route('attendance.import') }}" enctype="multipart/form-data" x-data="{ fileName: '', uploading: false }" @submit="uploading = true">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="month" class="mb-1.5 block text-sm font-medium text-slate-700">Periode bulan</label>
                    <select id="month" name="month" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
                        @foreach (range(1, 12) as $monthNumber)
                            <option value="{{ $monthNumber }}" @selected(old('month', now()->month) == $monthNumber)>{{ \Carbon\Carbon::create()->month($monthNumber)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="year" class="mb-1.5 block text-sm font-medium text-slate-700">Periode tahun</label>
                    <input id="year" name="year" type="number" min="2020" max="2100" value="{{ old('year', now()->year) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
                </div>
            </div>

            <label for="file" class="mt-5 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-teal-400 hover:bg-teal-50/40">
                <span class="flex size-12 items-center justify-center rounded-xl bg-teal-100 text-teal-700">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5"/><path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4"/></svg>
                </span>
                <span class="mt-4 text-sm font-semibold text-slate-800" x-text="fileName || 'Klik untuk memilih atau letakkan file Excel di sini'"></span>
                <span class="mt-1 text-xs text-slate-500">XLSX, XLS, atau CSV · maksimal 10 MB</span>
                <input id="file" name="file" type="file" accept=".xlsx,.xls,.csv" required class="sr-only" @change="fileName = $event.target.files[0]?.name || ''">
            </label>

            <div class="mt-5 rounded-xl bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kolom wajib (urut sesuai template)</p>
                <p class="mt-2 text-sm text-slate-700">NIK · Nama Petugas · Tanggal (YYYY-MM-DD) · Jam Masuk (HH:MM) · Jam Pulang (HH:MM) · Status Kehadiran</p>
                <p class="mt-1 text-xs text-slate-500">Tanggal wajib sesuai bulan dan tahun periode impor.</p>
            </div>

            <div x-show="uploading" x-cloak class="mt-5 flex items-center gap-3 rounded-xl bg-teal-50 px-4 py-3 text-sm text-teal-800" role="status">
                <span class="size-4 animate-spin rounded-full border-2 border-teal-600 border-t-transparent"></span>
                File sedang diproses. Jangan tutup halaman ini.
            </div>
            <div class="mt-5 flex justify-end">
                <button type="submit" :disabled="uploading" class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:cursor-wait disabled:opacity-60">Mulai impor</button>
            </div>
        </form>
    </section>

    <section class="mt-8">
        <div class="mb-4 flex items-center justify-between">
            <div><h2 class="font-semibold text-slate-900">Riwayat rekap HR</h2><p class="mt-1 text-sm text-slate-500">Versi terdahulu dapat diaktifkan kembali dari riwayat versi.</p></div>
            <a href="{{ route('attendance.history') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Lihat semua</a>
        </div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-semibold">Periode / Versi</th><th class="px-5 py-3 font-semibold">Nama file</th><th class="px-5 py-3 font-semibold">Hasil</th><th class="px-5 py-3 font-semibold">Waktu impor</th><th class="px-5 py-3 font-semibold">Status</th><th class="px-5 py-3 font-semibold">Detail</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($imports as $import)
                            <tr>
                                <td class="px-5 py-3.5 font-medium text-slate-800">{{ \Carbon\Carbon::create()->month($import->period_month)->translatedFormat('M') }} {{ $import->period_year }} · V{{ $import->version }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $import->file_name }}</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $import->total_matched }}/{{ $import->total_rows }} cocok</td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $import->created_at->format('d M Y H:i') }}</td>
                                <td class="px-5 py-3.5"><span @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-700' => $import->is_active, 'bg-slate-100 text-slate-600' => ! $import->is_active])>{{ $import->is_active ? 'Aktif' : 'Arsip' }}</span></td>
                                <td class="px-5 py-3.5"><a href="{{ route('attendance.errors', $import) }}" class="font-medium text-teal-700 hover:text-teal-900">Error ({{ $import->errors_count }})</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada histori impor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($imports->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $imports->links() }}</div>@endif
        </div>
    </section>
@endsection
