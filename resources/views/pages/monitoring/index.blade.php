@extends('layouts.app')

@section('title', 'Monitoring Pengiriman')

@section('module_title', 'Modul Monitoring Pengiriman')

@section('content')
<div class="space-y-6" x-data="{ showInputModal: false, etaAwal: '{{ date('Y-m-d', strtotime('+3 days')) }}', etaTerkini: '{{ date('Y-m-d', strtotime('+3 days')) }}' }">

    @include('layouts.partials.flash')

    <!-- TOP HEADER / CONTEXT BAR -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-4 lg:p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center text-slate-700 border border-red-100">
                <i class="fa-solid fa-truck-moving text-xl text-red-600"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    @if($currentRole === 'logistik')
                        <span class="text-[10px] font-bold uppercase tracking-wider text-red-600">Logistik Pusat</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-slate-100 text-slate-700">LOG-PST-001</span>
                    @elseif($currentRole === 'sam')
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Senior Area Manager</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-slate-100 text-slate-700">SAM-SBR-01</span>
                    @else
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Distributor Regional</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-slate-100 text-slate-700">DST-PDG-014</span>
                    @endif
                </div>
                <h1 class="text-base lg:text-lg font-extrabold text-slate-900">
                    @if($currentRole === 'logistik')
                        Operasional & Distribusi Logistik Nasional
                    @elseif($currentRole === 'sam')
                        Monitoring Pengiriman Wilayah Sumatera Barat
                    @else
                        PT Minang Niaga Jaya (Cabang Padang)
                    @endif
                </h1>
            </div>
        </div>

        <!-- STEPPER TAHAPAN (Dokumen A3) -->
        <div class="flex items-center space-x-3 text-xs">
            <div class="flex items-center space-x-2 bg-red-50 text-red-700 font-bold px-3 py-1.5 rounded-lg border border-red-200">
                <span class="w-5 h-5 rounded-full bg-red-600 text-white flex items-center justify-center text-[10px]">1</span>
                <span>Monitoring Pengiriman</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-200 text-red-800 ml-1">Tahap Aktif</span>
            </div>
            <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
            <div class="flex items-center space-x-2 text-slate-500 px-2 py-1.5">
                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">2</span>
                <span>Daftar Kontainer Aktif</span>
            </div>
            <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
            <div class="flex items-center space-x-2 text-slate-500 px-2 py-1.5">
                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">3</span>
                <span>Peringatan Keterlambatan</span>
            </div>
        </div>
    </div>

    <!-- STATS CARD SECTION (4 Metric Cards) -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Ringkasan Status Pengiriman</h2>
                <p class="text-xs text-slate-500">Pemantauan real-time pengiriman kontainer aktif untuk periode berjalan</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                {{ $stats['total_aktif'] }} Kontainer Aktif
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Kontainer Aktif -->
            <div class="bg-slate-50/80 rounded-xl border border-slate-200/80 p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Kontainer Aktif</span>
                <div class="mt-2 text-3xl font-black text-slate-900">{{ $stats['total_aktif'] }}</div>
                <div class="mt-1 text-xs text-slate-500 font-medium">
                    <span class="font-semibold text-slate-700">{{ $stats['dalam_perjalanan'] }}</span> dalam perjalanan • 
                    <span class="font-semibold text-slate-700">{{ $stats['menunggu_muat'] }}</span> menunggu muat
                </div>
            </div>

            <!-- Kontainer Terlambat -->
            <div class="bg-red-50/50 rounded-xl border border-red-200/80 p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-red-600">Kontainer Terlambat</span>
                <div class="mt-2 text-3xl font-black text-red-600">{{ $stats['total_terlambat'] }}</div>
                <div class="mt-1 text-xs text-red-600/90 font-medium flex items-center gap-1">
                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                    2 hari lebih lambat dari ETA
                </div>
            </div>

            <!-- Volume Pengiriman -->
            <div class="bg-slate-50/80 rounded-xl border border-slate-200/80 p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Volume Pengiriman</span>
                <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($stats['total_volume'], 0, ',', '.') }}</div>
                <div class="mt-1 text-xs text-slate-500 font-medium">
                    {{ number_format($stats['volume_perjalanan'], 0, ',', '.') }} karton dalam perjalanan
                </div>
            </div>

            <!-- Distributor Aktif -->
            <div class="bg-slate-50/80 rounded-xl border border-slate-200/80 p-4">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Distributor Aktif</span>
                <div class="mt-2 text-3xl font-black text-slate-900">{{ $stats['distributor_aktif'] }}</div>
                <div class="mt-1 text-xs text-slate-500 font-medium truncate">
                    Padang, Pekanbaru, Medan, Palembang
                </div>
            </div>
        </div>
    </div>

    <!-- DAFTAR KONTAINER AKTIF (TABLE SECTION) -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Daftar Kontainer Aktif</h3>
                <p class="text-xs text-slate-500">Kontainer yang sedang bergerak, menunggu muat, atau memerlukan perhatian</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-slate-100 text-slate-700">
                    {{ $shipments->count() }} Kontainer Prioritas
                </span>
                @if($currentRole === 'logistik')
                    <button type="button" @click="showInputModal = true" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-sm transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-plus-circle text-sm"></i>
                        <span>Input Pengiriman Baru</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="p-4 bg-slate-50 border-b border-slate-200/80">
            <form method="GET" action="{{ route('monitoring.index') }}" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="role" value="{{ $currentRole }}">

                <!-- Filter Distributor -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600">Distributor:</label>
                    <select name="distributor" onchange="this.form.submit()" class="text-xs rounded-lg border-slate-300 py-1.5 px-3 bg-white font-medium focus:ring-red-500 focus:border-red-500">
                        <option value="semua" {{ $filterDistributor === 'semua' ? 'selected' : '' }}>Semua</option>
                        @foreach($distributorList as $dist)
                            <option value="{{ $dist->id_distributor }}" {{ $filterDistributor == $dist->id_distributor ? 'selected' : '' }}>
                                {{ $dist->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600">Status:</label>
                    <select name="status" onchange="this.form.submit()" class="text-xs rounded-lg border-slate-300 py-1.5 px-3 bg-white font-medium focus:ring-red-500 focus:border-red-500">
                        <option value="semua" {{ $filterStatus === 'semua' ? 'selected' : '' }}>Semua</option>
                        <option value="Dalam Perjalanan" {{ $filterStatus === 'Dalam Perjalanan' ? 'selected' : '' }}>Dalam Perjalanan</option>
                        <option value="Terlambat" {{ $filterStatus === 'Terlambat' ? 'selected' : '' }}>Terlambat</option>
                        <option value="Menunggu Muat" {{ $filterStatus === 'Menunggu Muat' ? 'selected' : '' }}>Menunggu Muat</option>
                        <option value="Selesai" {{ $filterStatus === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>

                <!-- Filter Periode -->
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600">Periode:</label>
                    <select name="periode" onchange="this.form.submit()" class="text-xs rounded-lg border-slate-300 py-1.5 px-3 bg-white font-medium focus:ring-red-500 focus:border-red-500">
                        <option value="current">Periode Berjalan (Nov 2026)</option>
                        <option value="2026-10">Oktober 2026</option>
                        <option value="2026-09">September 2026</option>
                    </select>
                </div>

                @if($filterDistributor || $filterStatus || $filterPeriode)
                <a href="{{ route('monitoring.index') }}" class="text-xs text-red-600 font-semibold hover:underline flex items-center gap-1 ml-auto">
                    <i class="fa-solid fa-rotate-left text-[10px]"></i> Reset Filter
                </a>
                @endif
            </form>
        </div>

        <!-- Table Kontainer -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-100/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-5">No. Kontainer</th>
                        <th class="py-3.5 px-4">Distributor</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">ETA</th>
                        <th class="py-3.5 px-4">Volume</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 font-medium">
                    @forelse($shipments as $shipment)
                    @php
                        $distributorNama = $shipment->po?->forecast?->distributor?->nama ?? 'PT Minang Niaga Jaya';
                        $distributorKota = $shipment->po?->forecast?->distributor?->wilayah?->nama_wilayah ?? 'Padang';

                        // Hitung volume muatan
                        $volumeKarton = 0;
                        foreach($shipment->detailPengirimanRecords as $dp) {
                            $kpk = $dp->detailPo?->produk?->karton_per_kontainer ?? 500;
                            $volumeKarton += $dp->jumlah_dikirim * $kpk;
                        }
                        if ($volumeKarton === 0) {
                            $volumeKarton = 2400;
                        }
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="py-4 px-5">
                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                <i class="fa-solid fa-box text-slate-400 text-xs"></i>
                                {{ $shipment->nomor_kontainer }}
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ $shipment->nama_vessel ?? 'Feeder Vessel' }} &middot; Ref: {{ $shipment->po?->nomor_po ?? 'PO-YFI-2026' }}
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-semibold text-slate-800">{{ $distributorNama }}</div>
                            <div class="text-[11px] text-slate-400">{{ $distributorKota }}</div>
                        </td>
                        <td class="py-4 px-4">
                            @if($shipment->status === 'Dalam Perjalanan')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Dalam Perjalanan
                                </span>
                            @elseif($shipment->status === 'Terlambat')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                                    Terlambat
                                </span>
                            @elseif($shipment->status === 'Menunggu Muat')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Menunggu Muat
                                </span>
                            @elseif($shipment->status === 'Selesai')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    <i class="fa-solid fa-check-double text-[10px]"></i>
                                    Selesai
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    {{ $shipment->status }}
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-4">
                            <div class="font-semibold text-slate-800">
                                {{ \Carbon\Carbon::parse($shipment->eta_terkini)->translatedFormat('d M Y') }}
                            </div>
                            @if($shipment->eta_terkini != $shipment->eta_awal)
                                <div class="text-[10px] text-red-500 font-medium">Revisi dari {{ \Carbon\Carbon::parse($shipment->eta_awal)->format('d/m') }}</div>
                            @endif
                        </td>
                        <td class="py-4 px-4 font-semibold text-slate-800">
                            {{ number_format($volumeKarton, 0, ',', '.') }} Ctn
                        </td>
                        <td class="py-4 px-5 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Tombol Update Status (Khusus Logistik Pusat) -->
                                @if($currentRole === 'logistik')
                                    <a href="{{ route('monitoring.update', $shipment->id_pengiriman) }}" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-lg transition shadow-sm">
                                        <i class="fa-solid fa-truck-fast text-[11px]"></i>
                                        Update Status Kontainer
                                    </a>
                                @endif

                                <!-- Tombol Konfirmasi Penerimaan (Khusus Distributor jika belum diterima dan sudah sampai) -->
                                @if($currentRole === 'distributor' && $shipment->status !== 'Selesai' && !$shipment->penerimaanBarangRecord)
                                    <a href="{{ route('monitoring.konfirmasi', $shipment->id_pengiriman) }}" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition shadow-sm">
                                        <i class="fa-solid fa-clipboard-check text-[11px]"></i>
                                        Konfirmasi Terima
                                    </a>
                                @endif

                                <!-- Tombol Lihat Detail (Semua Aktor) -->
                                <a href="{{ route('monitoring.detail', $shipment->id_pengiriman) }}" 
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs transition">
                                    <span>Lihat Detail</span>
                                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-400"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-box-open text-4xl mb-3 text-slate-300"></i>
                            <p class="font-semibold text-sm">Tidak ada kontainer pengiriman yang sesuai filter.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ETA & PERINGATAN KETERLAMBATAN SECTION (Dokumen A3) -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">ETA & Peringatan Keterlambatan</h3>
                <p class="text-xs text-slate-500">Pantau kontainer yang berisiko keterlambatan dan tindak lanjut segera</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>
                {{ $delayedContainers->count() }} Kontainer Berisiko
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($delayedContainers as $delayed)
            @php
                $distName = $delayed->po?->forecast?->distributor?->nama ?? 'PT Minang Niaga Jaya';
                $destKota = $delayed->po?->forecast?->distributor?->wilayah?->nama_wilayah ?? 'Padang';
                $rute = "Tj. Priok (JKT) &rarr; {$destKota}";
                if ($delayed->nomor_kontainer === 'CNTR-2025-015') {
                    $rute = "Jakarta &rarr; Pekanbaru";
                    $keteranganAlert = "Kontainer tertahan di pelabuhan akibat verifikasi kelengkapan dokumen pabean.";
                } elseif ($delayed->nomor_kontainer === 'CNTR-2025-018') {
                    $rute = "Surabaya &rarr; Padang";
                    $keteranganAlert = "Cuaca buruk memperlambat izin keberangkatan kapal dari pelabuhan asal.";
                } else {
                    $keteranganAlert = $delayed->milestonePengirimanRecords->last()?->keterangan ?? "Terdapat penyesuaian lead time perjalanan laut.";
                }
            @endphp
            <div class="border border-red-200 rounded-xl p-5 bg-gradient-to-br from-red-50/40 via-white to-white shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-base font-black text-slate-900">{{ $delayed->nomor_kontainer }}</span>
                            <div class="text-xs text-slate-500 font-medium mt-0.5">
                                Rute: {!! $rute !!} &middot; <span class="font-semibold text-slate-700">{{ $distName }}</span>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-red-600 text-white">
                            Terlambat
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 my-4 py-3 border-y border-slate-100">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">ETA Awal</span>
                            <div class="text-sm font-bold text-slate-600 mt-0.5">
                                {{ \Carbon\Carbon::parse($delayed->eta_awal)->translatedFormat('d F Y') }}
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-red-600">ETA Revisi</span>
                            <div class="text-sm font-extrabold text-red-600 mt-0.5">
                                {{ \Carbon\Carbon::parse($delayed->eta_terkini)->translatedFormat('d F Y') }}
                            </div>
                        </div>
                    </div>

                    <!-- Alert Box Keterangan -->
                    <div class="bg-red-50/90 border border-red-200/80 rounded-lg p-3 text-xs text-red-800 flex items-start gap-2.5">
                        <i class="fa-solid fa-circle-exclamation text-red-600 mt-0.5"></i>
                        <span class="leading-relaxed">{{ $keteranganAlert }}</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400 font-medium">
                        Kapal: {{ $delayed->nama_vessel ?? 'Feeder Vessel' }}
                    </span>
                    <a href="{{ route('monitoring.detail', $delayed->id_pengiriman) }}" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                        <span>Pantau Perjalanan</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-2 py-8 text-center text-slate-400">
                <i class="fa-solid fa-circle-check text-3xl text-emerald-500 mb-2"></i>
                <p class="font-semibold text-sm text-slate-700">Semua pengiriman berjalan tepat waktu sesuai jadwal ETA.</p>
            </div>
            @endforelse
        </div>
    </div>

    <!-- MODAL INPUT PENGIRIMAN BARU (KHUSUS LOGISTIK PUSAT) -->
    @if($currentRole === 'logistik')
    <div x-show="showInputModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div @click.away="showInputModal = false" 
             x-show="showInputModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-xl w-full overflow-hidden my-8">
            
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-ship text-base"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Input Pengiriman Kontainer Baru</h3>
                        <p class="text-xs text-slate-500">Daftarkan nomor kontainer, armada kapal, dan jadwal ETA pengiriman</p>
                    </div>
                </div>
                <button type="button" @click="showInputModal = false" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('monitoring.store') }}" class="p-6 space-y-4">
                @csrf

                <!-- Pilih E-PO -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pilih Purchase Order (E-PO) Terverifikasi <span class="text-red-500">*</span>
                    </label>
                    <select name="id_po" required class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-red-500 focus:border-red-500 font-medium">
                        <option value="">-- Pilih Nomor E-PO --</option>
                        @foreach($purchaseOrders as $po)
                            <option value="{{ $po->id_po }}">
                                {{ $po->nomor_po }} &middot; {{ $po->forecast?->distributor?->nama ?? 'Distributor' }} ({{ $po->status }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Nomor Kontainer & Nama Vessel -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Nomor Kontainer <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nomor_kontainer" required placeholder="Contoh: CNTR-2026-025" 
                               class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-red-500 focus:border-red-500 font-medium uppercase font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Armada / Feeder Vessel
                        </label>
                        <input type="text" name="nama_vessel" placeholder="Contoh: MV Meratus Sabang V.14" 
                               class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-red-500 focus:border-red-500 font-medium">
                    </div>
                </div>

                <!-- ETA Awal & ETA Terkini -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Estimasi Tiba (ETA Awal) <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="eta_awal" x-model="etaAwal" @change="etaTerkini = etaAwal" required 
                               class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-red-500 focus:border-red-500 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            ETA Terkini (Revisi)
                        </label>
                        <input type="date" name="eta_terkini" x-model="etaTerkini" 
                               class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-red-500 focus:border-red-500 font-medium">
                    </div>
                </div>

                <!-- Status Awal -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Status Awal Pengiriman <span class="text-red-500">*</span>
                    </label>
                    <select name="status" required class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-red-500 focus:border-red-500 font-medium">
                        <option value="Menunggu Muat">Menunggu Muat (Stuffing Pabrik & Dokumen Booking)</option>
                        <option value="Dalam Perjalanan">Dalam Perjalanan (Pelayaran Aktif)</option>
                    </select>
                </div>

                <!-- Catatan / Keterangan Milestone 1 -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Catatan Milestone 1 (Booking Dikonfirmasi)
                    </label>
                    <textarea name="keterangan" rows="2" placeholder="Catatan jadwal muat, kapal, atau kondisi stuffing..." 
                              class="w-full text-xs rounded-xl border-slate-300 p-3 focus:ring-red-500 focus:border-red-500 font-medium"></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="showInputModal = false" class="px-4 py-2.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-lg transition shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span>Simpan & Daftarkan Kontainer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
