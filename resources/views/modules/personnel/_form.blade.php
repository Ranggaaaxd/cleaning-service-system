@php($isEdit = isset($personnel))
<form method="POST" action="{{ $isEdit ? route('personnel.update', $personnel) : route('personnel.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="text-base font-semibold text-slate-900">Informasi personel</h2>
        <p class="mt-1 text-sm text-slate-500">Data identitas dan kontak petugas.</p>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <label for="nik" class="mb-1.5 block text-sm font-medium text-slate-700">NIK</label>
                <input id="nik" name="nik" value="{{ old('nik', $personnel->nik ?? '') }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                @error('nik') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama lengkap</label>
                <input id="name" name="name" value="{{ old('name', $personnel->name ?? '') }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="gender" class="mb-1.5 block text-sm font-medium text-slate-700">Jenis kelamin</label>
                <select id="gender" name="gender" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500">
                    <option value="">Pilih jenis kelamin</option>
                    <option value="L" @selected(old('gender', $personnel->gender ?? '') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('gender', $personnel->gender ?? '') === 'P')>Perempuan</option>
                </select>
                @error('gender') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" class="mb-1.5 block text-sm font-medium text-slate-700">Nomor telepon</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', $personnel->phone ?? '') }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500">
                @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="address" class="mb-1.5 block text-sm font-medium text-slate-700">Alamat</label>
                <textarea id="address" name="address" rows="3" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500">{{ old('address', $personnel->address ?? '') }}</textarea>
                @error('address') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="photo" class="mb-1.5 block text-sm font-medium text-slate-700">Foto personel</label>
                <input id="photo" name="photo" type="file" accept="image/*" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold">
                @error('photo') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            @if ($isEdit && auth()->user()->isSupervisor())
                <div class="sm:col-span-2">
                    <label for="change_reason" class="mb-1.5 block text-sm font-medium text-slate-700">Alasan permohonan koreksi identitas</label>
                    <textarea id="change_reason" name="change_reason" rows="2" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500" placeholder="Wajib diisi jika mengubah NIK atau nama">{{ old('change_reason') }}</textarea>
                    @error('change_reason') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <h2 class="text-base font-semibold text-slate-900">Penempatan dan status kerja</h2>
        <p class="mt-1 text-sm text-slate-500">Tentukan jabatan, lokasi utama, dan masa kerja.</p>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <label for="position_id" class="mb-1.5 block text-sm font-medium text-slate-700">Jabatan</label>
                <select id="position_id" name="position_id" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500">
                    <option value="">Pilih jabatan</option>
                    @foreach ($positions as $position)
                        <option value="{{ $position->id }}" @selected(old('position_id', $personnel->position_id ?? '') == $position->id)>{{ $position->name }}</option>
                    @endforeach
                </select>
                @error('position_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="floor_id" class="mb-1.5 block text-sm font-medium text-slate-700">Penempatan lantai utama</label>
                <select id="floor_id" name="floor_id" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500">
                    <option value="">Belum ditempatkan</option>
                    @foreach ($floors as $floor)
                        <option value="{{ $floor->id }}" @selected(old('floor_id', $personnel->floor_id ?? '') == $floor->id)>{{ $floor->building->name }} · {{ $floor->name }}</option>
                    @endforeach
                </select>
                @error('floor_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                <select id="status" name="status" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500">
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $personnel->status ?? 'Aktif') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="join_date" class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal bergabung</label>
                <input id="join_date" name="join_date" type="date" value="{{ old('join_date', isset($personnel) ? $personnel->join_date->format('Y-m-d') : '') }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-teal-500">
                @error('join_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <div class="flex justify-end gap-3">
        <a href="{{ $isEdit ? route('personnel.show', $personnel) : route('personnel.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
        <button type="submit" class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">{{ $isEdit ? 'Simpan perubahan' : 'Simpan personel' }}</button>
    </div>
</form>
