@extends('layouts.app')

@section('title', 'Ajukan Forecast')

@section('content')
<div class="space-y-6" x-data="forecastForm(@js($baris), {{ $kapasitas }}, {{ $editable ? 'true' : 'false' }}, {{ $kartonPerKontainer }})">

    {{-- HEADER + STEPPER --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg"><i class="fa-solid fa-store"></i></div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Distributor Regional &middot; DST-{{ str_pad($distributor->id_distributor, 3, '0', STR_PAD_LEFT) }}</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">{{ $distributor->nama }} ({{ $distributor->wilayah->nama_wilayah }})</h1>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            <form method="POST" action="{{ route('e-po.distributor.konteks') }}" class="flex items-center gap-2">
                @csrf
                <label for="ctx-distributor" class="text-[10px] font-bold uppercase text-slate-400">Perusahaan simulasi</label>
                <select id="ctx-distributor" name="id_distributor" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded-lg px-2 py-1.5 bg-white">
                    @foreach ($daftarDistributor as $d)
                        <option value="{{ $d->id_distributor }}" @selected($d->id_distributor === $distributor->id_distributor)>{{ $d->nama }}</option>
                    @endforeach
                </select>
            </form>
            @php $langkah = ['Usulan Forecast', 'Input E-PO', 'Dokumen PO Selesai']; $aktif = 1; @endphp
            <ol class="flex items-center gap-2 text-xs">
                @foreach ($langkah as $i => $label)
                    @php $n = $i + 1; @endphp
                    <li class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold {{ $n < $aktif ? 'bg-emerald-500 text-white' : ($n == $aktif ? 'bg-red-600 text-white ring-4 ring-red-100' : 'bg-slate-200 text-slate-500') }}">
                            @if ($n < $aktif) <i class="fa-solid fa-check text-[10px]"></i> @else {{ $n }} @endif
                        </span>
                        <span class="{{ $n == $aktif ? 'font-bold text-slate-900' : 'text-slate-500' }}">{{ $label }}</span>
                        @if (! $loop->last) <span class="w-8 h-px bg-slate-300"></span> @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </div>

    @include('layouts.partials.flash')

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Forecast Kebutuhan Periode Mendatang</h2>
            <p class="text-xs text-slate-500">
                Periode forecast: {{ $tanggalEpo($periode, 'F Y') }} &middot;
                batas pengajuan {{ $tanggalEpo($tenggat, 'd M Y, H.i') }} WIB &middot;
                1 forecast = kebutuhan 1 bulan target kedatangan; dapat direncanakan hingga 5 bulan ke depan
            </p>
        </div>
        @if ($forecast)
            @php $bf = $badge($forecast->status, 'forecast'); @endphp
            <span class="inline-block rounded px-2 py-1 text-[10px] font-bold {{ $bf['class'] }}">&#9679; {{ $bf['label'] }}</span>
        @endif
    </div>

    @unless ($editable)
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600">
            <i class="fa-solid fa-lock mr-2"></i>
            @if (! $bisaMengajukanPeriode)
                Periode {{ $tanggalEpo($periode, 'F Y') }} sudah lewat sehingga forecast ditampilkan hanya-baca dan tidak dapat diubah.
            @else
                Forecast ini sudah diajukan sehingga ditampilkan hanya-baca.
                @if ($bisaDibatalkan) Anda masih dapat membatalkannya selama Logistik belum memprosesnya. @endif
            @endif
            <a href="{{ route('e-po.distributor.hitung-forecast', ['periode' => $periode->format('Y-m')]) }}" class="ml-1 font-bold text-red-600 hover:underline">Kembali ke Hitung Forecast</a>
        </div>
    @endunless

    <form method="POST" action="{{ route('e-po.distributor.submit-forecast') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="periode" value="{{ $periode->format('Y-m') }}">

        {{-- RINGKASAN --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Rekomendasi Sistem</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900"><span x-text="fmt(totalRek)"></span> <span class="text-xs text-slate-500">Karton</span></p>
                <p class="text-[10px] text-slate-500">Sell-Out {{ $jumlahBulan }} bln + stok pengaman</p>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-red-600">Total Usulan Distributor</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900"><span x-text="fmt(total)"></span> <span class="text-xs text-slate-500">Karton</span></p>
                <p class="text-[10px] text-red-600"><span x-text="terisi"></span> SKU terisi &middot; &asymp; <span x-text="fmt2(total / kpk)"></span> kontainer</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Estimasi Nilai Pengajuan</p>
                <p class="mt-2 text-xl font-extrabold text-slate-900">Rp <span x-text="fmt(nilai)"></span></p>
                <p class="text-[10px] text-slate-500">Harga produk &times; karton</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Kapasitas Pabrik / Bulan</p>
                <p class="mt-2 text-xl font-extrabold text-slate-900">{{ number_format($kapasitas, 0, ',', '.') }} <span class="text-xs text-slate-500">Karton</span></p>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full" :class="pct > 60 ? 'bg-amber-500' : 'bg-emerald-500'" :style="`width:${pct}%`"></div></div>
                <p class="mt-1 text-[10px] text-slate-500">Usulan Anda = <span x-text="pct"></span>% kapasitas</p>
            </div>
        </div>

        {{-- TABEL SEMUA SKU --}}
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Rincian SKU Usulan Forecast <span class="ml-2 rounded bg-slate-100 px-2 py-0.5 text-[10px]">{{ count($baris) }} SKU Aktif</span></h3>
                    <p class="text-[11px] text-slate-500">Isi hanya SKU yang dibutuhkan; SKU lain biarkan 0 (tidak ikut dikirim).</p>
                </div>
                @if ($editable)
                    <div class="flex gap-2">
                        <button type="button" @click="pakaiSemua()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">
                            <i class="fa-solid fa-wand-magic-sparkles mr-1 text-red-600"></i>Terapkan Semua Rekomendasi
                        </button>
                        <button type="button" @click="kosongkan()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold hover:bg-slate-50">Kosongkan</button>
                    </div>
                @endif
            </div>
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode SKU</th>
                        <th class="px-4 py-3 text-left">Nama Produk</th>
                        <th class="px-4 py-3 text-right">Sell-Out / Bln</th>
                        <th class="px-4 py-3 text-right">Stok Layak Jual</th>
                        <th class="px-4 py-3 text-right">Rekomendasi</th>
                        <th class="px-4 py-3 text-center">Usulan (Karton)</th>
                        <th class="px-4 py-3 text-right">Estimasi Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="r in rows" :key="r.id">
                        <tr :class="Number(r.usulan) > 0 ? 'bg-red-50/30' : 'hover:bg-slate-50'">
                            <td class="px-4 py-3 font-mono text-[11px] font-bold text-red-600" x-text="r.kode"></td>
                            <td class="px-4 py-3"><p class="font-semibold text-slate-900" x-text="r.nama"></p><p class="text-[10px] text-slate-500" x-text="r.kategori || '-'"></p></td>
                            <td class="px-4 py-3 text-right text-slate-600" x-text="fmt(r.rata_rata)"></td>
                            <td class="px-4 py-3 text-right text-slate-600" x-text="fmt(r.stok)"></td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold" x-text="fmt(r.rekomendasi)"></span>
                                <button type="button" x-show="editable && r.rekomendasi > 0" @click="r.usulan = r.rekomendasi" class="ml-1 rounded border border-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-red-600 hover:bg-red-50">pakai</button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" x-show="editable" @click="step(r, -100)" class="h-7 w-7 rounded border border-slate-200 hover:bg-slate-100"><i class="fa-solid fa-minus text-[10px]"></i></button>
                                    <input type="number" min="0" step="1" x-model.number="r.usulan" :name="`usulan[${r.id}]`" :readonly="!editable"
                                           class="w-24 rounded border border-slate-200 py-1 text-center text-sm font-bold focus:border-red-500 focus:ring-red-500"
                                           :class="Number(r.usulan) > r.rekomendasi && r.rekomendasi > 0 ? 'text-red-600' : ''">
                                    <button type="button" x-show="editable" @click="step(r, 100)" class="h-7 w-7 rounded border border-slate-200 hover:bg-slate-100"><i class="fa-solid fa-plus text-[10px]"></i></button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold" x-text="'Rp ' + fmt(Number(r.usulan || 0) * r.harga)"></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-slate-50 font-bold text-slate-900">
                    <tr>
                        <td colspan="4" class="px-4 py-3 text-xs uppercase">Total Rekapitulasi Usulan Cabang</td>
                        <td class="px-4 py-3 text-right" x-text="fmt(totalRek)"></td>
                        <td class="px-4 py-3 text-center text-red-600"><span x-text="fmt(total)"></span> krt</td>
                        <td class="px-4 py-3 text-right">Rp <span x-text="fmt(nilai)"></span></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($editable)
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <label class="flex max-w-3xl cursor-pointer items-start gap-3">
                    <input type="checkbox" name="konfirmasi" value="1" x-model="agree" class="mt-1 rounded border-slate-300 text-red-600 focus:ring-red-500">
                    <span class="text-xs text-slate-600">
                        <b class="text-slate-900">Konfirmasi Kebenaran Data Cabang &amp; Kemampuan Gudang.</b><br>
                        Saya menyatakan usulan di atas disusun berdasarkan kondisi riil cabang dan kapasitas gudang untuk periode {{ $tanggalEpo($periode, 'F Y') }}.
                    </span>
                </label>
                <div class="flex gap-3">
                    <button type="submit" name="aksi" value="draft" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50"><i class="fa-regular fa-floppy-disk mr-1"></i> Simpan Draft</button>
                    <button type="submit" name="aksi" value="kirim" :disabled="!agree || terisi < 1"
                            class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40"><i class="fa-solid fa-paper-plane mr-1"></i> Submit Forecast</button>
                </div>
            </div>
        @endif
    </form>

    @if ($bisaDibatalkan)
        <form method="POST" action="{{ route('e-po.distributor.hapus-forecast', $forecast) }}" class="flex justify-end"
              onsubmit="return confirm('{{ $forecast->status === 'Draft' ? 'Hapus draft forecast ini?' : 'Batalkan pengajuan forecast ini? Data usulan akan dihapus.' }}')">
            @csrf @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-300 px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50">
                <i class="fa-regular fa-trash-can mr-1"></i> {{ $forecast->status === 'Draft' ? 'Hapus Draft Forecast' : 'Batalkan Pengajuan Forecast' }}
            </button>
        </form>
    @endif
</div>

@push('scripts')
<script>
    function forecastForm(rows, kapasitas, editable, kpk) {
        return {
            rows, kapasitas, editable, kpk, agree: false,
            fmt(n) { return new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0)); },
            fmt2(n) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(n) || 0); },
            step(r, d) { r.usulan = Math.max(0, Number(r.usulan || 0) + d); },
            pakaiSemua() { this.rows.forEach(r => { r.usulan = r.rekomendasi; }); },
            kosongkan() { this.rows.forEach(r => { r.usulan = 0; }); },
            get totalRek() { return this.rows.reduce((a, r) => a + r.rekomendasi, 0); },
            get total() { return this.rows.reduce((a, r) => a + Number(r.usulan || 0), 0); },
            get terisi() { return this.rows.filter(r => Number(r.usulan || 0) > 0).length; },
            get nilai() { return this.rows.reduce((a, r) => a + Number(r.usulan || 0) * r.harga, 0); },
            get pct() { return Math.min(100, Math.round(this.total / this.kapasitas * 100)); },
        };
    }
</script>
@endpush
@endsection