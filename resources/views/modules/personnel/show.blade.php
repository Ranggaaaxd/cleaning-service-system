@extends('layouts.app')

@section('title', $personnel->name)
@section('breadcrumb', 'Operasional / Data Personel / Detail')
@section('heading', 'Detail Personel')

@section('content')
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div class="flex items-center gap-4">
            @if ($personnel->photo_path)
                <img src="{{ asset('storage/'.$personnel->photo_path) }}" alt="Foto {{ $personnel->name }}" class="size-16 rounded-2xl object-cover">
            @else
                <div class="flex size-16 items-center justify-center rounded-2xl bg-teal-100 text-xl font-bold text-teal-700">{{ strtoupper(substr($personnel->name, 0, 1)) }}</div>
            @endif
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $personnel->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">NIK {{ $personnel->nik }} · {{ $personnel->position->name }}</p>
            </div>
        </div>
        <a href="{{ route('personnel.edit', $personnel) }}" class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ubah data</a>
    </div>

    @if (auth()->user()->isSupervisor() && $personnel->changeRequests->where('status', 'pending')->isNotEmpty())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Terdapat permohonan koreksi identitas yang menunggu tinjauan Admin.</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(20rem,0.7fr)]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Informasi personel</h3>
                <dl class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Jenis kelamin</dt><dd class="mt-1 text-sm text-slate-800">{{ $personnel->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Telepon</dt><dd class="mt-1 text-sm text-slate-800">{{ $personnel->phone ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Tanggal bergabung</dt><dd class="mt-1 text-sm text-slate-800">{{ $personnel->join_date->format('d F Y') }}</dd></div>
                    <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Status</dt><dd class="mt-1 text-sm text-slate-800">{{ $personnel->status }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Alamat</dt><dd class="mt-1 whitespace-pre-line text-sm text-slate-800">{{ $personnel->address ?: '—' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Riwayat status</h3>
                <ol class="mt-5 space-y-5">
                    @forelse ($personnel->statusHistories as $history)
                        <li class="relative flex gap-4">
                            <span class="relative mt-1 flex size-3 shrink-0 rounded-full bg-teal-500 ring-4 ring-teal-50"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">{{ $history->old_status ?: 'Personel baru' }} <span class="font-normal text-slate-400">→</span> {{ $history->new_status }}</p>
                                    <time class="text-xs text-slate-400">{{ $history->created_at->format('d M Y, H:i') }}</time>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $history->reason ?: '—' }} · oleh {{ $history->changedBy->name }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum ada riwayat status.</li>
                    @endforelse
                </ol>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Riwayat mutasi penempatan</h3>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[480px] text-left text-sm">
                        <thead class="text-xs uppercase tracking-wide text-slate-400"><tr><th class="pb-3 font-medium">Tanggal efektif</th><th class="pb-3 font-medium">Dari</th><th class="pb-3 font-medium">Ke</th><th class="pb-3 font-medium">Alasan</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($personnel->mutationHistories as $history)
                                <tr><td class="py-3 text-slate-600">{{ $history->effective_date->format('d M Y') }}</td><td class="py-3 text-slate-600">{{ $history->fromFloor?->name ?? 'Penempatan awal' }}</td><td class="py-3 font-medium text-slate-800">{{ $history->toFloor->name }}</td><td class="py-3 text-slate-500">{{ $history->reason ?: '—' }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-slate-500">Belum ada riwayat mutasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($personnel->changeRequests->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900">Permohonan perubahan data</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($personnel->changeRequests as $changeRequest)
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-800">{{ strtoupper($changeRequest->field_name) }}: {{ $changeRequest->old_value }} → {{ $changeRequest->new_value }}</p>
                                <p class="mt-1 text-xs text-slate-500">Oleh {{ $changeRequest->requestedBy->name }} · {{ $changeRequest->created_at->format('d M Y') }} · {{ ucfirst($changeRequest->status) }}</p>
                                <p class="mt-2 text-sm text-slate-600">{{ $changeRequest->reason }}</p>
                                @if ($changeRequest->review_notes)<p class="mt-2 text-xs text-slate-500">Catatan Admin: {{ $changeRequest->review_notes }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Penempatan utama</h3>
                <p class="mt-4 text-lg font-semibold text-teal-700">{{ $personnel->floor?->name ?? 'Belum ditempatkan' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $personnel->floor?->building?->name ?? 'Pilih penempatan lantai' }}</p>
                <form method="POST" action="{{ route('personnel.mutate', $personnel) }}" class="mt-5 space-y-3 border-t border-slate-100 pt-5">
                    @csrf
                    <label for="floor_id" class="block text-xs font-medium text-slate-600">Mutasi penempatan</label>
                    <select id="floor_id" name="floor_id" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        @foreach ($floors as $floor)
                            <option value="{{ $floor->id }}" @selected($personnel->floor_id == $floor->id)>{{ $floor->building->name }} · {{ $floor->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="reason" required rows="2" placeholder="Alasan mutasi" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></textarea>
                    @error('floor_id') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    @error('reason') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    <button class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan mutasi</button>
                </form>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-slate-900">Perubahan status</h3>
                <form method="POST" action="{{ route('personnel.status', $personnel) }}" class="mt-4 space-y-3">
                    @csrf
                    <select name="status" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($personnel->status === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                    <textarea name="reason" required rows="2" placeholder="Alasan perubahan status" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm"></textarea>
                    @error('status') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    <button class="w-full rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Perbarui status</button>
                </form>
            </section>
        </aside>
    </div>
@endsection
