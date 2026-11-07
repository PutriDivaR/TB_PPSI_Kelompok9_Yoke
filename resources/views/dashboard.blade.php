@extends('layouts.app')

@section('title', 'Dashboard Monitoring')
@section('page_heading', 'Dashboard Ringkasan Operasional')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <!-- Stat Cards -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500 font-medium">E-PO Aktif</div>
            <div class="text-2xl font-bold text-gray-800">12 PO</div>
        </div>
        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center text-xl">
            <i class="fa-solid fa-file-invoice"></i>
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500 font-medium">Kontainer Dalam Perjalanan</div>
            <div class="text-2xl font-bold text-amber-600">8 Kontainer</div>
        </div>
        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-lg flex items-center justify-center text-xl">
            <i class="fa-solid fa-ship"></i>
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500 font-medium">Klaim Pending (BAK)</div>
            <div class="text-2xl font-bold text-red-600">3 Klaim</div>
        </div>
        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-lg flex items-center justify-center text-xl">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <div class="text-sm text-gray-500 font-medium">Sisa Kuota Forecast (Sumbar)</div>
            <div class="text-2xl font-bold text-emerald-600">45 Kontainer</div>
        </div>
        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-lg flex items-center justify-center text-xl">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>
</div>

<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <h2 class="text-lg font-bold text-gray-800 mb-2">Selamat Datang di Portal PT Yoke Food Industries</h2>
    <p class="text-gray-600 text-sm">Base Layout Aplikasi Integrated Supply Chain & Sales Monitoring System telah berhasil disiapkan dan siap digunakan untuk pengembangan modul.</p>
</div>
@endsection