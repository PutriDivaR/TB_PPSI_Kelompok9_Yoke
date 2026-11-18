@extends('layouts.app')

@section('title', 'Penetapan Kuota')

@section('content')

<div class="p-6 space-y-6"
     x-data="{
        rows: {{ \Illuminate\Support\Js::from($baris) }},
        kapasitas: {{ $kapasitas }},
        sisaKapasitas: {{ $sisaKapasitas }},
        fmt(n) { return new Intl.NumberFormat('id-ID').format(n) },
        stat(r) { const k = Number(r.kuota || 0); return k === r.usulan ? 'ok' : (k > r.usulan ? 'lebih' : 'kurang') },
        get usulan() { return this.rows.reduce((a, r) => a + r.usulan, 0) },
        get kuotaTotal() { return this.rows.reduce((a, r) => a + Number(r.kuota || 0), 0) },
        get selisih() { return this.kuotaTotal - this.usulan },
        get nilai() { return this.rows.reduce((a, r) => a + Number(r.kuota || 0) * r.kpk * r.harga, 0) },
        get melebihi() { return this.kuotaTotal > this.sisaKapasitas }
     }">

    

    

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Distributor Pemohon</p>
            <p class="mt-1 font-bold text-slate-900">{{ $forecast->distributor->nama }}</p>
            <p class="text-[11px] text-slate-500">{{ $forecast->distributor->wilayah->nama_wilayah }} - MoU {{ $forecast->distributor->nomor_mou ?? '-' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Periode Forecast</p>
            <p class="mt-1 font-bold text-slate-900">{{ $tanggalEpo($forecast->periode, 'F Y') }}</p>
            <p class="text-[11px] text-slate-500">Diajukan {{ $tanggalEpo($forecast->tanggal_pengajuan, 'd M Y, H:i') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Sisa Kapasitas Pabrik</p>
            <p class="mt-1 font-bold text-slate-900">{{ number_format($sisaKapasitas, 0, ',', '.') }} Kontainer</p>
            <p class="text-[11px] text-slate-500">Dari {{ number_format($kapasitas, 0, ',', '.') }} kontainer (setelah kuota distributor lain)</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Status</p>
            <div class="mt-2">
                <span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $forecast->status === 'Diproses' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">
                    {{ $forecast->status === 'Diproses' ? 'Sedang diproses' : 'Disetujui' }}
                </span>
            </div>
            @if ($forecast->status === 'Disetujui')
                <p class="text-[11px] text-slate-500 mt-1">{{ $tanggalEpo($forecast->tanggal_disetujui, 'd M Y') }} oleh {{ $forecast->penyetuju?->nama }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Usulan Distributor</p>
            <p class="mt-2 text-3xl font-extrabold"><span x-text="fmt(usulan)"></span> <span class="text-xs text-slate-500">Ktr</span></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Sisa Kapasitas</p>
            <p class="mt-2 text-3xl font-extrabold">{{ number_format($sisaKapasitas, 0, ',', '.') }} <span class="text-xs text-slate-500">Ktr</span></p>
        </div>
        <div class="rounded-xl border p-4 shadow-sm" :class="melebihi ? 'bg-red-50 border-red-300' : 'bg-emerald-50 border-emerald-200'">
            <p class="text-[10px] font-bold uppercase tracking-wider" :class="melebihi ? 'text-red-600' : 'text-emerald-700'">Alokasi Kuota Resmi</p>
            <p class="mt-2 text-3xl font-extrabold"><span x-text="fmt(kuotaTotal)"></span> <span class="text-xs text-slate-500">Ktr</span></p>
            <p class="text-[10px]" :class="melebihi ? 'text-red-600' : 'text-emerald-700'" x-text="melebihi ? 'Melebihi sisa kapasitas!' : 'Dalam kapasitas pabrik'"></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Penyesuaian Kuota</p>
            <p class="mt-2 text-3xl font-extrabold" :class="selisih < 0 ? 'text-red-600' : 'text-slate-900'"><span x-text="(selisih > 0 ? '+' : '') + fmt(selisih)"></span> <span class="text-xs text-slate-500">Ktr</span></p>
            <p class="text-[10px] text-slate-500">Nilai kuota Rp <span x-text="fmt(nilai)"></span></p>
        </div>
    </div>

    <form method="POST" action="{{ route('e-po.logistik.tetapkan-kuota', $forecast) }}" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <div class="px-5 py-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-table-list text-red-600 mr-2"></i>Tabel Evaluasi & Penetapan Kuota Resmi per SKU</h3>
            </div>
            <table class="w-full text-sm min-w-[900px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode SKU</th>
                        <th class="px-4 py-3 text-left">Nama Produk</th>
                        <th class="px-4 py-3 text-right">Usulan Distributor</th>
                        <th class="px-4 py-3 text-center">Kuota Resmi (Kontainer)</th>
                        <th class="px-4 py-3 text-right">Nilai Kuota</th>
                        <th class="px-4 py-3 text-center">Status Penetapan</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in rows" :key="r.id">
                        <tr class="border-t border-slate-100" :class="stat(r) !== 'ok' ? 'bg-red-50/60' : ''">
                            <td class="px-4 py-3 font-mono text-[11px] font-bold text-red-600" x-text="r.kode"></td>
                            <td class="px-4 py-3"><p class="font-semibold" x-text="r.nama"></p><p class="text-[10px] text-slate-500"><span x-text="fmt(r.kpk)"></span> karton/kontainer</p></td>
                            <td class="px-4 py-3 text-right font-semibold"><span x-text="fmt(r.usulan)"></span> ktr</td>
                            <td class="px-4 py-3 text-center">
                                <input type="number" min="0" step="1" x-model.number="r.kuota" :name="`kuota[${r.id}]`" @if (! $editable) readonly @endif
                                       class="w-24 text-center font-bold text-sm border rounded py-1.5 focus:ring-red-500 focus:border-red-500"
                                       :class="stat(r) !== 'ok' ? 'border-red-400' : 'border-slate-200'">
                            </td>
                            <td class="px-4 py-3 text-right" x-text="'Rp ' + fmt(Number(r.kuota || 0) * r.kpk * r.harga)"></td>
                            <td class="px-4 py-3 text-center">
                                <span x-show="stat(r)==='ok'" class="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-1 rounded">Sesuai usulan</span>
                                <span x-show="stat(r)==='kurang'" style="display:none" class="text-[10px] font-bold bg-red-600 text-white px-2 py-1 rounded">Penyesuaian Kuota</span>
                                <span x-show="stat(r)==='lebih'" style="display:none" class="text-[10px] font-bold bg-amber-500 text-white px-2 py-1 rounded">Di atas Usulan</span>
                            </td>
                            <td class="px-4 py-3 text-[11px] text-slate-600">
                                <span x-show="stat(r)==='ok'">Kuota sama dengan usulan.</span>
                                <span x-show="stat(r)==='kurang'" style="display:none" class="text-red-600">Dikurangi <b x-text="fmt(r.usulan - r.kuota)"></b> kontainer dari usulan.</span>
                                <span x-show="stat(r)==='lebih'" style="display:none" class="text-amber-700">Kuota ditetapkan di atas usulan; tetap dalam kapasitas pabrik.</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-slate-50 font-bold">
                    <tr class="border-t border-slate-200">
                        <td colspan="2" class="px-4 py-3 text-xs uppercase">Total Akumulasi ({{ count($baris) }} SKU)</td>
                        <td class="px-4 py-3 text-right"><span x-text="fmt(usulan)"></span> ktr</td>
                        <td class="px-4 py-3 text-center text-red-600"><span x-text="fmt(kuotaTotal)"></span> ktr</td>
                        <td class="px-4 py-3 text-right">Rp <span x-text="fmt(nilai)"></span></td>
                        <td class="px-4 py-3 text-center text-[10px]" :class="melebihi ? 'text-red-600' : 'text-emerald-600'" x-text="melebihi ? 'PERLU DIPERBAIKI' : 'SIAP DITETAPKAN'"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('e-po.logistik.antrean') }}" class="px-4 py-2.5 text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Daftar Forecast</a>
            @if ($editable)
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" name="aksi" value="draft" class="px-4 py-2.5 text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50"><i class="fa-regular fa-floppy-disk mr-1"></i> Simpan Draft Penetapan</button>
                    <button type="submit" name="aksi" value="setujui" :disabled="melebihi || kuotaTotal < 1"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:bg-slate-300 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-lock mr-1"></i> Setujui & Tetapkan Kuota
                    </button>
                </div>
            @else
                <span class="text-xs text-slate-500"><i class="fa-solid fa-lock mr-1"></i>Kuota sudah ditetapkan dan tidak dapat diubah.</span>
            @endif
        </div>
    </form>
</div>
@endsection
