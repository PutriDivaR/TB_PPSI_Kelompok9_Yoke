@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" 
     x-data="{
        rows: {{ \Illuminate\Support\Js::from($skus) }}.map(r => ({...r, input: r.input ?? r.kuota})),
        plafon: {{ $fc['kuota'] }},
        fmt(n) { return new Intl.NumberFormat('id-ID').format(n) },
        over(r) { return Number(r.input) > r.kuota },
        get total() { return this.rows.reduce((a, r) => a + Number(r.input || 0), 0) },
        get nilai() { return this.rows.reduce((a, r) => a + Number(r.input || 0) * r.harga, 0) },
        get overCount() { return this.rows.filter(r => this.over(r)).length }
     }">

    <!-- Header -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Distributor Regional · {{ $distributor['kode'] }}</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">Input Form Pemesanan E-PO</h1>
            </div>
        </div>
        <ol class="flex items-center gap-2 text-xs">
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold bg-emerald-500 text-white"><i class="fa-solid fa-check text-[10px]"></i></span>
                <span class="text-slate-500">Usulan Forecast</span>
                <span class="w-8 h-px bg-slate-300"></span>
            </li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold bg-red-600 text-white ring-4 ring-red-100">2</span>
                <span class="font-bold text-slate-900">Input E-PO</span>
                <span class="w-8 h-px bg-slate-300"></span>
            </li>
            <li class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold bg-slate-200 text-slate-500">3</span>
                <span class="text-slate-500">Dokumen PO Selesai</span>
            </li>
        </ol>
    </div>

    <!-- Banner Session Error (Jika Diredirect dari Backend) -->
    @if (session('error'))
        <div class="rounded-lg bg-red-50 border border-red-300 text-red-700 text-sm px-4 py-3 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Alert / Peringatan Realtime Alpine JS -->
    <div x-show="overCount > 0" x-cloak class="rounded-xl border border-red-300 bg-red-50 p-4 flex gap-3 text-red-700 text-xs">
        <i class="fa-solid fa-circle-exclamation text-lg mt-0.5"></i>
        <div>
            <p class="font-bold text-sm">Validasi Gagal — Kuantitas Pesanan Melebihi Kuota Resmi!</p>
            <p class="mt-0.5">Terdapat <b x-text="overCount"></b> SKU yang diinput melebihi kuota. Harap sesuaikan kuantitas agar sesuai dengan batas kuota sebelum dikirim.</p>
        </div>
    </div>

    <!-- Ringkasan Kuota -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Plafon Kuota Resmi</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($fc['kuota'], 0, ',', '.') }} <span class="text-xs text-slate-500">Ctn</span></p>
        </div>
        <div class="bg-white rounded-xl border p-4 shadow-sm" :class="overCount ? 'bg-red-50 border-red-300' : 'border-slate-200'">
            <p class="text-[10px] font-bold uppercase tracking-wider" :class="overCount ? 'text-red-600' : 'text-slate-500'">Total Kuantitas Pesanan</p>
            <p class="mt-2 text-2xl font-extrabold" :class="overCount ? 'text-red-600' : 'text-slate-900'"><span x-text="fmt(total)"></span> <span class="text-xs text-slate-500">Ctn</span></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Nilai E-PO</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">Rp <span x-text="fmt(nilai)"></span></p>
        </div>
    </div>

    <!-- Form Tabel Input -->
    <form method="POST" action="{{ route('e-po.distributor.submit-epo') }}" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode & Nama SKU</th>
                        <th class="px-4 py-3 text-right">Batas Kuota</th>
                        <th class="px-4 py-3 text-center">Kuantitas Pesanan (Ctn)</th>
                        <th class="px-4 py-3 text-right">Harga Satuan</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                        <th class="px-4 py-3 text-center">Status Validasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="r in rows" :key="r.kode">
                        <tr class="border-t border-slate-100" :class="over(r) ? 'bg-red-50/70' : 'hover:bg-slate-50'">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900" x-text="r.nama"></p>
                                <p class="text-[10px] font-mono text-red-600" x-text="r.kode"></p>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-700" x-text="fmt(r.kuota) + ' Ctn'"></td>
                            <td class="px-4 py-3 text-center">
                                <input type="number" min="0" x-model.number="r.input" :name="`qty[${r.kode}]`"
                                       class="w-24 text-center font-bold text-sm rounded py-1.5 border"
                                       :class="over(r) ? 'border-red-500 text-red-600 bg-white focus:ring-red-500' : 'border-slate-200 focus:ring-red-500'">
                                <p x-show="over(r)" x-cloak class="mt-1 text-[10px] font-bold text-red-600">
                                    Over <span x-text="fmt(r.input - r.kuota)"></span> Ctn
                                </p>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600" x-text="'Rp ' + fmt(r.harga)"></td>
                            <td class="px-4 py-3 text-right font-semibold" x-text="'Rp ' + fmt(r.input * r.harga)"></td>
                            <td class="px-4 py-3 text-center">
                                <span x-show="!over(r)" class="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-1 rounded">● Sesuai Kuota</span>
                                <span x-show="over(r)" x-cloak class="text-[10px] font-bold bg-red-600 text-white px-2 py-1 rounded"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Over Kuota</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end gap-3">
            <button type="submit" :disabled="overCount > 0" class="px-6 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:bg-slate-300 disabled:cursor-not-allowed">
                <i class="fa-solid fa-paper-plane mr-1"></i> Kirim E-PO Ke Logistik
            </button>
        </div>
    </form>
</div>
@endsection