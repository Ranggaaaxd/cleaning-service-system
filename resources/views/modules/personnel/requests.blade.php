@extends('layouts.app')

@section('title', 'Permohonan Koreksi Identitas')
@section('breadcrumb', 'Pengelolaan / Permohonan Perubahan')
@section('heading', 'Permohonan Koreksi Identitas')

@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Tinjau perubahan NIK dan nama yang diajukan Supervisor.</p>
    </div>
    <div class="space-y-4">
        @forelse ($requests as $changeRequest)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-slate-900">{{ $changeRequest->personnel->name }}</h2>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">{{ ucfirst($changeRequest->status) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Diajukan {{ $changeRequest->created_at->format('d M Y, H:i') }} oleh {{ $changeRequest->requestedBy->name }}</p>
                    </div>
                    <a href="{{ route('personnel.show', $changeRequest->personnel) }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Lihat profil</a>
                </div>
                <div class="mt-5 rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perubahan {{ strtoupper($changeRequest->field_name) }}</p>
                    <p class="mt-2 text-sm text-slate-700"><span class="text-slate-400">{{ $changeRequest->old_value }}</span> <span class="px-2 text-teal-600">→</span> <strong>{{ $changeRequest->new_value }}</strong></p>
                    <p class="mt-3 text-sm text-slate-600">{{ $changeRequest->reason }}</p>
                </div>
                @if ($changeRequest->status === 'pending')
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        <form method="POST" action="{{ route('personnel.change-requests.approve', $changeRequest) }}" class="flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <input name="review_notes" placeholder="Catatan persetujuan (opsional)" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <button class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">Setujui</button>
                        </form>
                        <form method="POST" action="{{ route('personnel.change-requests.reject', $changeRequest) }}" class="flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <input name="review_notes" required placeholder="Alasan penolakan" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <button class="rounded-xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Tolak</button>
                        </form>
                    </div>
                @else
                    <p class="mt-4 text-xs text-slate-500">Ditinjau {{ $changeRequest->reviewed_at?->format('d M Y, H:i') }} oleh {{ $changeRequest->reviewedBy?->name ?? 'Admin' }}{{ $changeRequest->review_notes ? ' · '.$changeRequest->review_notes : '' }}</p>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center text-sm text-slate-500">Belum ada permohonan perubahan identitas.</div>
        @endforelse
    </div>
    @if ($requests->hasPages())
        <div class="mt-5">{{ $requests->links() }}</div>
    @endif
@endsection
