@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" x-data="{ cekKredit: false }">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex justify-between items-center">
        <div>
            <p class="text-[10px] font-bold uppercase text-slate-500">Verifikasi Logistik</p>
            <h1 class="text-lg font-bold text-slate-900">Verifikasi Dokumen E-PO: {{ $po['no_po'] }}</h1>
        </div>
    </div>

    <!-- Ringkasan Detail -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
        <div class="grid grid-cols-2 gap-4 text-xs border-b pb-4">
            <div>
                <p class="text-slate-500 uppercase font-bold text-[10px]">Distributor Pemesan</p>
                <p class="font-bold text-sm text-slate-900">{{ $distributor['nama'] }}</p>
            </div>
            <div>
                <p class="text-slate-500 uppercase font-bold text-[10px]">Total Nilai E-PO</p>
                <p class="font-extrabold text-sm text-red-600">Rp {{ number_format($po['total'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Tabel SKU -->
        <table class="w-full text-xs">
            <thead class="bg-slate-50 uppercase text-slate-500">
                <tr>
                    <th class="p-2 text-left">SKU</th>
                    <th class="p-2 text-right">Kuantitas</th>
                    <th class="p-2 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($po['rows'] as $r)
                    <tr>
                        <td class="p-2 font-semibold">{{ $r['nama'] }}</td>
                        <td class="p-2 text-right">{{ number_format($r['qty'], 0, ',', '.') }} Ctn</td>
                        <td class="p-2 text-right font-semibold">Rp {{ number_format($r['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Checkbox Verifikasi & Form Submit -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
        <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700">
            <input type="checkbox" x-model="cekKredit" class="rounded text-red-600 focus:ring-red-500">
            Saya mengonfirmasi bahwa status kredit & jadwal armada pengiriman aman.
        </label>

        <form method="POST" action="{{ route('e-po.logistik.setujui-epo', $id) }}" class="flex justify-end">
            @csrf
            <button type="submit" :disabled="!cekKredit" class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:bg-slate-300 disabled:cursor-not-allowed">
                <i class="fa-solid fa-stamp mr-1"></i> Setujui E-PO & Terbitkan Kode SAP
            </button>
        </form>
    </div>
</div>
@endsection