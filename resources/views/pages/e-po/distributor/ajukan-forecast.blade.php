@extends('layouts.app')

@section('title', 'Ajukan Forecast')

@section('content')

<div class="p-6 space-y-6"
     x-data="{
        rows: {{ \Illuminate\Support\Js::from($baris) }},
        q: '',
        kapasitas: {{ $kapasitas }},
        agree: false,
        editable: {{ $editable ? 'true' : 'false' }},
        fmt(n) { return new Intl.NumberFormat('id-ID').format(n) },
        step(r, d) { r.usulan = Math.max(0, Number(r.usulan || 0) + d) },
        add(r) { r.selected = true; r.usulan = r.rekomendasi },
        remove(r) { r.selected = false; r.usulan = 0 },
        get selectedRows() { return this.rows.filter(r => r.selected) },
        get searchRows() { return this.rows.filter(r => !r.selected && (r.kode + ' ' + r.nama).toLowerCase().includes(this.q.toLowerCase())) },
        get totalRek() { return this.selectedRows.reduce((a, r) => a + r.rekomendasi, 0) },
        get total() { return this.selectedRows.reduce((a, r) => a + Number(r.usulan || 0), 0) },
        get karton() { return this.selectedRows.reduce((a, r) => a + Number(r.usulan || 0) * r.kpk, 0) },
        get nilai() { return this.selectedRows.reduce((a, r) => a + Number(r.usulan || 0) * r.kpk * r.harga, 0) },
        get selisih() { return this.totalRek > 0 ? ((this.total / this.totalRek - 1) * 100).toFixed(1) : null },
        get pct() { return Math.min(100, Math.round(this.total / this.kapasitas * 100)) }
     }">

    

    

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Formulir Usulan & Penyesuaian Forecast Bulanan</h2>
            <p class="text-xs text-slate-500">Periode forecast: {{ $tanggalEpo($periode, 'F Y') }} - batas pengajuan {{ $tanggalEpo($tenggat, 'd M Y, H.i') }} WIB</p>
        </div>
        @if ($forecast)
            @php
                $status = match ($forecast->status ?? 'Draft') {
                    'Draft' => ['Draft', 'bg-slate-100 text-slate-700'],
                    'Diajukan' => ['Menunggu penetapan', 'bg-amber-50 text-amber-700'],
                    'Diproses' => ['Sedang diproses', 'bg-blue-50 text-blue-700'],
                    'Disetujui' => ['Disetujui', 'bg-emerald-50 text-emerald-700'],
                    default => [$forecast->status ?? 'Forecast baru', 'bg-slate-100 text-slate-700'],
                };
            @endphp
            <span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $status[1] }}">{{ $status[0] }}</span>
        @endif
    </div>

    @unless ($editable)
        <div class="rounded-lg bg-slate-50 border border-slate-200 text-slate-600 text-xs px-4 py-3">
            <i class="fa-solid fa-lock mr-2"></i>Forecast ini sudah diajukan sehingga ditampilkan hanya-baca.
            <a href="{{ route('e-po.distributor.hitung-forecast') }}" class="font-bold text-red-600 hover:underline ml-1">Kembali ke Hitung Forecast</a>
        </div>
    @endunless

    <form method="POST" action="{{ route('e-po.distributor.submit-forecast') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="periode" value="{{ $periode->format('Y-m') }}">

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Rekomendasi Sistem</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900"><span x-text="fmt(totalRek)"></span> <span class="text-xs text-slate-500">Kontainer</span></p>
                <p class="text-[10px] text-slate-500">Rata-rata Sell-Out {{ $jumlahBulan }} bulan + stok pengaman</p>
            </div>
            <div class="bg-red-50 rounded-xl border border-red-200 p-4 shadow-sm">
                <div class="flex justify-between items-start">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-red-600">Total Usulan Distributor</p>
                    <span x-show="selisih !== null && Number(selisih) !== 0" style="display:none" class="text-[10px] font-bold bg-red-600 text-white px-1.5 rounded"
                          x-text="(selisih > 0 ? '+' : '') + selisih + '%'"></span>
                </div>
                <p class="mt-2 text-2xl font-extrabold text-slate-900"><span x-text="fmt(total)"></span> <span class="text-xs text-slate-500">Kontainer</span></p>
                <p class="text-[10px] text-red-600">Setara <span x-text="fmt(karton)"></span> karton</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Estimasi Nilai Pesanan</p>
                <p class="mt-2 text-xl font-extrabold text-slate-900">Rp <span x-text="fmt(nilai)"></span></p>
                <p class="text-[10px] text-slate-500">Harga produk x karton</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Kapasitas Pabrik / Bulan</p>
                <p class="mt-2 text-xl font-extrabold text-slate-900">{{ number_format($kapasitas, 0, ',', '.') }} <span class="text-xs text-slate-500">Kontainer</span></p>
                <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden"><div class="h-full rounded-full" :class="pct > 60 ? 'bg-amber-500' : 'bg-emerald-500'" :style="`width:${pct}%`"></div></div>
                <p class="text-[10px] text-slate-500 mt-1">Usulan Anda = <span x-text="pct"></span>% kapasitas</p>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
            <div class="px-5 py-4 border-b border-slate-100">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Rincian SKU Usulan Forecast
                            <span class="ml-2 text-[10px] bg-slate-100 px-2 py-0.5 rounded"><span x-text="selectedRows.length"></span> Produk Dipilih</span>
                        </h3>
                        <p class="text-[11px] text-slate-500">Kebutuhan dihitung dalam karton, lalu dibulatkan ke kemasan kontainer. Satuan usulan tetap kontainer sesuai kapasitas pabrik.</p>
                    </div>
                    @if ($editable)
                        <div class="relative min-w-[280px]">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-xs text-slate-400"></i>
                            <input type="search" x-model="q" placeholder="Cari kode atau nama produk..." class="w-full rounded-lg border border-slate-300 py-2 pl-8 pr-3 text-xs">
                        </div>
                    @endif
                </div>
                @if ($editable)
                    <div x-show="q.length > 0" class="mt-3 max-h-52 overflow-y-auto rounded-lg border border-slate-200 divide-y divide-slate-100">
                        <template x-for="r in searchRows" :key="'catalog-'+r.id">
                            <button type="button" @click="add(r); q=''" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-slate-50">
                                <span>
                                    <span class="font-mono text-[11px] font-bold text-red-600" x-text="r.kode"></span>
                                    <span class="ml-2 text-xs font-semibold text-slate-900" x-text="r.nama"></span>
                                </span>
                                <span class="text-[10px] font-bold text-emerald-700">Tambah - rekomendasi <span x-text="fmt(r.rekomendasi)"></span> ktr</span>
                            </button>
                        </template>
                        <p x-show="searchRows.length === 0" class="px-3 py-3 text-xs text-slate-500">Tidak ada produk yang cocok atau produk sudah dipilih.</p>
                    </div>
                @endif
            </div>
            <table class="w-full text-sm min-w-[950px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode SKU</th>
                        <th class="px-4 py-3 text-left">Nama Produk</th>
                        <th class="px-4 py-3 text-right">Rata-rata Sell-Out / Bln</th>
                        <th class="px-4 py-3 text-right">Stok Layak Jual</th>
                        <th class="px-4 py-3 text-right">Rekomendasi</th>
                        <th class="px-4 py-3 text-center">Usulan (Kontainer)</th>
                        <th class="px-4 py-3 text-right">Setara Karton</th>
                        <th class="px-4 py-3 text-right">Estimasi Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="r in selectedRows" :key="r.id">
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-[11px] font-bold text-red-600" x-text="r.kode"></td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900" x-text="r.nama"></p>
                                <p class="text-[10px] text-slate-500"><span x-text="r.kategori || '-'"></span> - <span x-text="fmt(r.kpk)"></span> karton/kontainer</p>
                                <button type="button" x-show="editable" @click="remove(r)" class="mt-1 text-[10px] font-semibold text-slate-400 hover:text-red-600">
                                    <i class="fa-solid fa-xmark mr-1"></i>Hapus dari pilihan
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600"><span x-text="fmt(r.rata_rata)"></span> krt</td>
                            <td class="px-4 py-3 text-right text-slate-600"><span x-text="fmt(r.stok)"></span> krt</td>
                            <td class="px-4 py-3 text-right font-semibold">
                                <span x-text="fmt(r.rekomendasi)"></span> ktr
                                <p class="text-[10px] font-normal text-slate-500"><span x-text="fmt(r.kebutuhan_karton)"></span> krt kebutuhan</p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" x-show="editable" @click="step(r, -1)" class="w-7 h-7 rounded border border-slate-200 hover:bg-slate-100"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                    <input type="number" min="0" step="1" x-model.number="r.usulan" :name="`usulan[${r.id}]`" :readonly="!editable"
                                           class="w-20 text-center text-sm font-bold border border-slate-200 rounded py-1 focus:ring-red-500 focus:border-red-500"
                                           :class="r.usulan > r.rekomendasi ? 'text-red-600 bg-red-50' : ''">
                                    <button type="button" x-show="editable" @click="step(r, 1)" class="w-7 h-7 rounded border border-slate-200 hover:bg-slate-100"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600" x-text="fmt(Number(r.usulan || 0) * r.kpk)"></td>
                            <td class="px-4 py-3 text-right font-semibold" x-text="'Rp ' + fmt(Number(r.usulan || 0) * r.kpk * r.harga)"></td>
                        </tr>
                    </template>
                    <tr x-show="selectedRows.length === 0">
                        <td colspan="8" class="px-4 py-10 text-center">
                            <i class="fa-solid fa-box-open text-2xl text-slate-300"></i>
                            <p class="mt-2 text-sm font-semibold text-slate-700">Belum ada produk dipilih</p>
                            <p class="mt-1 text-xs text-slate-500">Gunakan pencarian katalog di atas untuk menambahkan SKU.</p>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-slate-900">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-xs uppercase">Total Rekapitulasi Usulan Cabang</td>
                        <td class="px-4 py-3 text-right"><span x-text="fmt(totalRek)"></span> ktr</td>
                        <td class="px-4 py-3 text-center text-red-600"><span x-text="fmt(total)"></span> ktr</td>
                        <td class="px-4 py-3 text-right" x-text="fmt(karton)"></td>
                        <td class="px-4 py-3 text-right">Rp <span x-text="fmt(nilai)"></span></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($editable)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
                <label class="flex items-start gap-3 max-w-3xl cursor-pointer">
                    <input type="checkbox" name="konfirmasi" value="1" x-model="agree" class="mt-1 rounded border-slate-300 text-red-600 focus:ring-red-500">
                    <span class="text-xs text-slate-600">
                        <b class="text-slate-900">Konfirmasi Kebenaran Data Cabang & Kemampuan Gudang.</b><br>
                        Saya menyatakan usulan di atas disusun berdasarkan kondisi riil cabang dan kapasitas gudang untuk periode {{ $tanggalEpo($periode, 'F Y') }}.
                    </span>
                </label>
                <div class="flex gap-3">
                    <button type="submit" name="aksi" value="draft" class="px-4 py-2.5 text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">
                        <i class="fa-regular fa-floppy-disk mr-1"></i> Simpan Draft
                    </button>
                    <button type="submit" name="aksi" value="kirim" :disabled="!agree || total < 1"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-paper-plane mr-1"></i> Submit Forecast
                    </button>
                </div>
            </div>
        @endif
    </form>
</div>
@endsection
