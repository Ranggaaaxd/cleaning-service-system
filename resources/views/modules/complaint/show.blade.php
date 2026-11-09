@extends('layouts.app')

@section('title', $complaint->ticket_number)
@section('breadcrumb', 'Operasional / Data Komplain / Detail')
@section('heading', 'Detail Tiket Komplain')

@section('content')
    @php
        $priorityStyles = ['Rendah' => 'bg-slate-100 text-slate-700', 'Sedang' => 'bg-blue-50 text-blue-700', 'Tinggi' => 'bg-amber-50 text-amber-800', 'Darurat' => 'bg-rose-50 text-rose-700'];
        $statusStyles = ['Baru' => 'bg-blue-50 text-blue-700', 'Diverifikasi' => 'bg-violet-50 text-violet-700', 'Diproses' => 'bg-amber-50 text-amber-800', 'Menunggu Pemeriksaan' => 'bg-orange-50 text-orange-800', 'Selesai' => 'bg-emerald-50 text-emerald-700', 'Ditolak/Dibatalkan' => 'bg-slate-100 text-slate-600'];
    @endphp
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <div><p class="font-mono text-sm font-semibold text-teal-700">{{ $complaint->ticket_number }}</p><p class="mt-1 text-sm text-slate-500">Dibuat {{ $complaint->created_at->format('d F Y, H:i') }}</p></div>
        <a href="{{ route('complaint.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali ke daftar</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap gap-2"><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', $priorityStyles[$complaint->priority] ?? 'bg-slate-100'])>{{ $complaint->priority }}</span><span @class(['rounded-full px-2.5 py-1 text-xs font-semibold', $statusStyles[$complaint->status] ?? 'bg-slate-100'])>{{ $complaint->status }}</span></div>
                <h2 class="mt-5 text-lg font-bold text-slate-900">{{ $complaint->category->name }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $complaint->description }}</p>
                @if ($complaint->photo_path)<a href="{{ asset('storage/'.$complaint->photo_path) }}" target="_blank" rel="noopener" class="mt-5 inline-block"><img src="{{ asset('storage/'.$complaint->photo_path) }}" alt="Foto keluhan {{ $complaint->ticket_number }}" class="max-h-96 rounded-xl object-cover"></a>@endif
                <dl class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-400">Pelapor</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $complaint->reporter_name }}</dd></div>
                    <div><dt class="text-xs text-slate-400">Kontak</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $complaint->reporter_contact ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-400">Lokasi</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $complaint->area->name }} · {{ $complaint->area->floor->building->name }} · {{ $complaint->area->floor->name }}</dd></div>
                    @if ($complaint->verified_at)<div><dt class="text-xs text-slate-400">Diverifikasi</dt><dd class="mt-1 text-sm text-slate-800">{{ $complaint->verified_at->format('d M Y H:i') }} oleh {{ $complaint->verifiedBy?->name }}</dd></div>@endif
                    @if ($complaint->resolved_at)<div><dt class="text-xs text-slate-400">Selesai</dt><dd class="mt-1 text-sm text-slate-800">{{ $complaint->resolved_at->format('d M Y H:i') }}</dd></div>@endif
                </dl>
            </section>

            @if (! in_array($complaint->status, ['Selesai', 'Ditolak/Dibatalkan'], true))
                <section x-data="{ open: false }" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-3"><div><h2 class="font-semibold text-slate-900">Tindakan komplain</h2><p class="mt-1 text-sm text-slate-500">Perbarui status dan catat tindak lanjut.</p></div><button type="button" @click="open = !open" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700" x-text="open ? 'Tutup' : 'Ubah status'"></button></div>
                    <form x-show="open" x-cloak method="POST" action="{{ route('complaint.status', $complaint) }}" class="mt-5 space-y-4 border-t border-slate-100 pt-5">
                        @csrf
                        <div><label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">Status baru</label><select id="status" name="status" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">@foreach ($statuses as $status)<option value="{{ $status }}" @selected($complaint->status === $status)>{{ $status }}</option>@endforeach</select>@error('status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label for="status_notes" class="mb-1.5 block text-sm font-medium text-slate-700">Catatan tindak lanjut</label><textarea id="status_notes" name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm"></textarea>@error('notes')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <button class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Simpan status</button>
                    </form>
                </section>
            @endif
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-slate-900">Riwayat status</h2>
            <ol class="mt-5 space-y-5">
                @foreach ($complaint->statusHistories as $history)
                    <li class="relative flex gap-3">
                        <span class="relative mt-1 flex size-3 shrink-0 rounded-full bg-teal-500 ring-4 ring-teal-50"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800">{{ $history->old_status ?: 'Tiket dibuat' }} <span class="font-normal text-slate-400">→</span> {{ $history->new_status }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $history->created_at->format('d M Y, H:i') }} · {{ $history->changedBy->name }}</p>
                            @if ($history->notes)<p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $history->notes }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
@endsection
