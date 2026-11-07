@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6">
    <!-- Header Halaman -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-store"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Distributor Regional · {{ $distributor['kode'] }}</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">{{ $distributor['nama'] }} ({{ $distributor['cabang'] }})</h1>
            </div>
        </div>
        <ol class="flex items-center gap-2 text-xs">
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold bg-red-600 text-white ring-4 ring-red-100">1</span>
                <span class="font-bold text-slate-900">Usulan Forecast</span>
                <span class="w-8 h-px bg-slate-300"></span>
            </li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold bg-slate-200 text-slate-500">2</span>
                <span class="text-slate-500">Input E-PO</span>
                <span class="w-8 h-px bg-slate-300"></span>
            </li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold bg-slate-200 text-slate-500">3</span>
                <span class="text-slate-500">Dokumen PO Selesai</span>
            </li>
        </ol>
    </div>

    <!-- Banner Aksi -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-wrap items-center justify-between gap-6">
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-red-600 bg-red-50 px-2.5 py-1 rounded-full">
                <i class="fa-solid fa-calendar-days"></i> Tahap Forecast · FY-2026 · Periode {{ $fc['periode'] }}
            </span>
            <h2 class="mt-3 text-xl font-bold text-slate-900">Mulai Penyusunan Estimasi Kebutuhan Produk</h2>
            <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                Menyiapkan data forecast penjualan sebagai dasar menghitung kebutuhan stok tiap SKU. Sistem akan menghitung rekomendasi volume berdasarkan histori penjualan, stok berjalan, dan tren permintaan di area Anda.
            </p>
        </div>
        <a href="{{ route('e-po.distributor.ajukan-forecast') }}" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-6 py-3 rounded-lg shadow transition">
            <i class="fa-solid fa-calculator"></i> Hitung Forecast
        </a>
    </div>

    <!-- Grid Statistik -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex justify-between items-start">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Status Periode Berjalan</p>
                <i class="fa-regular fa-calendar text-slate-400"></i>
            </div>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">FY-2026</p>
            <p class="text-xs text-slate-500">Siklus pengajuan periode {{ $fc['periode'] }}</p>
            <span class="mt-3 inline-block text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded">● Siklus Baru Terbuka</span>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex justify-between items-start">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Batas Waktu Pengajuan</p>
                <i class="fa-regular fa-clock text-slate-400"></i>
            </div>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">30 Oktober 2026</p>
            <p class="text-xs text-slate-500">Tenggat waktu pukul 17.00 WIB</p>
            <span class="mt-3 inline-block text-[10px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded">● Segera Berakhir (1 hari)</span>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div class="flex justify-between items-start">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Realisasi Dari Periode Lalu</p>
                <i class="fa-solid fa-chart-line text-slate-400"></i>
            </div>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">9.500 <span class="text-sm font-semibold text-slate-500">Karton Terealisasi</span></p>
            <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" style="width:100%"></div></div>
            <p class="mt-1 text-[10px] text-slate-500">Target tercapai 100%</p>
        </div>
    </div>

    <!-- Tabel Riwayat -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 flex items-center justify-between border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-clock-rotate-left text-red-600 mr-2"></i>Riwayat Pengajuan Periode Lalu</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left">ID Forecast</th>
                    <th class="px-5 py-3 text-left">Periode Fiskal</th>
                    <th class="px-5 py-3 text-left">Tanggal Submit</th>
                    <th class="px-5 py-3 text-right">Total Volume</th>
                    <th class="px-5 py-3 text-left">Status Otorisasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($riwayat as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold text-slate-900">{{ $r['kode'] }}</td>
                        <td class="px-5 py-3">{{ $r['periode'] }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $r['tgl'] }}</td>
                        <td class="px-5 py-3 text-right font-semibold">{{ number_format($r['volume'], 0, ',', '.') }} Ctn</td>
                        <td class="px-5 py-3"><span class="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-1 rounded">● {{ $r['status'] }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection