@extends('layouts.app')

@section('title', 'Ajukan Peminjaman')
@section('breadcrumb', 'Operasional / Peminjaman / Baru')
@section('heading', 'Ajukan Peminjaman Ruangan')

@section('content')
    @if ($errors->any())<div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('booking.store') }}" x-data="bookingForm(@js($buildings->map(fn ($building) => ['id' => $building->id, 'name' => $building->name, 'floors' => $building->floors->map(fn ($floor) => ['id' => $floor->id, 'name' => $floor->name, 'areas' => $floor->areas->map(fn ($area) => ['id' => $area->id, 'name' => $area->name])->values()])->values()])->values()), @js(old('area_id')))" class="max-w-4xl space-y-6">
        @csrf
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="font-semibold text-slate-900">Lokasi dan waktu acara</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div><label for="building_id" class="mb-1.5 block text-sm font-medium text-slate-700">Gedung</label><select id="building_id" x-model="buildingId" @change="floorId = ''; areaId = ''; checkConflicts()" class="w-full rounded-xl border-slate-300 text-sm"><option value="">Pilih Gedung</option><template x-for="building in buildings" :key="building.id"><option :value="String(building.id)" x-text="building.name"></option></template></select></div>
                <div><label for="floor_id" class="mb-1.5 block text-sm font-medium text-slate-700">Lantai</label><select id="floor_id" x-model="floorId" @change="areaId = ''; checkConflicts()" :disabled="!buildingId" class="w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"><option value="">Pilih Lantai</option><template x-for="floor in floors" :key="floor.id"><option :value="String(floor.id)" x-text="floor.name"></option></template></select></div>
                <div><label for="area_id" class="mb-1.5 block text-sm font-medium text-slate-700">Area/Ruangan</label><select id="area_id" name="area_id" x-model="areaId" @change="checkConflicts()" required :disabled="!floorId" class="w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"><option value="">Pilih Area / Ruangan</option><template x-for="area in areas" :key="area.id"><option :value="String(area.id)" x-text="area.name"></option></template></select>@error('area_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            </div>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><label for="start_time" class="mb-1.5 block text-sm font-medium text-slate-700">Waktu mulai</label><input id="start_time" name="start_time" type="datetime-local" value="{{ old('start_time') }}" x-model="startTime" @change="checkConflicts()" required class="w-full rounded-xl border-slate-300 text-sm">@error('start_time')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="end_time" class="mb-1.5 block text-sm font-medium text-slate-700">Waktu selesai</label><input id="end_time" name="end_time" type="datetime-local" value="{{ old('end_time') }}" x-model="endTime" @change="checkConflicts()" required class="w-full rounded-xl border-slate-300 text-sm">@error('end_time')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            </div>
            <div x-show="conflicts.length" x-cloak role="status" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><p class="font-semibold">Peringatan benturan jadwal — pengajuan tetap dapat dilanjutkan.</p><template x-for="conflict in conflicts" :key="conflict.booking_code"><p class="mt-1" x-text="`${conflict.booking_code} · ${conflict.event_name} · ${conflict.start_time}–${conflict.end_time} (${conflict.status})`"></p></template></div>
            <p x-show="conflictError" x-cloak role="alert" class="mt-3 text-sm text-rose-600" x-text="conflictError"></p>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="font-semibold text-slate-900">Informasi penyelenggara</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><label for="event_name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama acara</label><input id="event_name" name="event_name" value="{{ old('event_name') }}" required class="w-full rounded-xl border-slate-300 text-sm">@error('event_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="organizer_name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama penyelenggara</label><input id="organizer_name" name="organizer_name" value="{{ old('organizer_name') }}" required class="w-full rounded-xl border-slate-300 text-sm">@error('organizer_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="organizer_contact" class="mb-1.5 block text-sm font-medium text-slate-700">Kontak penyelenggara</label><input id="organizer_contact" name="organizer_contact" value="{{ old('organizer_contact') }}" required class="w-full rounded-xl border-slate-300 text-sm">@error('organizer_contact')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="participant_count" class="mb-1.5 block text-sm font-medium text-slate-700">Jumlah peserta (opsional)</label><input id="participant_count" name="participant_count" type="number" min="1" value="{{ old('participant_count') }}" class="w-full rounded-xl border-slate-300 text-sm"></div>
                <div class="sm:col-span-2"><label for="notes" class="mb-1.5 block text-sm font-medium text-slate-700">Catatan</label><textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border-slate-300 text-sm">{{ old('notes') }}</textarea></div>
            </div>
        </section>
        <div class="flex justify-end gap-3"><a href="{{ route('booking.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white">Ajukan peminjaman</button></div>
    </form>
    <script>
        function bookingForm(buildings, oldAreaId) {
            return {
                buildings, buildingId: '', floorId: '', areaId: oldAreaId ? String(oldAreaId) : '',
                startTime: @js(old('start_time')), endTime: @js(old('end_time')),
                conflicts: [], conflictError: '', conflictTimer: null,
                get floors() { return this.buildings.find(item => String(item.id) === this.buildingId)?.floors ?? []; },
                get areas() { return this.floors.find(item => String(item.id) === this.floorId)?.areas ?? []; },
                init() {
                    for (const building of this.buildings) for (const floor of building.floors) {
                        if (floor.areas.some(area => String(area.id) === this.areaId)) {
                            this.buildingId = String(building.id); this.floorId = String(floor.id); this.checkConflicts(); return;
                        }
                    }
                    this.areaId = '';
                },
                checkConflicts() {
                    clearTimeout(this.conflictTimer); this.conflicts = []; this.conflictError = '';
                    if (!this.areaId || !this.startTime || !this.endTime || this.endTime <= this.startTime) return;
                    this.conflictTimer = setTimeout(async () => {
                        try {
                            const params = new URLSearchParams({ area_id: this.areaId, start_time: this.startTime.replace('T', ' '), end_time: this.endTime.replace('T', ' ') });
                            const response = await fetch(`{{ route('booking.conflicts') }}?${params}`, { headers: { Accept: 'application/json' } });
                            if (!response.ok) throw new Error('Peringatan benturan jadwal tidak dapat dimuat.');
                            this.conflicts = await response.json();
                        } catch (error) {
                            this.conflictError = error instanceof Error ? error.message : 'Terjadi kesalahan saat memeriksa jadwal.';
                        }
                    }, 250);
                },
            };
        }
    </script>
@endsection
