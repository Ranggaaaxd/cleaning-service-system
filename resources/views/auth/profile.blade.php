@extends('layouts.app')

@section('title', 'Profil Pengguna')
@section('breadcrumb', 'Profil Pengguna')
@section('heading', 'Profil Pengguna')

@section('content')
    <div class="max-w-3xl">
        <div class="mb-6">
            <p class="text-sm text-slate-500">Perbarui informasi akun dan kredensial Anda.</p>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-base font-semibold text-slate-900">Informasi akun</h2>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                        @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor telepon</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                        @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-base font-semibold text-slate-900">Ganti password</h2>
                <p class="mt-1 text-sm text-slate-500">Kosongkan jika tidak ingin mengganti password.</p>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">Password saat ini</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                        @error('current_password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div></div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password baru</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi password baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-500/20">Simpan perubahan</button>
            </div>
        </form>
    </div>
@endsection
