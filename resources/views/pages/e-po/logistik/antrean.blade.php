@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" x-data="{ tab: 'forecast' }">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex justify-between items-center">
        <div>
            <p class="text-[10px] font-bold uppercase text-slate-500">Logistik Pusat</p>
            <h1 class="text-lg font-bold text-slate-900">Antrean Pengajuan Forecast & E-PO Masuk</h1>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase text-slate-500">Pending Forecast</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $stats['forecast_pending'] }} <span class="text-xs text-slate-500">Pengajuan</span></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase text-slate-500">E-PO Perlu Verifikasi</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $stats['epo_verifikasi'] }} <span class="text-xs text-slate-500">Dokumen</span></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase text-slate-500">Utilisasi Pabrik</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-600">{{ $stats['utilisasi'] }}%</p>
        </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 pt-4 flex gap-6 text-sm border-b border-slate-100">
            <button @click="tab='forecast'" class="pb-3 font-bold border-b-2" :class="tab==='forecast' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500'">Antrean Forecast</button>
            <button @click="tab='epo'" class="pb-3 font-bold border-b-2" :class="tab==='epo' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500'">Antrean E-PO Masuk</button>
        </div>

        <div x-show="tab==='forecast'">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Distributor</th>
                        <th class="px-5 py-3 text-right">Volume Usulan</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($forecasts as $f)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold">{{ $f['distributor'] }} ({{ $f['area'] }})</td>
                            <td class="px-5 py-3 text-right font-bold">{{ number_format($f['volume'], 0, ',', '.') }} Ctn</td>
                            <td class="px-5 py-3"><span class="text-[10px] font-bold bg-amber-50 text-amber-700 px-2 py-1 rounded">● {{ $f['status'] }}</span></td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('e-po.logistik.detail-forecast', $f['id']) }}" class="text-xs font-bold text-white bg-red-600 px-3 py-1.5 rounded hover:bg-red-700">Tinjau & Tetapkan</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div x-show="tab==='epo'" x-cloak>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">No. E-PO</th>
                        <th class="px-5 py-3 text-left">Distributor</th>
                        <th class="px-5 py-3 text-right">Nilai PO</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($epos as $e)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-bold text-red-600">{{ $e['no'] }}</td>
                            <td class="px-5 py-3">{{ $e['distributor'] }}</td>
                            <td class="px-5 py-3 text-right font-bold">Rp {{ number_format($e['nilai'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('e-po.logistik.verifikasi-epo', $e['id']) }}" class="text-xs font-bold text-white bg-red-600 px-3 py-1.5 rounded hover:bg-red-700">Verifikasi E-PO</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection