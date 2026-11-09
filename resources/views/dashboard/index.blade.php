@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('heading', 'Ringkasan Operasional')

@section('content')
    <div class="mb-8 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm text-slate-500">Selamat datang kembali, {{ auth()->user()?->name ?? 'Administrator' }}.</p>
            <p class="mt-1 text-sm text-slate-500">Berikut ringkasan aktivitas operasional cleaning service.</p>
        </div>
        <p class="text-sm font-medium text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    <section aria-label="Statistik operasional" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statistics as $statistic)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium leading-5 text-slate-500">{{ $statistic['label'] }}</p>
                        <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ $statistic['value'] }}</p>
                    </div>
                    <span @class([
                        'flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold',
                        'bg-blue-50 text-blue-700' => $statistic['tone'] === 'blue',
                        'bg-violet-50 text-violet-700' => $statistic['tone'] === 'violet',
                        'bg-amber-50 text-amber-700' => $statistic['tone'] === 'amber',
                        'bg-rose-50 text-rose-700' => $statistic['tone'] === 'rose',
                    ]) aria-hidden="true">
                        @switch($statistic['icon'])
                            @case('people') P @break
                            @case('tasks') T @break
                            @case('complaints') K @break
                            @case('inventory') I @break
                        @endswitch
                    </span>
                </div>
                <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">{{ $statistic['note'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Aktivitas operasional</h2>
                <p class="mt-1 text-sm text-slate-500">Ringkasan modul akan ditampilkan di sini.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                <span class="size-1.5 rounded-full bg-teal-500"></span>
                Sistem siap digunakan
            </span>
        </div>
    </section>
@endsection
