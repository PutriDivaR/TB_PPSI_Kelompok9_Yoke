@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6">
    <!-- Stat Banner -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Registrasi PO</p>
            <p class="text-xl font-extrabold text-slate-900">{{ $po['no_po'] }}</p>
            <span class="mt-2 inline-block text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-1 rounded">● Disetujui Logistik</span>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Nilai Pesanan</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">Rp {{ number_format($po['total'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Kode Integrasi SAP</p>
            <p class="mt-2 text-xl font-extrabold text-red-600">{{ $po['no_sap'] }}</p>
        </div>
    </div>

    <!-- Pratinjau Dokumen Cetak -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-4xl mx-auto p-8 text-slate-800">
        <div class="flex justify-between items-start border-b-2 border-slate-800 pb-4">
            <div>
                <p class="text-base font-extrabold">{{ $pabrik['nama'] }}</p>
                <p class="text-[10px] text-slate-500 max-w-xs">{{ $pabrik['alamat'] }}</p>
            </div>
            <div class="text-right">
                <span class="text-[10px] font-bold bg-red-600 text-white px-2 py-1 rounded">PURCHASE ORDER</span>
                <p class="mt-2 text-lg font-extrabold text-red-600">{{ $po['no_po'] }}</p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-5 text-xs">
            <div>
                <p class="text-[10px] font-bold uppercase text-slate-500 mb-1">Pemesan:</p>
                <p class="font-bold text-sm">{{ $distributor['nama'] }}</p>
                <p class="text-slate-600">{{ $distributor['alamat'] }}</p>
            </div>
            <div>
                <p class="text-[10px] font-bold uppercase text-slate-500 mb-1">Pengiriman:</p>
                <p><b>Tanggal Kirim:</b> {{ $po['kirim'] }}</p>
                <p><b>Term of Payment:</b> {{ $po['top'] }}</p>
            </div>
        </div>

        <table class="w-full text-xs mt-6 border border-slate-200">
            <thead class="bg-slate-100 uppercase text-[10px]">
                <tr>
                    <th class="px-3 py-2 text-left">No</th>
                    <th class="px-3 py-2 text-left">Deskripsi SKU</th>
                    <th class="px-3 py-2 text-right">Qty</th>
                    <th class="px-3 py-2 text-right">Harga Satuan</th>
                    <th class="px-3 py-2 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach ($po['rows'] as $i => $r)
                    <tr>
                        <td class="px-3 py-2">{{ $i + 1 }}</td>
                        <td class="px-3 py-2"><p class="font-semibold">{{ $r['nama'] }}</p><p class="text-[10px] text-slate-500">{{ $r['kode'] }}</p></td>
                        <td class="px-3 py-2 text-right font-semibold">{{ number_format($r['qty'], 0, ',', '.') }} Ctn</td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($r['harga'], 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right font-semibold">Rp {{ number_format($r['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-between items-center mt-6 text-xs border-t pt-4">
            <p class="text-slate-500">Dokumen sah terbit otomatis via SAP Sistem.</p>
            <p class="text-base font-extrabold text-red-600">Total: Rp {{ number_format($po['total'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="flex justify-center gap-3">
        <button onclick="window.print()" class="px-6 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700">
            <i class="fa-solid fa-print mr-1"></i> Cetak Dokumen E-PO
        </button>
    </div>
</div>
@endsection