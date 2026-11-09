@extends('layouts.app')

@section('title', 'Buat Komplain')
@section('breadcrumb', 'Operasional / Data Komplain / Baru')
@section('heading', 'Buat Tiket Komplain')

@section('content')
    @if ($errors->any())<div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('complaint.store') }}" enctype="multipart/form-data" x-data="{ preview: null }" class="max-w-3xl space-y-6">
        @csrf
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="font-semibold text-slate-900">Informasi pelapor</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><label for="reporter_name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama pelapor</label><input id="reporter_name" name="reporter_name" value="{{ old('reporter_name', auth()->user()->name) }}" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">@error('reporter_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="reporter_contact" class="mb-1.5 block text-sm font-medium text-slate-700">Kontak pelapor (opsional)</label><input id="reporter_contact" name="reporter_contact" value="{{ old('reporter_contact') }}" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">@error('reporter_contact')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="font-semibold text-slate-900">Detail keluhan</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2 grid grid-cols-1 gap-4 md:grid-cols-3" x-data="complaintLocation(@js($buildings), @js(old('area_id')))">
                    <div>
                        <label for="building_id" class="mb-1.5 block text-sm font-medium text-slate-700">Gedung</label>
                        <select id="building_id" x-model="buildingId" @change="floorId = ''; areaId = ''" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">
                            <option value="">Pilih Gedung</option>
                            <template x-for="building in buildings" :key="building.id">
                                <option :value="String(building.id)" x-text="building.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="floor_id" class="mb-1.5 block text-sm font-medium text-slate-700">Lantai</label>
                        <select id="floor_id" x-model="floorId" @change="areaId = ''" :disabled="!buildingId" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="">Pilih Lantai</option>
                            <template x-for="floor in floors" :key="floor.id">
                                <option :value="String(floor.id)" x-text="floor.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="area_id" class="mb-1.5 block text-sm font-medium text-slate-700">Area/Ruangan</label>
                        <select id="area_id" name="area_id" x-model="areaId" required :disabled="!floorId" class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            <option value="">Pilih Area / Ruangan</option>
                            <template x-for="area in areas" :key="area.id">
                                <option :value="String(area.id)" x-text="area.name"></option>
                            </template>
                        </select>
                        @error('area_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div><label for="category_id" class="mb-1.5 block text-sm font-medium text-slate-700">Kategori komplain</label><select id="category_id" name="category_id" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm"><option value="">Pilih kategori</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select>@error('category_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="priority" class="mb-1.5 block text-sm font-medium text-slate-700">Prioritas</label><select id="priority" name="priority" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">@foreach ($priorities as $priority)<option value="{{ $priority }}" @selected(old('priority', 'Sedang') === $priority)>{{ $priority }}</option>@endforeach</select>@error('priority')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label for="photo" class="mb-1.5 block text-sm font-medium text-slate-700">Foto keluhan (opsional)</label><input id="photo" name="photo" type="file" accept="image/*" @change="preview = URL.createObjectURL($event.target.files[0])" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold">@error('photo')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror<template x-if="preview"><img :src="preview" alt="Preview keluhan" class="mt-3 h-32 rounded-xl object-cover"></template></div>
                <div class="sm:col-span-2"><label for="description" class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi keluhan</label><textarea id="description" name="description" rows="5" required class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm">{{ old('description') }}</textarea>@error('description')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            </div>
        </section>
        <div class="flex justify-end gap-3"><a href="{{ route('complaint.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a><button class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700">Buat tiket</button></div>
    </form>

    <script>
        function complaintLocation(buildings, oldAreaId) {
            const location = {
                buildings,
                buildingId: '',
                floorId: '',
                areaId: oldAreaId ? String(oldAreaId) : '',
                get floors() {
                    return this.buildings.find(building => String(building.id) === this.buildingId)?.floors ?? [];
                },
                get areas() {
                    return this.floors.find(floor => String(floor.id) === this.floorId)?.areas ?? [];
                },
                init() {
                    if (!this.areaId) return;

                    for (const building of this.buildings) {
                        for (const floor of building.floors) {
                            if (floor.areas.some(area => String(area.id) === this.areaId)) {
                                this.buildingId = String(building.id);
                                this.floorId = String(floor.id);
                                return;
                            }
                        }
                    }

                    this.areaId = '';
                },
            };

            return location;
        }
    </script>
@endsection
