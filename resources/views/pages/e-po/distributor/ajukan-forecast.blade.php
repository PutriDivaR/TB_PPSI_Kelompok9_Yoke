@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" 
     x-data="{
        rows: {{ \Illuminate\Support\Js::from($skus) }}.map(r => ({...r, input: r.usulan})),
        agree: false,
        get total() { return this.rows.reduce((a, r) => a + Number(r.input || 0), 0) },
        get nilai() { return this.rows.reduce((a, r) => a + Number(r.input || 0) * r.harga, 0) },
        get kapasitas() { return {{ $fc['kapasitas'] }} },
        get pct() { return Math.min(100, Math.round(this.total / this.kapasitas * 100)) },
        fmt(n) { return new Intl.NumberFormat('id-ID').format(n) },
        step(r, d) { r.input = Math.max(0, Number(r.input) + d) }
     }">

    <!-- Header -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-calculator"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Distributor Regional · {{ $distributor['kode'] }}</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">Formulir Usulan Forecast {{ $fc['periode'] }}</h1>
            </div>
        </div>
    </div>

    <!-- Ringkasan Angka -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Rekomendasi Sistem</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($fc['rekomendasi'], 0, ',', '.') }} <span class="text-xs text-slate-500">Ctn</span></p>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-red-600">Total Usulan Distributor</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900"><span x-text="fmt(total)"></span> <span class="text-xs text-slate-500">Ctn</span></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Estimasi Nilai Pesanan</p>
            <p class="mt-2 text-xl font-extrabold text-slate-900">Rp <span x-text="fmt(nilai)"></span></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Kapasitas Pabrik</p>
            <p class="mt-2 text-xl font-extrabold text-slate-900">{{ number_format($fc['kapasitas'], 0, ',', '.') }} <span class="text-xs text-slate-500">Ctn</span></p>
        </div>
    </div>

    <!-- Form & Tabel Input -->
    <form method="POST" action="{{ route('e-po.distributor.submit-forecast') }}" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode SKU</th>
                        <th class="px-4 py-3 text-left">Nama Produk</th>
                        <th class="px-4 py-3 text-right">Rekomendasi</th>
                        <th class="px-4 py-3 text-center">Usulan Distributor (Ctn)</th>
                        <th class="px-4 py-3 text-left">Alasan Penyesuaian</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="r in rows" :key="r.kode">
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-[11px] font-bold text-red-600" x-text="r.kode"></td>
                            <td class="px-4 py-3 font-semibold text-slate-900" x-text="r.nama"></td>
                            <td class="px-4 py-3 text-right text-slate-600" x-text="fmt(r.rekomendasi) + ' Ctn'"></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" @click="step(r, -100)" class="w-7 h-7 rounded border border-slate-200 hover:bg-slate-100"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                    <input type="number" x-model.number="r.input" :name="`usulan[${r.kode}]`" class="w-20 text-center text-sm font-bold border border-slate-200 rounded py-1">
                                    <button type="button" @click="step(r, 100)" class="w-7 h-7 rounded border border-slate-200 hover:bg-slate-100"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                </div>
                            </td>
                            <td class="px-4 py-3"><input type="text" :name="`alasan[${r.kode}]`" :value="r.alasan" class="w-full text-xs border border-slate-200 rounded px-2 py-1.5"></td>
                            <td class="px-4 py-3 text-right font-semibold" x-text="'Rp ' + fmt(r.input * r.harga)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" x-model="agree" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                <span class="text-xs text-slate-600">Saya mengonfirmasi bahwa data forecast di atas sudah sesuai dengan kebutuhan cabang.</span>
            </label>
            <button type="submit" :disabled="!agree" class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-40">
                <i class="fa-solid fa-paper-plane mr-1"></i> Submit Forecast
            </button>
        </div>
    </form>
</div>
@endsection