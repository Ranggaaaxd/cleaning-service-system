<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div x-data="{ sidebarOpen: false, sidebarExpanded: true }" class="min-h-screen">
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"
            aria-hidden="true"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarExpanded ? 'lg:w-72' : 'lg:w-20'
            ]"
            aria-label="Navigasi utama"
        >
            <a href="{{ route('dashboard') }}" class="flex h-20 shrink-0 items-center gap-3 border-b border-slate-100 px-6">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-lg font-bold text-white">C</span>
                <span x-show="sidebarExpanded" class="min-w-0">
                    <span class="block truncate text-sm font-bold text-slate-900">CleanCare</span>
                    <span class="block text-xs text-slate-500">Manajemen Operasional</span>
                </span>
            </a>

            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-6">
                <div>
                    <p x-show="sidebarExpanded" class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Menu Utama</p>
                    <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg bg-teal-50 px-3 py-2.5 text-sm font-semibold text-teal-700" title="Dashboard">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/></svg>
                        <span x-show="sidebarExpanded">Dashboard</span>
                    </a>
                </div>

                <div>
                    <p x-show="sidebarExpanded" class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Operasional</p>
                    <div class="space-y-1">
                        @foreach ([
                            ['Data Personel', route('personnel.index')],
                            ['Jadwal Kerja', 'schedule'],
                            ['Absensi', 'attendance'],
                            ['Monitoring Kebersihan', 'monitoring'],
                            ['Data Komplain', 'complaints'],
                            ['Peminjaman Gedung/Ruangan', 'bookings'],
                            ['Penugasan Operasional', 'tasks'],
                        ] as [$label, $icon])
                            <a href="{{ $icon }}" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" title="{{ $label }}">
                                <span class="flex size-5 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[10px] font-bold text-slate-500">{{ strtoupper(substr($label, 0, 1)) }}</span>
                                <span x-show="sidebarExpanded" class="truncate">{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p x-show="sidebarExpanded" class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Inventaris</p>
                    <a href="#" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" title="Gudang dan Inventaris">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[10px] font-bold text-slate-500">G</span>
                        <span x-show="sidebarExpanded">Gudang dan Inventaris</span>
                    </a>
                </div>

                <div>
                    <p x-show="sidebarExpanded" class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Pengelolaan</p>
                    <div class="space-y-1">
                        @foreach (['Laporan', 'Notifikasi', 'Pengaturan Sistem'] as $label)
                            <a href="#" @click="sidebarOpen = false" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" title="{{ $label }}">
                                <span class="flex size-5 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[10px] font-bold text-slate-500">{{ strtoupper(substr($label, 0, 1)) }}</span>
                                <span x-show="sidebarExpanded">{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('personnel.change-requests.index') }}" @click="sidebarOpen = false" class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" title="Permohonan Koreksi Identitas">
                            <span class="flex size-5 shrink-0 items-center justify-center rounded-md bg-amber-50 text-[10px] font-bold text-amber-700">K</span>
                            <span x-show="sidebarExpanded" class="truncate">Permohonan Koreksi Identitas</span>
                        </a>
                    @endif
                </div>
            </nav>

            <div class="border-t border-slate-100 p-3">
                <p x-show="sidebarExpanded" class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Profil</p>
                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50" title="Profil Pengguna">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-700">{{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}</span>
                    <span x-show="sidebarExpanded" class="truncate">Profil Pengguna</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-600 transition hover:bg-slate-50" title="Logout">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                        <span x-show="sidebarExpanded">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="min-h-screen transition-[padding] duration-200" :class="sidebarExpanded ? 'lg:pl-72' : 'lg:pl-20'">
            <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-4">
                    <button
                        type="button"
                        @click="if (window.innerWidth < 1024) { sidebarOpen = !sidebarOpen } else { sidebarExpanded = !sidebarExpanded }"
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                        aria-label="Toggle sidebar"
                    >
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-xs text-slate-500">Halaman / @yield('breadcrumb', 'Dashboard')</p>
                        <h1 class="truncate text-base font-semibold text-slate-900 sm:text-lg">@yield('heading', 'Dashboard')</h1>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @php
                        $currentUser = auth()->user();
                        $currentRole = match ($currentUser?->role) {
                            'admin' => 'Admin',
                            'supervisor' => 'SPV',
                            default => 'Admin',
                        };
                    @endphp
                    <span class="hidden rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 sm:inline-flex">{{ $currentRole }}</span>
                    <div x-data="{ profileOpen: false }" class="relative">
                        <button type="button" @click="profileOpen = !profileOpen" @click.outside="profileOpen = false" class="flex items-center gap-2 rounded-lg p-1.5 transition hover:bg-slate-50" aria-label="Menu profil pengguna">
                            <span class="flex size-9 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">{{ strtoupper(substr($currentUser?->name ?? 'Admin', 0, 1)) }}</span>
                            <span class="hidden text-left sm:block">
                                <span class="block max-w-36 truncate text-sm font-medium text-slate-800">{{ $currentUser?->name ?? 'Administrator' }}</span>
                                <span class="block text-xs text-slate-500">{{ $currentRole }}</span>
                            </span>
                            <svg class="hidden size-4 text-slate-400 sm:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-cloak x-show="profileOpen" x-transition class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            <a href="{{ route('profile.show') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Profil Pengguna</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div role="alert" class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <p class="font-semibold">Terjadi kesalahan:</p>
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
