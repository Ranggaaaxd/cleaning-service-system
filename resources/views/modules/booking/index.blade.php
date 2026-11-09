@extends('layouts.app')

@section('title', 'Peminjaman Ruangan')
@section('breadcrumb', 'Operasional / Peminjaman')
@section('heading', 'Peminjaman Gedung/Ruangan')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <p class="text-sm text-slate-500">Kelola jadwal penggunaan gedung dan ruangan.</p>
            <a href="{{ route('booking.create') }}" class="rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Ajukan peminjaman</a>
        </div>
        <form method="GET" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-4">
            <select name="area_id" class="rounded-xl border-slate-300 text-sm"><option value="">Semua area</option>@foreach ($areas as $area)<option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->floor->building->name }} · {{ $area->floor->name }} · {{ $area->name }}</option>@endforeach</select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-xl border-slate-300 text-sm" aria-label="Tanggal mulai">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-xl border-slate-300 text-sm" aria-label="Tanggal akhir">
            <button class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Terapkan filter</button>
        </form>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Kode / Acara</th><th class="px-5 py-3">Lokasi</th><th class="px-5 py-3">Waktu</th><th class="px-5 py-3">Penyelenggara</th><th class="px-5 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($bookings as $booking)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-4"><a href="{{ route('booking.show', $booking) }}" class="font-semibold text-teal-700 hover:text-teal-900">{{ $booking->booking_code }}</a><p class="mt-1 text-slate-600">{{ $booking->event_name }}</p></td>
                                <td class="px-5 py-4 text-slate-600">{{ $booking->area->floor->building->name }} · {{ $booking->area->floor->name }}<p class="mt-1">{{ $booking->area->name }}</p></td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $booking->start_time->format('d M Y H:i') }}<p class="mt-1">s.d. {{ $booking->end_time->format('d M Y H:i') }}</p></td>
                                <td class="px-5 py-4 text-slate-600">{{ $booking->organizer_name }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ $booking->status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada data peminjaman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $bookings->links() }}</div>
        </div>
    </div>
@endsection
