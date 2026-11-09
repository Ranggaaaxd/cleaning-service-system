@extends('layouts.app')

@section('title', 'Tambah Personel')
@section('breadcrumb', 'Operasional / Data Personel / Tambah')
@section('heading', 'Tambah Personel')

@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Lengkapi data petugas baru untuk ditambahkan ke sistem.</p>
    </div>
    @include('modules.personnel._form')
@endsection
