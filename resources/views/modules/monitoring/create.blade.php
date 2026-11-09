@extends('layouts.app')

@section('title', 'Inspeksi Baru')
@section('breadcrumb', 'Operasional / Monitoring / Inspeksi Baru')
@section('heading', 'Inspeksi Kebersihan')

@section('content')
    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <form method="POST" action="{{ route('monitoring.store') }}" enctype="multipart/form-data" x-data="inspectionForm(
        @js($buildings->map(fn ($building) => [
            'id' => $building->id,
            'name' => $building->name,
            'floors' => $building->floors->map(fn ($floor) => [
                'id' => $floor->id,
                'name' => $floor->name,
                'areas' => $floor->areas->map(fn ($area) => ['id' => $area->id, 'name' => $area->name, 'type' => $area->type])->values(),
            ])->values(),
        ])->values()),
        @js($templates->map(fn ($template) => [
            'area_type' => $template->area_type,
            'items' => $template->items->map(fn ($item) => ['id' => $item->id, 'task_name' => $item->task_name])->values(),
        ])->values()),
        @js(old('area_id'))
    )" class="space-y-6">
        @csrf
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="font-semibold text-slate-900">Informasi inspeksi</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label for="building_id" class="mb-1.5 block text-sm font-medium text-slate-700">Gedung</label>
                        <select id="building_id" x-model="buildingId" @change="floorId = ''; areaId = ''; loadChecklist()" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
                            <option value="">Pilih Gedung</option>
                            <template x-for="building in buildings" :key="building.id">
                                <option :value="String(building.id)" x-text="building.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="floor_id" class="mb-1.5 block text-sm font-medium text-slate-700">Lantai</label>
                        <select id="floor_id" x-model="floorId" @change="areaId = ''; loadChecklist()" :disabled="!buildingId" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="">Pilih Lantai</option>
                            <template x-for="floor in floors" :key="floor.id">
                                <option :value="String(floor.id)" x-text="floor.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="area_id" class="mb-1.5 block text-sm font-medium text-slate-700">Area/Ruangan</label>
                        <select id="area_id" name="area_id" x-model="areaId" @change="loadChecklist()" required :disabled="!floorId" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="">Pilih Area / Ruangan</option>
                            <template x-for="area in areas" :key="area.id">
                                <option :value="String(area.id)" x-text="area.name"></option>
                            </template>
                        </select>
                        @error('area_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="inspection_type" class="mb-1.5 block text-sm font-medium text-slate-700">Jenis inspeksi</label>
                    <select id="inspection_type" name="inspection_type" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm"><option value="rutin">Rutin</option><option value="mendadak">Mendadak</option></select>
                </div>
                <div>
                    <label for="overall_status" class="mb-1.5 block text-sm font-medium text-slate-700">Kesimpulan keseluruhan</label>
                    <select id="overall_status" name="overall_status" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">@foreach ($statuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select>
                </div>
                <div>
                    <label for="inspection_date" class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal inspeksi</label>
                    <input id="inspection_date" name="inspection_date" type="date" value="{{ old('inspection_date', today()->toDateString()) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
                </div>
                <div>
                    <label for="inspection_time" class="mb-1.5 block text-sm font-medium text-slate-700">Waktu inspeksi</label>
                    <input id="inspection_time" name="inspection_time" type="time" value="{{ old('inspection_time', now()->format('H:i')) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="notes" class="mb-1.5 block text-sm font-medium text-slate-700">Catatan umum</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">{{ old('notes') }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                <div><h2 class="font-semibold text-slate-900">Checklist area</h2><p class="mt-1 text-sm text-slate-500">Item dimuat sesuai kategori area. Tambahkan item manual bila perlu.</p></div>
                <button type="button" @click="addItem()" :disabled="!areaId" class="w-fit rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">+ Item manual</button>
            </div>
            <p x-show="!areaId" class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Silakan pilih Gedung, Lantai, dan Area terlebih dahulu untuk memuat item checklist kebersihan.</p>
            <div x-show="loading" x-cloak class="mt-5 flex items-center gap-2 text-sm text-slate-500"><span class="size-4 animate-spin rounded-full border-2 border-teal-600 border-t-transparent"></span>Memuat checklist...</div>
            <div x-show="loadError" x-cloak role="alert" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="loadError"></div>
            <div x-show="!loading && areaId && items.length === 0" x-cloak class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Belum ada template checklist aktif untuk jenis area ini. Tambahkan item manual untuk melanjutkan inspeksi.</div>
            <div class="mt-5 space-y-4">
                <template x-for="(item, index) in items" :key="item.key">
                    <article class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <input type="hidden" :name="`items[${index}][checklist_item_id]`" :value="item.id || ''">
                                <input type="hidden" :name="`items[${index}][item_snapshot_text]`" :value="item.task_name">
                                <input x-show="!item.id" x-model="item.task_name" required placeholder="Nama item checklist manual" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <p x-show="item.id" class="font-medium text-slate-800" x-text="item.task_name"></p>
                            </div>
                            <button type="button" @click="removeItem(index)" class="rounded-lg px-2 py-1 text-xs font-medium text-slate-400 hover:bg-rose-50 hover:text-rose-700" aria-label="Hapus item">Hapus</button>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <fieldset>
                                <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Penilaian</legend>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="status in ['Bersih', 'Kotor', 'Rusak', 'Tidak Berlaku']" :key="status">
                                        <label class="cursor-pointer">
                                            <input type="radio" :name="`items[${index}][status]`" :value="status" x-model="item.status" required class="peer sr-only">
                                            <span class="inline-flex rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:text-teal-800" x-text="status"></span>
                                        </label>
                                    </template>
                                </div>
                            </fieldset>
                            <div>
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Foto bukti (opsional)</label>
                                <input type="file" accept="image/*" :name="`items[${index}][photo]`" @change="previewFile($event, item)" class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold">
                                <template x-if="item.preview"><img :src="item.preview" alt="Preview foto" class="mt-2 h-24 rounded-lg object-cover"></template>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Catatan item</label>
                                <input :name="`items[${index}][notes]`" x-model="item.notes" placeholder="Keterangan kondisi item..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            </div>
                        </div>
                    </article>
                </template>
            </div>
            @error('items')<p class="mt-3 text-sm text-rose-600">{{ $message }}</p>@enderror
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('monitoring.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" :disabled="loading || !areaId || items.length === 0" class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50">Simpan inspeksi</button>
        </div>
    </form>

    <script>
        function inspectionForm(buildings, templates, oldAreaId) {
            return {
                buildings,
                templates,
                buildingId: '',
                floorId: '',
                areaId: oldAreaId ? String(oldAreaId) : '',
                items: [],
                loading: false,
                loadError: '',
                get floors() {
                    return this.buildings.find(building => String(building.id) === this.buildingId)?.floors ?? [];
                },
                get areas() {
                    return this.floors.find(floor => String(floor.id) === this.floorId)?.areas ?? [];
                },
                selectedArea() {
                    return this.areas.find(area => String(area.id) === this.areaId);
                },
                init() {
                    if (!this.areaId) return;

                    for (const building of this.buildings) {
                        for (const floor of building.floors) {
                            if (floor.areas.some(area => String(area.id) === this.areaId)) {
                                this.buildingId = String(building.id);
                                this.floorId = String(floor.id);
                                this.loadChecklist();
                                return;
                            }
                        }
                    }

                    this.areaId = '';
                },
                loadChecklist() {
                    this.items = [];
                    this.loadError = '';
                    const area = this.selectedArea();
                    if (!area) return;

                    const matchingTemplates = this.templates.filter(template => template.area_type === area.type);
                    this.items = matchingTemplates.flatMap(template => template.items).map(item => ({
                            id: item.id,
                            task_name: item.task_name,
                            status: 'Bersih',
                            notes: '',
                            preview: null,
                            key: `template-${item.id}`,
                        }));
                },
                addItem() {
                    this.items.push({
                        id: null,
                        task_name: '',
                        status: 'Bersih',
                        notes: '',
                        preview: null,
                        key: `manual-${Date.now()}-${Math.random()}`,
                    });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                previewFile(event, item) {
                    const file = event.target.files[0];
                    item.preview = file ? URL.createObjectURL(file) : null;
                },
            };
        }
    </script>
@endsection
