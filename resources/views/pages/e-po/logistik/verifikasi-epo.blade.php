@extends('layouts.app')

@section('title', 'Verifikasi E-PO')

@section('content')

<style>
    .po-sheet, .po-sheet * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @media print {
        @page { size: A4; margin: 10mm; }
        header, aside { display: none !important; }
        .pt-16 { padding-top: 0 !important; }
        main { margin: 0 !important; padding: 0 !important; }
        body { background: #fff !important; }
        .print-hidden { display: none !important; }
        .po-sheet { border: 0 !important; box-shadow: none !important; padding: 0 !important; }
    }
</style>

<div class="space-y-6" x-data="{ stok: false, jadwal: false, get lengkap() { return this.stok && this.jadwal } }">
<div class="print-hidden space-y-6">

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg"><i class="fa-solid fa-file-circle-check"></i></div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Logistik Pusat &middot; PT Yoke Food Industries Indonesia</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">Verifikasi Detail E-PO</h1>
            </div>
        </div>
        @php $langkah = ['Penetapan Kuota', 'Verifikasi E-PO']; $aktif = $po->status === 'Disetujui' ? 3 : 2; @endphp
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

    @php $bp = $badge($po->status, 'po'); $totalKuota = (int) $po->forecast->detailForecastRecords->sum('jumlah_disetujui'); @endphp
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex justify-between">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pesanan vs Kuota Forecast</p>
                <span class="rounded px-2 py-0.5 text-[10px] font-bold {{ $dok['total_karton'] <= $totalKuota ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $totalKuota > 0 ? round($dok['total_karton'] / $totalKuota * 100) : 0 }}% terpakai</span>
            </div>
            <p class="mt-2 text-3xl font-extrabold">{{ number_format($dok['total_karton'], 0, ',', '.') }} <span class="text-xs text-slate-500">/ {{ number_format($totalKuota, 0, ',', '.') }} karton (kuota)</span></p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-emerald-500" style="width: {{ $totalKuota > 0 ? min(100, round($dok['total_karton'] / $totalKuota * 100)) : 0 }}%"></div></div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Ekspektasi Pengiriman</p>
            <p class="mt-2 text-lg font-extrabold text-slate-900">&asymp; {{ number_format($dok['total_kontainer'], 2, ',', '.') }} kontainer</p>
            <p class="text-[11px] text-slate-500">{{ (int) ceil($dok['total_kontainer']) }} kontainer dibutuhkan &middot; tujuan {{ $po->forecast->distributor->wilayah->nama_wilayah }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nilai Dokumen</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">Rp {{ number_format($dok['total_nilai'], 0, ',', '.') }}</p>
            <span class="mt-1 inline-block rounded px-2 py-1 text-[10px] font-bold {{ $bp['class'] }}">&#9679; {{ $bp['label'] }}</span>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3">
        <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-file-lines mr-2 text-red-600"></i>Pratinjau Dokumen PO Resmi {{ $po->status === 'Disetujui' ? '(Siap Cetak)' : '(Draf Menunggu Verifikasi)' }}</h3>
        @if ($po->status === 'Disetujui')
            <button type="button" onclick="window.print()" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-700"><i class="fa-solid fa-print mr-1"></i> Cetak Dokumen PO</button>
        @endif
    </div>
</div>

@php
    $distPo = $po->forecast->distributor;
    $disetujui = $po->status === 'Disetujui';
    $pengaju = $distPo->penggunaRecord;
@endphp
<div class="po-sheet relative overflow-hidden rounded-xl border border-slate-300 bg-white p-8 text-slate-800 shadow-sm md:p-10">
    <div class="pointer-events-none absolute inset-0 flex select-none items-center justify-center">
        <span class="-rotate-12 text-6xl font-black tracking-widest {{ $disetujui ? 'text-emerald-600/10' : 'text-slate-400/15' }}">{{ $disetujui ? 'DISETUJUI' : 'MENUNGGU VERIFIKASI' }}</span>
    </div>

    <div class="relative">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-slate-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="flex h-14 w-14 items-center justify-center rounded bg-red-600 text-3xl font-extrabold text-white">Y</div>
                <div>
                    <p class="text-base font-extrabold uppercase">{{ $pabrik['nama'] }}</p>
                    <p class="max-w-sm text-[10px] text-slate-500">{{ $pabrik['alamat'] }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="rounded bg-red-600 px-2 py-1 text-[10px] font-bold tracking-wider text-white">PURCHASE ORDER</span>
                <p class="mt-2 text-xl font-extrabold text-red-600">{{ $po->nomor_po }}</p>
                <p class="text-[10px] text-slate-500">Tanggal Pengajuan: {{ $tanggalEpo($po->tanggal_pengajuan, 'd F Y') }}</p>
            </div>
        </div>

        <div class="mt-5 grid gap-6 text-xs md:grid-cols-2">
            <div>
                <p class="mb-1 text-[10px] font-bold uppercase text-slate-500">Informasi Pemesan / Distributor</p>
                <p class="text-sm font-bold">{{ $distPo->nama }} ({{ $distPo->wilayah->nama_wilayah }})</p>
                <p class="text-slate-600">{{ $distPo->alamat_gudang ?? '-' }}</p>
                <p class="text-slate-600">Kontak: {{ $distPo->kontak ?? '-' }} &middot; MoU: {{ $distPo->nomor_mou ?? '-' }}</p>
            </div>
            <div>
                <p class="mb-1 text-[10px] font-bold uppercase text-slate-500">Informasi Logistik &amp; Pembayaran</p>
                <dl class="space-y-1">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Periode Forecast</dt><dd class="font-semibold">{{ $tanggalEpo($po->forecast->periode, 'F Y') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Asal Pengiriman</dt><dd class="font-semibold">{{ $pabrik['plant'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Gelombang Keberangkatan</dt><dd class="font-semibold">{{ $tanggalEpo($jadwal['gelombang_kirim'], 'd F Y') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Estimasi Tiba (ETA)</dt><dd class="font-semibold">{{ $tanggalEpo($jadwal['eta_min'], 'd F Y') }} - {{ $tanggalEpo($jadwal['eta_max'], 'd F Y') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Termin Pembayaran</dt><dd class="font-semibold">{{ $pabrik['termin'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Nomor PO (Kode SAP)</dt><dd class="font-bold text-red-600">{{ $po->nomor_po }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] border border-slate-300 text-xs">
                <thead class="bg-slate-100 text-[10px] uppercase tracking-wider">
                    <tr>
                        <th class="px-3 py-2 text-left">No</th>
                        <th class="px-3 py-2 text-left">Kode SKU &amp; Deskripsi</th>
                        <th class="px-3 py-2 text-right">Kuantitas (Karton)</th>
                        <th class="px-3 py-2 text-right">Harga / Karton (Rp)</th>
                        <th class="px-3 py-2 text-right">Subtotal (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($dok['rows'] as $r)
                        <tr>
                            <td class="px-3 py-2">{{ str_pad($r['no'], 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-3 py-2"><p class="font-semibold">{{ $r['nama'] }}</p><p class="text-[10px] text-slate-500"><span class="font-mono">{{ $r['kode'] }}</span> &middot; {{ $r['kategori'] ?? '-' }}</p></td>
                            <td class="px-3 py-2 text-right font-semibold">{{ number_format($r['karton'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($r['harga'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ number_format($r['subtotal'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-5 grid gap-6 text-xs md:grid-cols-2">
            <div class="rounded-lg bg-slate-50 p-3">
                <p class="mb-1 text-[10px] font-bold uppercase text-slate-500">Catatan &amp; Ketentuan</p>
                <p class="leading-relaxed text-slate-600">Barang dikirim dalam kontainer sesuai estimasi di samping. Klaim kerusakan maksimal 2&times;24 jam setelah barang diterima disertai berita acara. Dokumen ini sah tanpa tanda tangan basah karena diterbitkan dan diverifikasi melalui sistem Waypoint.</p>
            </div>
            <dl class="space-y-1.5">
                <div class="flex justify-between"><dt class="text-slate-500">Total Kuantitas</dt><dd class="font-semibold">{{ number_format($dok['total_karton'], 0, ',', '.') }} Karton</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Estimasi Kontainer</dt><dd class="font-semibold">{{ number_format($dok['total_kontainer'], 2, ',', '.') }} (&asymp; {{ (int) ceil($dok['total_kontainer']) }} kontainer)</dd></div>
                <div class="flex justify-between border-t border-slate-300 pt-2 text-sm"><dt class="font-bold">Grand Total (DPP)</dt><dd class="font-extrabold text-red-600">Rp {{ number_format($dok['total_nilai'], 0, ',', '.') }}</dd></div>
            </dl>
        </div>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-lg border border-slate-300 p-4 text-center text-xs">
                <p class="text-[10px] font-bold uppercase text-slate-500">Pihak Pemesan / Distributor</p>
                <div class="mx-auto my-3 flex w-52 -rotate-3 flex-col items-center rounded-lg border-2 border-dashed border-blue-500 px-3 py-2 text-blue-700">
                    <span class="text-[9px] font-bold tracking-widest">E-SUBMITTED</span>
                    <span class="text-sm font-black leading-tight"><i class="fa-solid fa-paper-plane mr-1"></i>DIAJUKAN</span>
                    <span class="text-[9px]">{{ $tanggalEpo($po->tanggal_pengajuan, 'd/m/Y H:i') }} WIB</span>
                </div>
                <p class="font-bold">{{ $pengaju?->nama ?? $distPo->nama }}</p>
                <p class="text-slate-500">{{ $distPo->nama }}</p>
            </div>
            <div class="rounded-lg border border-slate-300 p-4 text-center text-xs">
                <p class="text-[10px] font-bold uppercase text-slate-500">Pihak Penjual / Logistik Pusat</p>
                @if ($disetujui && $po->penyetuju)
                    <div class="mx-auto my-3 flex w-56 -rotate-6 flex-col items-center rounded-lg border-4 border-double border-emerald-600 px-3 py-2 text-emerald-700">
                        <span class="text-[9px] font-bold tracking-widest">E-APPROVED</span>
                        <span class="text-xl font-black leading-tight tracking-wider"><i class="fa-solid fa-circle-check mr-1"></i>DISETUJUI</span>
                        <span class="text-[9px]">{{ $tanggalEpo($po->tanggal_disetujui, 'd/m/Y H:i') }} WIB</span>
                    </div>
                    <p class="font-bold">{{ $po->penyetuju->nama }}</p>
                    <p class="text-slate-500">Logistik Pusat &middot; {{ $pabrik['nama'] }}</p>
                    <p class="mt-1 font-mono text-[10px] text-emerald-700">Kode verifikasi: {{ $dok['kode_verifikasi'] }}</p>
                @else
                    <div class="mx-auto my-3 w-52 rounded-lg border-2 border-dashed border-slate-300 px-3 py-3 text-[10px] font-bold text-slate-400">MENUNGGU PERSETUJUAN LOGISTIK</div>
                    <p class="text-slate-400">Belum disetujui</p>
                @endif
            </div>
        </div>

        <div class="mt-6 flex flex-wrap justify-between gap-2 border-t border-slate-200 pt-3 text-[10px] text-slate-400">
            <span>Dokumen dihasilkan otomatis oleh E-PO &amp; Forecasting Portal Waypoint.</span>
            <span>{{ $po->nomor_po }} &middot; dicetak {{ $tanggalEpo(now(), 'd/m/Y H:i') }}</span>
        </div>
    </div>
</div>

<div class="print-hidden space-y-6">
    @if ($bisaDisetujui)
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900">Pemeriksaan Otomatis Sistem</h3>
                    <span class="rounded px-2 py-1 text-[10px] font-bold {{ $semuaLolos ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $semuaLolos ? 'Semua Lolos' : 'Ada Temuan' }}</span>
                </div>
                <ul class="space-y-2 rounded-lg bg-slate-50 p-3 text-xs">
                    @foreach ($pemeriksaan as $cek)
                        <li class="flex items-start gap-2">
                            <i class="mt-0.5 {{ $cek['lolos'] ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-xmark text-red-600' }}"></i>
                            <span class="{{ $cek['lolos'] ? 'text-slate-700' : 'font-semibold text-red-700' }}">{{ $cek['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900">Konfirmasi Logistik</h3>
                    <span class="rounded px-2 py-1 text-[10px] font-bold" :class="lengkap ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" x-text="lengkap ? 'Terkonfirmasi' : 'Menunggu Anda'"></span>
                </div>
                <div class="space-y-2 rounded-lg bg-slate-50 p-3 text-xs">
                    <label class="flex items-center gap-2"><input type="checkbox" x-model="stok" class="rounded text-red-600 focus:ring-red-500"> Ketersediaan stok pabrik untuk seluruh SKU terkonfirmasi</label>
                    <label class="flex items-center gap-2"><input type="checkbox" x-model="jadwal" class="rounded text-red-600 focus:ring-red-500"> Jadwal produksi dan slot muat kontainer terkonfirmasi</label>
                </div>
            </div>
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('e-po.logistik.antrean') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Antrean</a>
            @if ($po->status === 'Diajukan')
                <form method="POST" action="{{ route('e-po.logistik.mulai-epo', $po) }}">@csrf
                    <button type="submit" class="rounded-lg border border-blue-300 px-4 py-2.5 text-sm font-bold text-blue-700 hover:bg-blue-50"><i class="fa-solid fa-play mr-1"></i> Tandai Sedang Diproses</button></form>
            @endif
        </div>
        @if ($bisaDisetujui)
            <form method="POST" action="{{ route('e-po.logistik.setujui-epo', $po) }}">
                @csrf
                <input type="hidden" name="konfirmasi" :value="lengkap ? 1 : ''">
                <button type="submit" :disabled="!lengkap || {{ $semuaLolos ? 'false' : 'true' }}"
                        class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-slate-300"><i class="fa-solid fa-stamp mr-1"></i> Setujui E-PO &amp; Terbitkan Dokumen</button>
            </form>
        @else
            <button type="button" onclick="window.print()" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700"><i class="fa-solid fa-print mr-1"></i> Cetak Dokumen PO</button>
        @endif
    </div>
</div>
</div>
@endsection