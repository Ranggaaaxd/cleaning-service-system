@extends('layouts.app')

@section('title', 'Data Personel')
@section('breadcrumb', 'Operasional / Data Personel')
@section('heading', 'Data Personel')

@section('content')
    @php
        $statusStyles = [
            'Aktif' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'Cuti' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'Nonaktif' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            'Resign' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            'Kontrak Berakhir' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
        ];
    @endphp
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Kelola data dan penempatan petugas cleaning service.</p>
        </div>
        <a href="{{ route('personnel.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700">
            <span class="text-lg leading-none">+</span> Tambah Personel
        </a>
    </div>

    <form method="GET" action="{{ route('personnel.index') }}" class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(14rem,1fr)_repeat(3,minmax(9rem,0.7fr))_auto]">
            <input name="search" value="{{ request('search') }}" type="search" placeholder="Cari NIK atau nama..." class="rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
            <select name="floor_id" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-teal-500">
                <option value="">Semua lantai</option>
                @foreach ($floors as $floor)
                    <option value="{{ $floor->id }}" @selected(request('floor_id') == $floor->id)>{{ $floor->building->name }} · {{ $floor->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-teal-500">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="position_id" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-teal-500">
                <option value="">Semua jabatan</option>
                @foreach ($positions as $position)
                    <option value="{{ $position->id }}" @selected(request('position_id') == $position->id)>{{ $position->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Filter</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Personel</th>
                        <th class="px-5 py-3.5 font-semibold">Jabatan</th>
                        <th class="px-5 py-3.5 font-semibold">Penempatan</th>
                        <th class="px-5 py-3.5 font-semibold">Tanggal bergabung</th>
                        <th class="px-5 py-3.5 font-semibold">Status</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($personnels as $personnel)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-4">
                                <a href="{{ route('personnel.show', $personnel) }}" class="font-semibold text-slate-900 hover:text-teal-700">{{ $personnel->name }}</a>
                                <p class="mt-1 text-xs text-slate-500">NIK {{ $personnel->nik }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $personnel->position->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $personnel->floor?->name ?? 'Belum ditempatkan' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $personnel->join_date->format('d M Y') }}</td>
                            <td class="px-5 py-4"><span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset', $statusStyles[$personnel->status] ?? 'bg-slate-100 text-slate-600'])>{{ $personnel->status }}</span></td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('personnel.edit', $personnel) }}" class="font-medium text-teal-700 hover:text-teal-900">Ubah</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada data personel yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($personnels->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $personnels->links() }}</div>
        @endif
    </div>
@endsection
