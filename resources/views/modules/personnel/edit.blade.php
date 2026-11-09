@extends('layouts.app')

@section('title', 'Ubah Data Personel')
@section('breadcrumb', 'Operasional / Data Personel / Ubah')
@section('heading', 'Ubah Data Personel')

@section('content')
    <div class="mb-6">
        <p class="text-sm text-slate-500">Perbarui informasi {{ $personnel->name }}.</p>
    </div>
    @include('modules.personnel._form')
@endsection
