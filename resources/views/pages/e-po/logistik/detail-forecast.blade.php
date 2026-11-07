@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6" 
     x-data="{
        rows: {{ \Illuminate\Support\Js::from($skus) }}.map(r => ({...r, tetap: r.kuota})),
        get kuotaTotal() { return this.rows.reduce((a, r) => a + Number(r.tetap || 0), 0) },
        fmt(n) { return new Intl.NumberFormat('id-ID').format(n) }
     }">

    <!-- Header -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex justify-between items-center">
        <div>
            <p class="text-[10px] font-bold uppercase text-slate-500">Evaluasi Logistik</p>
            <h1 class="text-lg font-bold text-slate-900">Penetapan Kuota Resmi: {{ $distributor['nama'] }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ route('e-po.logistik.tetapkan-kuota', $id) }}" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-[10px] uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode & Nama SKU</th>
                        <th class="px-4 py-3 text-right">Usulan Distributor</th>
                        <th class="px-4 py-3 text-center">Penetapan Kuota Resmi (Ctn)</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="r in rows" :key="r.kode">
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <p class="font-semibold" x-text="r.nama"></p>
                                <p class="text-[10px] font-mono text-red-600" x-text="r.kode"></p>
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-slate-700" x-text="fmt(r.usulan) + ' Ctn'"></td>
                            <td class="px-4 py-3 text-center">
                                <input type="number" min="0" x-model.number="r.tetap" :name="`kuota[${r.kode}]`" class="w-24 text-center font-bold text-sm border rounded py-1.5 focus:ring-red-500">
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <span x-show="r.tetap == r.usulan">Kuota sesuai usulan.</span>
                                <span x-show="r.tetap < r.usulan" class="text-red-600 font-semibold">Penyesuaian stok lini produksi.</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center bg-white p-4 rounded-xl border shadow-sm">
            <div>
                <p class="text-xs text-slate-500">Total Akumulasi Kuota Ditetapkan:</p>
                <p class="text-xl font-extrabold text-slate-900"><span x-text="fmt(kuotaTotal)"></span> Karton</p>
            </div>
            <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700">
                <i class="fa-solid fa-lock mr-1"></i> Simpan & Kunci Kuota
            </button>
        </div>
    </form>
</div>
@endsection