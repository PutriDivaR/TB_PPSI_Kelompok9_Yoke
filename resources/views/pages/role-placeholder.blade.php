<!-- INI HALAMAN SEMENTARA AJA YAAK -->

@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center">
    <div class="w-full max-w-2xl bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-person-circle-question"></i>
        </div>
        <p class="mt-6 text-xs font-bold uppercase tracking-wider text-slate-400">
            Simulasi Aktor · {{ strtoupper($role) }}
        </p>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $title }}</h1>
        <p class="mt-3 text-sm text-slate-500">{{ $description }}</p>
        <div class="mt-6 inline-flex items-center gap-2 rounded-lg bg-slate-50 px-4 py-3 text-xs font-semibold text-slate-500">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            Modul ini sedang disiapkan.
        </div>
    </div>
</div>
@endsection
