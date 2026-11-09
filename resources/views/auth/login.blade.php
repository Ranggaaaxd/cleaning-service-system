<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-teal-600 text-white shadow-lg shadow-teal-600/20" aria-hidden="true">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 19h14M7 16l2-7 3 4 3-7 2 10"/><path d="M5 5l.5 1L7 6.5 5.5 7 5 8l-.5-1L3 6.5 4.5 6 5 5Zm13 1 1 2 2 1-2 1-1 2-1-2-2-1 2-1 1-2Z"/></svg>
                </span>
                <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Selamat datang</h1>
                <p class="mt-2 text-sm text-slate-500">Masuk ke {{ config('app.name') }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                @if (session('status'))
                    <div role="status" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="block w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10" placeholder="nama@perusahaan.com">
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="block w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10" placeholder="Masukkan password">
                    </div>
                    <button type="submit" class="flex w-full items-center justify-center rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500/20">
                        Masuk ke Dashboard
                    </button>
                </form>
            </div>
            <p class="mt-6 text-center text-xs text-slate-400">Akses hanya untuk Admin dan Supervisor.</p>
        </div>
    </main>
</body>
</html>
