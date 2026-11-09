@extends('layouts.app')

@section('title', 'Buat Tugas')
@section('breadcrumb', 'Operasional / Penugasan / Baru')
@section('heading', 'Buat Tugas Operasional')

@section('content')
    @if ($errors->any())<div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('task.store') }}" x-data="taskLocation(@js($buildings->map(fn ($building) => ['id' => $building->id, 'name' => $building->name, 'floors' => $building->floors->map(fn ($floor) => ['id' => $floor->id, 'name' => $floor->name, 'areas' => $floor->areas->map(fn ($area) => ['id' => $area->id, 'name' => $area->name])->values()])->values()])->values()), @js(old('area_id')))" class="max-w-5xl space-y-6">
        @csrf
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><h2 class="font-semibold text-slate-900">Informasi tugas</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><label for="title" class="mb-1.5 block text-sm font-medium text-slate-700">Judul tugas</label><input id="title" name="title" value="{{ old('title') }}" required class="w-full rounded-xl border-slate-300 text-sm">@error('title')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="task_type" class="mb-1.5 block text-sm font-medium text-slate-700">Tipe</label><select id="task_type" name="task_type" required class="w-full rounded-xl border-slate-300 text-sm">@foreach ($types as $type)<option value="{{ $type }}" @selected(old('task_type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>@endforeach</select></div>
                <div><label for="priority" class="mb-1.5 block text-sm font-medium text-slate-700">Prioritas</label><select id="priority" name="priority" required class="w-full rounded-xl border-slate-300 text-sm">@foreach ($priorities as $priority)<option value="{{ $priority }}" @selected(old('priority', 'Sedang') === $priority)>{{ $priority }}</option>@endforeach</select></div>
                <div class="sm:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div><label for="building_id" class="mb-1.5 block text-sm font-medium text-slate-700">Gedung</label><select id="building_id" x-model="buildingId" @change="floorId = ''; areaId = ''" class="w-full rounded-xl border-slate-300 text-sm"><option value="">Pilih Gedung</option><template x-for="building in buildings" :key="building.id"><option :value="String(building.id)" x-text="building.name"></option></template></select></div>
                    <div><label for="floor_id" class="mb-1.5 block text-sm font-medium text-slate-700">Lantai</label><select id="floor_id" x-model="floorId" @change="areaId = ''" :disabled="!buildingId" class="w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"><option value="">Pilih Lantai</option><template x-for="floor in floors" :key="floor.id"><option :value="String(floor.id)" x-text="floor.name"></option></template></select></div>
                    <div><label for="area_id" class="mb-1.5 block text-sm font-medium text-slate-700">Area/Ruangan</label><select id="area_id" name="area_id" x-model="areaId" required :disabled="!floorId" class="w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"><option value="">Pilih Area / Ruangan</option><template x-for="area in areas" :key="area.id"><option :value="String(area.id)" x-text="area.name"></option></template></select>@error('area_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                </div>
                <div><label for="start_date" class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal mulai</label><input id="start_date" name="start_date" type="date" value="{{ old('start_date', today()->toDateString()) }}" required class="w-full rounded-xl border-slate-300 text-sm"></div>
                <div><label for="due_date" class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal tenggat</label><input id="due_date" name="due_date" type="date" value="{{ old('due_date') }}" class="w-full rounded-xl border-slate-300 text-sm"></div>
                <div><label for="due_time" class="mb-1.5 block text-sm font-medium text-slate-700">Waktu tenggat</label><input id="due_time" name="due_time" type="time" value="{{ old('due_time') }}" class="w-full rounded-xl border-slate-300 text-sm"></div>
                <div><label for="status" class="mb-1.5 block text-sm font-medium text-slate-700">Status awal</label><select id="status" name="status" required class="w-full rounded-xl border-slate-300 text-sm">@foreach ($statuses as $status)<option value="{{ $status }}" @selected(old('status', 'Belum Dikerjakan') === $status)>{{ $status }}</option>@endforeach</select></div>
                <div class="sm:col-span-2"><label for="description" class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi</label><textarea id="description" name="description" rows="3" class="w-full rounded-xl border-slate-300 text-sm">{{ old('description') }}</textarea></div>
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><h2 class="font-semibold text-slate-900">Petugas pelaksana</h2><p class="mt-1 text-sm text-slate-500">Pilih satu atau beberapa petugas berstatus aktif.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach ($personnels as $personnel)<label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 hover:border-teal-300"><input type="checkbox" name="personnel_ids[]" value="{{ $personnel->id }}" @checked(in_array($personnel->id, old('personnel_ids', []))) class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"><span><span class="block text-sm font-medium text-slate-800">{{ $personnel->name }}</span><span class="text-xs text-slate-500">{{ $personnel->nik }} · {{ $personnel->position->name }}</span></span></label>@endforeach</div>@error('personnel_ids')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </section>
        <div class="flex justify-end gap-3"><a href="{{ route('task.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</a><button class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white">Buat tugas</button></div>
    </form>
    <script>
        function taskLocation(buildings, oldAreaId) {
            return {
                buildings, buildingId: '', floorId: '', areaId: oldAreaId ? String(oldAreaId) : '',
                get floors() { return this.buildings.find(item => String(item.id) === this.buildingId)?.floors ?? []; },
                get areas() { return this.floors.find(item => String(item.id) === this.floorId)?.areas ?? []; },
                init() {
                    for (const building of this.buildings) for (const floor of building.floors) {
                        if (floor.areas.some(area => String(area.id) === this.areaId)) {
                            this.buildingId = String(building.id); this.floorId = String(floor.id); return;
                        }
                    }
                    this.areaId = '';
                },
            };
        }
    </script>
@endsection
