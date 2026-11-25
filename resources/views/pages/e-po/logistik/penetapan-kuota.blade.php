@extends('layouts.app')

@section('title', 'Penetapan Kuota')

@section('content')
<div class="space-y-6" x-data="kuotaForm(@js($baris), {{ $kapasitas }}, {{ $sisaKapasitas }}, @js($catatan), @js($errors->messages()))">

    {{-- HEADER --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg"><i class="fa-solid fa-clipboard-check"></i></div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Logistik Pusat &middot; PT Yoke Food Industries Indonesia</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">Detail Evaluasi &amp; Penetapan Kuota Forecast</h1>
            </div>
        </div>
        @php $langkah = ['Penetapan Kuota', 'Verifikasi E-PO']; $aktif = $forecast->status === 'Disetujui' ? 2 : 1; @endphp
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

    @include('layouts.partials.flash')

    @php $bf = $badge($forecast->status, 'forecast'); @endphp
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Distributor Pemohon</p>
            <p class="mt-1 font-bold text-slate-900">{{ $forecast->distributor->nama }}</p>
            <p class="text-[11px] text-slate-500">{{ $forecast->distributor->wilayah->nama_wilayah }} &middot; MoU {{ $forecast->distributor->nomor_mou ?? '-' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Periode Forecast</p>
            <p class="mt-1 font-bold text-slate-900">{{ $tanggalEpo($forecast->periode, 'F Y') }}</p>
            <p class="text-[11px] text-slate-500">Diajukan {{ $tanggalEpo($forecast->tanggal_pengajuan, 'd M Y, H:i') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Sisa Kapasitas Pabrik</p>
            <p class="mt-1 font-bold text-slate-900">{{ number_format($sisaKapasitas, 0, ',', '.') }} Karton</p>
            <p class="text-[11px] text-slate-500">Dari {{ number_format($kapasitas, 0, ',', '.') }} karton (setelah kuota distributor lain)</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Status Review</p>
            <span class="mt-2 inline-block rounded px-2 py-1 text-[10px] font-bold {{ $bf['class'] }}">&#9679; {{ $bf['label'] }}</span>
            @if ($forecast->status === 'Disetujui')
                <p class="mt-1 text-[11px] text-slate-500">{{ $tanggalEpo($forecast->tanggal_disetujui, 'd M Y') }} oleh {{ $forecast->penyetuju?->nama }}</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Usulan Distributor</p>
            <p class="mt-2 text-3xl font-extrabold"><span x-text="fmt(usulan)"></span> <span class="text-xs text-slate-500">Krt</span></p>
            <p class="text-[10px] text-slate-500">&asymp; <span x-text="fmt2(usulan / {{ $kartonPerKontainer }})"></span> kontainer</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Sisa Kapasitas</p>
            <p class="mt-2 text-3xl font-extrabold">{{ number_format($sisaKapasitas, 0, ',', '.') }} <span class="text-xs text-slate-500">Krt</span></p>
            <p class="text-[10px]" :class="usulan > sisa ? 'text-red-600 font-bold' : 'text-slate-500'" x-text="usulan > sisa ? 'Usulan melebihi kapasitas, perlu dikurangi' : 'Cukup untuk seluruh usulan'"></p>
        </div>
        <div class="rounded-xl border p-4 shadow-sm" :class="melebihi ? 'border-red-300 bg-red-50' : 'border-emerald-200 bg-emerald-50'">
            <p class="text-[10px] font-bold uppercase tracking-wider" :class="melebihi ? 'text-red-600' : 'text-emerald-700'">Alokasi Kuota Resmi</p>
            <p class="mt-2 text-3xl font-extrabold"><span x-text="fmt(kuotaTotal)"></span> <span class="text-xs text-slate-500">Krt</span></p>
            <p class="text-[10px]" :class="melebihi ? 'text-red-600' : 'text-emerald-700'" x-text="melebihi ? 'Melebihi sisa kapasitas!' : 'Dalam kapasitas pabrik'"></p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Penyesuaian Kuota</p>
            <p class="mt-2 text-3xl font-extrabold" :class="selisih < 0 ? 'text-red-600' : 'text-slate-900'"><span x-text="(selisih > 0 ? '+' : '') + fmt(selisih)"></span> <span class="text-xs text-slate-500">Krt</span></p>
            <p class="text-[10px] text-slate-500">Nilai kuota Rp <span x-text="fmt(nilai)"></span></p>
        </div>
    </div>

    <form method="POST" action="{{ route('e-po.logistik.tetapkan-kuota', $forecast) }}" class="space-y-6">
        @csrf

        @if ($editable)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50/60 p-4">
                <div class="text-xs text-slate-700">
                    <p class="text-sm font-bold text-slate-900"><i class="fa-solid fa-bolt mr-1 text-red-600"></i>Alur cepat penetapan kuota</p>
                    <p class="mt-0.5">Bila semua usulan dapat dipenuhi, klik <b>Setujui Semua Sesuai Usulan</b>. Bila ada SKU yang dikurangi, ubah angkanya saja lalu isi catatan penyesuaian.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" x-show="usulan > sisa" style="display:none" @click="sesuaikanKapasitas()" class="rounded-lg border border-amber-400 bg-white px-3 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50"><i class="fa-solid fa-scale-balanced mr-1"></i>Sesuaikan ke Kapasitas</button>
                    <button type="button" @click="resetUsulan()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold hover:bg-slate-50">Reset ke Usulan</button>
                    <button type="submit" name="aksi" value="setujui_semua" :disabled="usulan > sisa"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40"><i class="fa-solid fa-check-double mr-1"></i>Setujui Semua Sesuai Usulan</button>
                </div>
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-table-list mr-2 text-red-600"></i>Tabel Evaluasi &amp; Penetapan Kuota Resmi per SKU</h3>
            </div>
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode SKU</th>
                        <th class="px-4 py-3 text-left">Nama Produk</th>
                        <th class="px-4 py-3 text-right">Usulan (Karton)</th>
                        <th class="px-4 py-3 text-center">Kuota Resmi (Karton)</th>
                        <th class="px-4 py-3 text-right">Selisih</th>
                        <th class="px-4 py-3 text-right">Nilai Kuota</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in rows" :key="r.id">
                        <tr class="border-t border-slate-100" :class="stat(r) !== 'ok' ? 'bg-red-50/60' : ''">
                            <td class="px-4 py-3 font-mono text-[11px] font-bold text-red-600" x-text="r.kode"></td>
                            <td class="px-4 py-3 font-semibold" x-text="r.nama"></td>
                            <td class="px-4 py-3 text-right font-semibold" x-text="fmt(r.usulan)"></td>
                            <td class="px-4 py-3 text-center">
                                <input type="number" min="0" :max="r.usulan" step="1" x-model.number="r.kuota" :name="`kuota[${r.id}]`" @if (! $editable) readonly @endif
                                       class="w-28 rounded border py-1.5 text-center text-sm font-bold focus:border-red-500 focus:ring-red-500" :class="stat(r) !== 'ok' ? 'border-red-400' : 'border-slate-200'">
                                <p x-show="galat['kuota.' + r.id]" style="display:none" class="mt-1 max-w-[15rem] text-[10px] font-bold text-red-600" x-text="(galat['kuota.' + r.id] || [])[0]"></p>
                            </td>
                            <td class="px-4 py-3 text-right" :class="r.kuota - r.usulan < 0 ? 'font-bold text-red-600' : 'text-slate-500'" x-text="(r.kuota - r.usulan > 0 ? '+' : '') + fmt(r.kuota - r.usulan)"></td>
                            <td class="px-4 py-3 text-right" x-text="'Rp ' + fmt(Number(r.kuota || 0) * r.harga)"></td>
                            <td class="px-4 py-3 text-center">
                                <span x-show="stat(r)==='ok'" class="rounded bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">&#9679; Sesuai Usulan</span>
                                <span x-show="stat(r)==='kurang'" style="display:none" class="rounded bg-red-600 px-2 py-1 text-[10px] font-bold text-white">Dikurangi</span>
                                <span x-show="stat(r)==='lebih'" style="display:none" class="rounded bg-amber-500 px-2 py-1 text-[10px] font-bold text-white">Melebihi Usulan</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-slate-50 font-bold">
                    <tr class="border-t border-slate-200">
                        <td colspan="2" class="px-4 py-3 text-xs uppercase">Total Akumulasi ({{ count($baris) }} SKU)</td>
                        <td class="px-4 py-3 text-right" x-text="fmt(usulan)"></td>
                        <td class="px-4 py-3 text-center text-red-600"><span x-text="fmt(kuotaTotal)"></span> krt</td>
                        <td class="px-4 py-3 text-right" :class="selisih < 0 ? 'text-red-600' : ''" x-text="(selisih > 0 ? '+' : '') + fmt(selisih)"></td>
                        <td class="px-4 py-3 text-right">Rp <span x-text="fmt(nilai)"></span></td>
                        <td class="px-4 py-3 text-center text-[10px]" :class="(melebihi || adaLebih) ? 'text-red-600' : 'text-emerald-600'" x-text="(melebihi || adaLebih) ? 'PERLU DIPERBAIKI' : 'SIAP DITETAPKAN'"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="rounded-xl border bg-white p-5 shadow-sm" :class="(adaPenyesuaian && catatan.trim().length < 5) ? 'border-red-300' : 'border-slate-200'">
            <label for="catatan" class="text-sm font-bold text-slate-900"><i class="fa-regular fa-pen-to-square mr-2 text-red-600"></i>Catatan Penyesuaian
                <span x-show="adaPenyesuaian" style="display:none" class="ml-1 rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white">wajib</span></label>
            <p class="text-[11px] text-slate-500">Jelaskan alasan pengurangan kuota (mis. kapasitas lini produksi, ketersediaan bahan baku). Dicatat di log aktivitas.</p>
            <textarea id="catatan" name="catatan" rows="2" maxlength="255" x-model="catatan" @if (! $editable) readonly @endif
                      class="mt-2 w-full rounded-lg border border-slate-300 p-2.5 text-sm focus:border-red-500 focus:ring-red-500" placeholder="Contoh: Kapasitas lini FAN Sparkling terbatas pada periode ini."></textarea>
            <p x-show="galat.catatan" style="display:none" class="mt-1 text-[11px] font-bold text-red-600" x-text="(galat.catatan || [])[0]"></p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <a href="{{ route('e-po.logistik.antrean') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Daftar Forecast</a>
            @if ($editable)
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" name="aksi" value="draft" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50"><i class="fa-regular fa-floppy-disk mr-1"></i> Simpan Draft</button>
                    <button type="submit" name="aksi" value="setujui" :disabled="!bisaSetujuiPenyesuaian"
                            class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-slate-300"><i class="fa-solid fa-lock mr-1"></i> Setujui dengan Penyesuaian</button>
                </div>
            @else
                <span class="text-xs text-slate-500"><i class="fa-solid fa-lock mr-1"></i>Kuota sudah ditetapkan dan tidak dapat diubah.</span>
            @endif
        </div>
    </form>

    @if ($forecast->status === 'Diajukan')
        <form method="POST" action="{{ route('e-po.logistik.mulai-forecast', $forecast) }}" class="flex justify-end">
            @csrf
            <button type="submit" class="rounded-lg border border-blue-300 px-4 py-2 text-xs font-bold text-blue-700 hover:bg-blue-50"><i class="fa-solid fa-play mr-1"></i> Tandai Sedang Diproses</button>
        </form>
    @endif
</div>

@push('scripts')
<script>
    function kuotaForm(rows, kapasitas, sisa, catatan, galat) {
        return {
            rows: rows.map(r => ({ ...r })), kapasitas, sisa, catatan: catatan || '', galat: galat || {},
            fmt(n) { return new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0)); },
            fmt2(n) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(n) || 0); },
            stat(r) { const k = Number(r.kuota || 0); return k === r.usulan ? 'ok' : (k > r.usulan ? 'lebih' : 'kurang'); },
            resetUsulan() { this.rows.forEach(r => { r.kuota = r.usulan; }); },
            sesuaikanKapasitas() {
                const faktor = this.sisa / this.usulan;
                this.rows.forEach(r => { r.kuota = Math.floor(r.usulan * faktor); });
            },
            get usulan() { return this.rows.reduce((a, r) => a + r.usulan, 0); },
            get kuotaTotal() { return this.rows.reduce((a, r) => a + Number(r.kuota || 0), 0); },
            get selisih() { return this.kuotaTotal - this.usulan; },
            get nilai() { return this.rows.reduce((a, r) => a + Number(r.kuota || 0) * r.harga, 0); },
            get melebihi() { return this.kuotaTotal > this.sisa; },
            get adaLebih() { return this.rows.some(r => this.stat(r) === 'lebih'); },
            get adaPenyesuaian() { return this.rows.some(r => this.stat(r) === 'kurang'); },
            get bisaSetujuiPenyesuaian() { return this.adaPenyesuaian && !this.melebihi && !this.adaLebih && this.kuotaTotal > 0 && this.catatan.trim().length >= 5; },
        };
    }
</script>
@endpush
@endsection