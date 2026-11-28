@extends('layouts.app')

@section('title', 'Detail Pengiriman Kontainer')

@section('module_title', 'Modul Monitoring Pengiriman')

@section('content')
<div class="space-y-6">

    @include('layouts.partials.flash')

    <!-- TOP CONTEXT & BREADCRUMBS -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-4 lg:p-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <a href="{{ route('monitoring.index') }}" class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-600 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Distributor Regional</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-slate-100 text-slate-700">
                        {{ $shipment->po?->forecast?->distributor?->nomor_mou ?? 'DST-PDG-014' }}
                    </span>
                </div>
                <h1 class="text-base lg:text-lg font-extrabold text-slate-900">
                    {{ $shipment->po?->forecast?->distributor?->nama ?? 'PT Minang Niaga Jaya (Cabang Padang)' }}
                </h1>
            </div>
        </div>

        <div class="flex items-center space-x-3 text-xs">
            <div class="flex items-center space-x-2 bg-red-50 text-red-700 font-bold px-3 py-1.5 rounded-lg border border-red-200">
                <span class="w-5 h-5 rounded-full bg-red-600 text-white flex items-center justify-center text-[10px]">1</span>
                <span>Detail Kontainer</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-200 text-red-800 ml-1">Tahap Aktif</span>
            </div>
            <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
            <div class="flex items-center space-x-2 text-slate-500 px-2 py-1.5">
                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">2</span>
                <span>Timeline Milestone</span>
            </div>
            <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
            <div class="flex items-center space-x-2 text-slate-500 px-2 py-1.5">
                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-[10px] font-bold">3</span>
                <span>Riwayat Status</span>
            </div>
        </div>
    </div>

    <!-- HEADER DETAIL INFO (CARD SECTION) -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-xl font-black text-slate-900 tracking-tight">Detail Pengiriman Kontainer</h2>
                <div class="text-xs text-slate-500 mt-0.5">
                    Nomor Kontainer: <span class="font-extrabold text-slate-800">{{ $shipment->nomor_kontainer }}</span>
                </div>
            </div>

            <!-- Action Buttons based on role -->
            <div class="flex items-center gap-3">
                @if($currentRole === 'logistik')
                    <a href="{{ route('monitoring.update', $shipment->id_pengiriman) }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-lg transition shadow-sm">
                        <i class="fa-solid fa-pen-to-square"></i>
                        Pembaruan Status
                    </a>
                @elseif($currentRole === 'distributor' && $shipment->status !== 'Selesai' && !$shipment->penerimaanBarangRecord)
                    <a href="{{ route('monitoring.konfirmasi', $shipment->id_pengiriman) }}" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition shadow-sm">
                        <i class="fa-solid fa-clipboard-check"></i>
                        Konfirmasi Penerimaan Barang
                    </a>
                @endif
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-3.5 py-2 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition">
                    <i class="fa-solid fa-print"></i> Cetak Ringkasan
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nomor Kontainer</span>
                <div class="text-base font-black text-slate-900 mt-1">{{ $shipment->nomor_kontainer }}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">40' HC &middot; Muatan Multimoda</div>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Referensi E-PO</span>
                <div class="text-base font-black text-slate-900 mt-1">{{ $shipment->po?->nomor_po ?? 'PO-YFI-2026' }}</div>
                <div class="text-[11px] text-emerald-600 font-semibold mt-0.5 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px]"></i> Dokumen PO Terverifikasi
                </div>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nama Distributor</span>
                <div class="text-base font-black text-slate-900 mt-1 truncate">
                    {{ $shipment->po?->forecast?->distributor?->nama ?? 'PT Minang Niaga Jaya' }}
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">
                    {{ $shipment->po?->forecast?->distributor?->alamat_gudang ?? 'Gudang Regional Barat' }}
                </div>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Status Terakhir</span>
                <div class="mt-1">
                    @if($shipment->status === 'Dalam Perjalanan')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                            Dalam Perjalanan
                        </span>
                    @elseif($shipment->status === 'Terlambat')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-red-100 text-red-800">
                            <span class="w-2 h-2 rounded-full bg-red-600"></span>
                            Terlambat
                        </span>
                    @elseif($shipment->status === 'Selesai')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800">
                            <i class="fa-solid fa-check-double text-[10px]"></i>
                            Selesai & Diterima
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-slate-200 text-slate-800">
                            {{ $shipment->status }}
                        </span>
                    @endif
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Kapal: {{ $shipment->nama_vessel ?? 'MV Sinar Bintan' }}
                </div>
            </div>
        </div>

        <!-- Schedule / Date Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-slate-100 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase text-slate-400">ETA Tiba di Gudang</div>
                    <div class="font-extrabold text-slate-900 text-sm">
                        {{ \Carbon\Carbon::parse($shipment->eta_terkini)->translatedFormat('d M Y') }} &middot; 09:00 WIB
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-ship"></i>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase text-slate-400">Aktual Berangkat</div>
                    <div class="font-bold text-slate-700">
                        {{ \Carbon\Carbon::parse($shipment->eta_awal)->subDays(7)->translatedFormat('d M Y') }} &middot; 06:15 WIB
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
                <div>
                    <div class="text-[10px] font-bold uppercase text-slate-400">Aktual Tiba di Gudang</div>
                    <div class="font-bold text-slate-700">
                        @if($shipment->penerimaanBarangRecord)
                            <span class="text-emerald-700 font-extrabold">
                                {{ \Carbon\Carbon::parse($shipment->penerimaanBarangRecord->tanggal_terima)->translatedFormat('d M Y H:i') }} WIB
                            </span>
                        @else
                            <span class="text-amber-600">Belum Tiba (Menunggu konfirmasi)</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DAFTAR BARANG DALAM KONTAINER (TABLE) -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Daftar Barang dalam Kontainer</h3>
                <p class="text-xs text-slate-500">
                    Referensi E-PO: <span class="font-bold text-slate-700">{{ $shipment->po?->nomor_po ?? 'PO-YFI-2026' }}</span>
                </p>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-full bg-slate-100 text-slate-700">
                {{ $shipment->detailPengirimanRecords->count() ?: 4 }} Entitas SKU
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-100/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-5 w-12 text-center">No</th>
                        <th class="py-3 px-4">Kode SKU</th>
                        <th class="py-3 px-4">Nama Produk & Kemasan</th>
                        <th class="py-3 px-4 text-right">Karton</th>
                        <th class="py-3 px-5 text-right">Nilai Estimasi (Rp)</th>
                        <th class="py-3 px-5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 font-medium">
                    @php 
                        $totalKartonTable = 0;
                        $totalNilaiTable = 0;
                        $dummyItems = [
                            ['sku' => 'YFI-TM-500', 'nama' => 'YFI Teh Melati 500ml', 'ctn' => 2700, 'nilai' => 256500000],
                            ['sku' => 'FN-SPK-325', 'nama' => 'FAN Sparkling Water 325ml', 'ctn' => 2150, 'nilai' => 204250000],
                            ['sku' => 'YFI-JA-330', 'nama' => 'YFI Jus Apel 330ml', 'ctn' => 1450, 'nilai' => 127600000],
                            ['sku' => 'YFI-ISO-330', 'nama' => 'YFI Isotonik Refresh 330ml', 'ctn' => 1280, 'nilai' => 121600000],
                        ];
                    @endphp

                    @if($shipment->detailPengirimanRecords->isNotEmpty())
                        @foreach($shipment->detailPengirimanRecords as $index => $item)
                        @php
                            $produk = $item->detailPo?->produk;
                            $kpk = $produk?->karton_per_kontainer ?? 500;
                            $ctn = $item->jumlah_dikirim * $kpk;
                            $harga = $produk?->harga ?? 95000;
                            $nilai = $ctn * $harga;
                            $totalKartonTable += $ctn;
                            $totalNilaiTable += $nilai;
                        @endphp
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3.5 px-5 text-center font-bold text-slate-400">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-4 font-mono font-bold text-red-600">{{ $produk?->kode_sku ?? 'SKU-001' }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-800">{{ $produk?->nama_produk ?? 'Produk Yoke Food' }}</td>
                            <td class="py-3.5 px-4 text-right font-extrabold text-slate-900">{{ number_format($ctn, 0, ',', '.') }}</td>
                            <td class="py-3.5 px-5 text-right font-bold text-slate-800">Rp {{ number_format($nilai, 0, ',', '.') }}</td>
                            <td class="py-3.5 px-5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                    {{ $shipment->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        @foreach($dummyItems as $idx => $di)
                        @php
                            $totalKartonTable += $di['ctn'];
                            $totalNilaiTable += $di['nilai'];
                        @endphp
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3.5 px-5 text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-4 font-mono font-bold text-red-600">{{ $di['sku'] }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-800">{{ $di['nama'] }}</td>
                            <td class="py-3.5 px-4 text-right font-extrabold text-slate-900">{{ number_format($di['ctn'], 0, ',', '.') }}</td>
                            <td class="py-3.5 px-5 text-right font-bold text-slate-800">Rp {{ number_format($di['nilai'], 0, ',', '.') }}</td>
                            <td class="py-3.5 px-5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                    {{ $shipment->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
                <tfoot class="bg-slate-50 font-black text-slate-900 border-t-2 border-slate-200">
                    <tr>
                        <td colspan="3" class="py-3 px-5 text-right uppercase text-[11px] tracking-wider text-slate-500">Total Akumulasi Muatan:</td>
                        <td class="py-3 px-4 text-right text-sm text-red-600">{{ number_format($totalKartonTable, 0, ',', '.') }} Ctn</td>
                        <td class="py-3 px-5 text-right text-sm">Rp {{ number_format($totalNilaiTable, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- TIMELINE MILESTONE & RIWAYAT STATUS (2 COLUMNS SECTION) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- TIMELINE MILESTONE PERJALANAN (Dokumen A3) -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Timeline Milestone Perjalanan</h3>
                    <p class="text-xs text-slate-500">Tahapan fisik kontainer dari pabrik hingga gudang tujuan</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded bg-slate-100 text-slate-600">
                    {{ $shipment->milestonePengirimanRecords->count() }} Tahap Tercatat
                </span>
            </div>

            <div class="relative space-y-6 before:absolute before:left-3.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200">
                @forelse($shipment->milestonePengirimanRecords as $milestone)
                <div class="relative flex items-start gap-4 group">
                    <span class="relative z-10 w-7 h-7 rounded-full bg-red-600 border-2 border-white shadow-sm flex items-center justify-center text-white text-xs font-black shrink-0">
                        {{ $milestone->urutan }}
                    </span>
                    <div class="min-w-0 flex-1 pt-0.5">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                            <span class="text-sm font-black text-slate-900 tracking-tight">{{ $milestone->nama_tahap }}</span>
                            <span class="text-[11px] font-semibold text-slate-500 shrink-0">
                                {{ \Carbon\Carbon::parse($milestone->tanggal_pembaruan)->translatedFormat('d M Y') }} &middot; {{ \Carbon\Carbon::parse($milestone->tanggal_pembaruan)->format('H:i') }} WIB
                            </span>
                        </div>
                        @if($milestone->keterangan)
                            <p class="text-xs text-slate-600 mt-2 leading-relaxed bg-slate-50 p-3 rounded-lg border border-slate-200/60">
                                {{ $milestone->keterangan }}
                            </p>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center py-6 text-slate-400">Belum ada milestone tercatat.</div>
                @endforelse
            </div>
        </div>

        <!-- RIWAYAT PERUBAHAN STATUS & LOG (Dokumen A3) -->
        <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Riwayat Perubahan Status</h3>
                    <p class="text-xs text-slate-500">Catatan audit log pembaruan status oleh operator logistik</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded bg-slate-100 text-slate-600">
                    Audit Trail
                </span>
            </div>

            <div class="space-y-4">
                @forelse($shipment->milestonePengirimanRecords->reverse() as $log)
                <div class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/50">
                    <div class="w-8 h-8 rounded-lg bg-red-100 text-red-700 font-black flex items-center justify-center text-xs shrink-0">
                        {{ $log->urutan }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-slate-900">{{ $log->nama_tahap }}</span>
                            <span class="text-[10px] text-slate-400 font-semibold">
                                {{ \Carbon\Carbon::parse($log->tanggal_pembaruan)->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            {{ $log->keterangan ?? 'Status diperbarui oleh Tim Logistik Pusat.' }}
                        </p>
                        <div class="text-[10px] text-slate-400 mt-1 flex items-center gap-1">
                            <i class="fa-solid fa-user-check text-[9px]"></i>
                            <span>Diperbarui oleh: <strong>Budi Santoso (Logistik Pusat)</strong></span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-6 text-slate-400">Belum ada riwayat tercatat.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
