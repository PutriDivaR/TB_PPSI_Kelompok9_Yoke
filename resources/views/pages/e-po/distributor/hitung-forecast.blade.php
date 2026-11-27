@extends('layouts.app')

@section('title', 'Hitung Forecast')

@section('content')
@if (! isset($distributor))
    <div class="flex min-h-[60vh] items-center justify-center">
        <div class="w-full max-w-xl rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-500">
                <i class="fa-solid fa-database"></i>
            </div>
            <h1 class="mt-5 text-xl font-bold text-slate-900">Belum ada distributor aktif</h1>
            <p class="mt-2 text-sm text-slate-500">Tabel <code class="font-mono">distributor</code> masih kosong. Isi data master atau jalankan data dummy E-PO, lalu muat ulang halaman ini.</p>
        </div>
    </div>
@else
<div class="space-y-6">

    {{-- HEADER HALAMAN + STEPPER --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-store"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Distributor Regional &middot; DST-{{ str_pad($distributor->id_distributor, 3, '0', STR_PAD_LEFT) }}</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">{{ $distributor->nama }} ({{ $distributor->wilayah->nama_wilayah }})</h1>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            @php
                $langkah = ['Usulan Forecast', 'Input E-PO', 'Dokumen PO Selesai'];
                $aktif = $siapInputEpo ? 2 : 1;
            @endphp
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

    {{-- HERO FORECAST --}}
    <div class="flex flex-wrap items-center justify-between gap-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-red-600">
                <i class="fa-solid fa-calendar-days"></i>
                Tahap Forecast
                &middot; {{ $tahunFiskal ? 'FY-'.$tahunFiskal->tanggal_mulai->year : 'Tahun fiskal belum terdaftar' }}
                &middot; Periode {{ $tanggalEpo($periode, 'F Y') }}
            </span>
            <h2 class="mt-3 text-xl font-bold text-slate-900">Mulai Penyusunan Estimasi Kebutuhan Produk</h2>
            <p class="mt-1 text-sm text-slate-600">
                Sistem menghitung kebutuhan dalam <b>karton</b> dari histori Sell-Out {{ $jumlahBulan ?? 3 }} bulan terakhir
                sebagai baseline untuk <b>Target Kedatangan {{ $tanggalEpo($periode, 'F Y') }}</b>. Forecast dibuat terpisah per bulan
                dalam horizon perencanaan 5 bulan ke depan.
            </p>
            @if (! $bisaMengajukanPeriode)
                <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    Periode ini tidak lagi dapat dijadikan Forecast baru karena cutoff 15 dan lead time 2–3 bulan. Gunakan periode 2–3 bulan mendatang atau buka riwayat melalui tab di bawah.
                </p>
            @endif
            <div class="mt-4 flex flex-wrap gap-3">
                @if (! $bisaMengajukanPeriode)
                    <span class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-5 py-2.5 text-sm font-semibold text-slate-500">
                        <i class="fa-solid fa-lock"></i>
                        Periode Sudah Lewat
                    </span>
                @elseif (! $forecastTarget || $forecastTarget->status === 'Draft')
                    <a href="{{ route('e-po.distributor.ajukan-forecast', ['periode' => $periode->format('Y-m')]) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-5 py-2.5">
                        <i class="fa-solid fa-calculator"></i>
                        {{ $forecastTarget ? 'Lanjutkan Draft' : 'Hitung Forecast Baru' }}
                    </a>
                @elseif ($forecastTarget->status === 'Disetujui' && $siapInputEpo)
                    <a href="{{ route('e-po.distributor.input-epo', ['forecast' => $forecastTarget->id_forecast]) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-5 py-2.5">
                        <i class="fa-solid fa-cart-shopping"></i>
                        Lanjutkan ke Input E-PO
                    </a>
                @endif
                @if ($realisasi)
                    <div class="rounded-lg border border-slate-200 px-4 py-2 text-xs text-slate-600">
                        Realisasi terakhir: {{ $realisasi['persen'] }}%
                        ({{ number_format($realisasi['dipesan'], 0, ',', '.') }}
                        / {{ number_format($realisasi['kuota'], 0, ',', '.') }} karton)
                    </div>
                @endif
            </div>
        </div>
        <form method="GET" action="{{ route('e-po.distributor.hitung-forecast') }}" class="w-full max-w-xs rounded-xl border border-slate-200 p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pilih Target Kedatangan</p>
            <p class="mt-0.5 text-[11px] text-slate-500">Forecast dapat direncanakan hingga 5 bulan ke depan. Bulan lama tetap tersedia untuk melihat riwayat.</p>
            <select name="periode" onchange="this.form.submit()" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <optgroup label="Riwayat / hanya-baca">
                @foreach ($periodePilihan as $pilihan)
                    @if ($pilihan->lessThan($periodeMinimumForecast))
                    <option value="{{ $pilihan->format('Y-m') }}" @selected($pilihan->format('Y-m') === $periode->format('Y-m'))>
                        {{ $tanggalEpo($pilihan, 'F Y') }} — Lihat riwayat
                    </option>
                    @endif
                @endforeach
                </optgroup>
                <optgroup label="Rolling Forecast hingga 5 bulan">
                @foreach ($periodePilihan as $pilihan)
                    @if ($pilihan->greaterThanOrEqualTo($periodeMinimumForecast) && $pilihan->lessThanOrEqualTo($periodeMaximumForecast))
                    <option value="{{ $pilihan->format('Y-m') }}" @selected($pilihan->format('Y-m') === $periode->format('Y-m'))>
                        {{ $tanggalEpo($pilihan, 'F Y') }} — Target kedatangan
                    </option>
                    @endif
                @endforeach
                </optgroup>
            </select>
        </form>
    </div>

    {{-- PROSES FORECAST --}}
    @if ($forecastTarget)
        @php
            $tahapAktif = match ($forecastTarget->status) {
                'Draft' => 1,
                'Diajukan', 'Diproses' => 2,
                'Disetujui' => $siapInputEpo ? 3 : 4,
                default => 1,
            };
            $labelStatus = $badge($forecastTarget->status, 'forecast');
        @endphp
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Proses Forecast {{ $tanggalEpo($periode, 'F Y') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Status Anda:
                        <span class="ml-1 rounded px-2 py-0.5 text-[10px] font-bold {{ $labelStatus['class'] }}">{{ $labelStatus['label'] }}</span>
                    </p>
                </div>
                @if ($forecastTarget->status === 'Diajukan')
                    <p class="text-xs text-amber-700"><i class="fa-solid fa-hourglass-half mr-1"></i> Menunggu Logistik Pusat menetapkan kuota.</p>
                @elseif ($forecastTarget->status === 'Diproses')
                    <p class="text-xs text-blue-700"><i class="fa-solid fa-spinner mr-1"></i> Sedang ditinjau oleh Logistik Pusat.</p>
                @elseif ($forecastTarget->status === 'Disetujui' && $siapInputEpo)
                    <p class="text-xs text-emerald-700"><i class="fa-solid fa-check-circle mr-1"></i> Kuota tersedia. Anda dapat membuat E-PO.</p>
                @endif
            </div>

            <ol class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Forecast dibuat', 'Data usulan tersimpan'],
                    ['Menunggu penetapan', 'Logistik memeriksa usulan'],
                    ['Kuota disetujui', 'Plafon E-PO tersedia'],
                    ['E-PO diterbitkan', 'Dokumen resmi selesai'],
                ] as $n => [$judul, $uraian])
                    @php $step = $n + 1; @endphp
                    <li class="rounded-lg border p-3 {{ $step < $tahapAktif ? 'border-emerald-200 bg-emerald-50' : ($step === $tahapAktif ? 'border-red-300 bg-red-50 ring-2 ring-red-100' : 'border-slate-200 bg-white') }}">
                        <p class="text-[10px] font-bold uppercase text-slate-500">Langkah {{ $step }}</p>
                        <p class="mt-1 text-sm font-bold {{ $step <= $tahapAktif ? 'text-slate-900' : 'text-slate-400' }}">{{ $judul }}</p>
                        <p class="text-[11px] {{ $step <= $tahapAktif ? 'text-slate-600' : 'text-slate-400' }}">{{ $uraian }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    {{-- RIWAYAT FORECAST & E-PO --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" x-data="{ tab: 'forecast', q: '' }">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 pt-4">
            <div class="flex gap-6 text-sm">
                <button type="button" @click="tab='forecast'" class="border-b-2 pb-3 font-bold transition" :class="tab==='forecast' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500'">
                    Riwayat Forecast <span class="ml-1 rounded-full bg-slate-100 px-1.5 text-[10px] text-slate-700">{{ $riwayat->count() }}</span>
                </button>
                <button type="button" @click="tab='epo'" class="border-b-2 pb-3 font-bold transition" :class="tab==='epo' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500'">
                    Pengajuan E-PO <span class="ml-1 rounded-full bg-red-600 px-1.5 text-[10px] text-white">{{ $riwayatEpo->count() }}</span>
                </button>
            </div>
            <div class="relative pb-3">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input x-model="q" type="text" placeholder="Cari nomor PO / periode..." class="w-60 rounded-lg border border-slate-200 py-1.5 pl-8 pr-3 text-xs">
            </div>
        </div>

        <div x-show="tab==='forecast'" class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Periode</th>
                        <th class="px-4 py-3 text-right">Total Usulan (Karton)</th>
                        <th class="px-4 py-3 text-right">Total Disetujui (Karton)</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Diajukan</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($riwayat as $row)
                        @php $b = $badge($row->status); @endphp
                        <tr x-show="@js(strtolower($tanggalEpo($row->periode, 'F Y').' '.$row->status)).includes(q.toLowerCase())">
                            <td class="px-4 py-3 font-medium">{{ $tanggalEpo($row->periode, 'F Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($row->total_usulan ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($row->total_disetujui ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3"><span class="rounded px-2 py-1 text-[10px] font-bold {{ $b['class'] }}">{{ $b['label'] }}</span></td>
                            <td class="px-4 py-3 text-slate-500">{{ $tanggalEpo($row->tanggal_pengajuan) }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('e-po.distributor.ajukan-forecast', ['periode' => $row->periode->format('Y-m')]) }}" class="rounded border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50">
                                    <i class="fa-regular fa-eye mr-1"></i>Lihat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada riwayat forecast.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab==='epo'" style="display:none" class="overflow-x-auto">
            <table class="w-full min-w-[920px] text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Nomor PO</th>
                        <th class="px-4 py-3 text-left">Periode Forecast</th>
                        <th class="px-4 py-3 text-right">Volume (Karton)</th>
                        <th class="px-4 py-3 text-right">Nilai E-PO</th>
                        <th class="px-4 py-3 text-left">Diajukan</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($riwayatEpo as $po)
                        @php $bp = $badge($po->status, 'po'); @endphp
                        <tr class="hover:bg-slate-50" x-show="@js(strtolower($po->nomor_po.' '.$tanggalEpo($po->forecast->periode, 'F Y').' '.$po->status))->includes(q.toLowerCase())">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-red-600">{{ $po->nomor_po }}</td>
                            <td class="px-4 py-3">{{ $tanggalEpo($po->forecast->periode, 'F Y') }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($po->total_karton, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($po->total_nilai, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $tanggalEpo($po->tanggal_pengajuan, 'd M Y, H:i') }}</td>
                            <td class="px-4 py-3"><span class="inline-block rounded px-2 py-1 text-[10px] font-bold {{ $bp['class'] }}">&#9679; {{ $bp['label'] }}</span></td>
                            <td class="px-4 py-3 text-center">
                                @if ($po->status === 'Draft')
                                    <a href="{{ route('e-po.distributor.input-epo') }}" class="rounded border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-regular fa-pen-to-square mr-1"></i>Lanjutkan</a>
                                @else
                                    <a href="{{ route('e-po.distributor.epo-selesai', $po) }}" class="rounded border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-regular fa-eye mr-1"></i>Lihat Status</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada pengajuan E-PO.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection