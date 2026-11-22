@extends('layouts.app')

@section('title', 'Pembaruan Status Pengiriman')

@section('module_title', 'Modul Monitoring Pengiriman')

@section('content')
<div class="space-y-6">

    @include('layouts.partials.flash')

    <!-- TOP HEADER -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-4 lg:p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-red-600 flex items-center gap-1">
                    <i class="fa-solid fa-shield-halved"></i> Modul Monitoring Pengiriman
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-red-100 text-red-700">
                    Khusus Tim Logistik Pusat & Distributor
                </span>
            </div>
            <h1 class="text-xl font-black text-slate-900 mt-1">Pembaruan Status Pengiriman</h1>
            <p class="text-xs text-slate-500">
                Perbarui status kontainer yang sedang dalam pengiriman, mulai dari barang sampai hingga selesai.
            </p>
        </div>
        <a href="{{ route('monitoring.detail', $shipment->id_pengiriman) }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Detail
        </a>
    </div>

    <!-- 7-STEP PROGRESS TIMELINE (Dokumen A3) -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6 overflow-x-auto">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-slate-900">Progres Pengiriman</h3>
            <span class="text-xs text-slate-500 font-semibold">
                Kontainer: <strong class="text-slate-900">{{ $shipment->nomor_kontainer }}</strong> &middot; Tj. Priok &rarr; {{ $shipment->po?->forecast?->distributor?->wilayah?->nama_wilayah ?? 'Teluk Bayur' }}
            </span>
        </div>

        @php
            $maxUrutan = (int) $shipment->milestonePengirimanRecords()->max('urutan');
        @endphp

        <div class="min-w-[700px] flex items-center justify-between relative mt-6 mb-2">
            <!-- Line Background -->
            <div class="absolute left-6 right-6 top-1/2 -translate-y-1/2 h-1 bg-slate-200 -z-0"></div>
            <div class="absolute left-6 top-1/2 -translate-y-1/2 h-1 bg-emerald-500 -z-0" 
                 style="width: {{ min(100, max(0, ($maxUrutan - 1) / 6 * 100)) }}%;"></div>

            @foreach($milestoneStages as $stepNum => $stepName)
            @php
                $isPassed = $stepNum <= $maxUrutan;
                $isCurrent = $stepNum === ($maxUrutan + 1);
            @endphp
            <div class="relative z-10 flex flex-col items-center text-center w-28">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shadow-sm transition
                    {{ $isPassed ? 'bg-emerald-600 text-white' : ($isCurrent ? 'bg-red-600 text-white ring-4 ring-red-100 animate-pulse' : 'bg-white border-2 border-slate-300 text-slate-400') }}">
                    @if($isPassed)
                        <i class="fa-solid fa-check text-[11px]"></i>
                    @else
                        {{ $stepNum }}
                    @endif
                </div>
                <span class="text-[10px] font-bold mt-2 leading-tight {{ $isPassed ? 'text-slate-900' : ($isCurrent ? 'text-red-600' : 'text-slate-400') }}">
                    {{ $stepName }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- MAIN FORM & SIDEBAR SUMMARY (GRID SECTION) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- FORM PEMBARUAN (2 COLUMNS) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Form Pembaruan Status</h2>
                    <p class="text-xs text-slate-500">Lengkapi langkah berikut untuk mencatat kejadian terbaru di lapangan.</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded bg-slate-100 text-slate-600">5 Langkah</span>
            </div>

            <form method="POST" action="{{ route('monitoring.milestone.store', $shipment->id_pengiriman) }}" class="space-y-6">
                @csrf

                <!-- Langkah 1: Pilih Pengiriman / Kontainer -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-800 text-white text-[9px] mr-1.5">1</span>
                        Pengiriman / Kontainer Terpilih
                    </label>
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-3.5 flex items-center justify-between">
                        <div>
                            <div class="font-extrabold text-sm text-slate-900">{{ $shipment->nomor_kontainer }}</div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                {{ $shipment->nama_vessel ?? 'MV Sinar Bintan' }} &middot; Ref: {{ $shipment->po?->nomor_po ?? 'PO-YFI-2026' }}
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            {{ $shipment->status }}
                        </span>
                    </div>
                </div>

                <!-- Langkah 2: Pilih Milestone Terbaru -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-800 text-white text-[9px] mr-1.5">2</span>
                        Pilih Milestone Terbaru
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ selectedMilestone: 'Dalam Perjalanan' }">
                        @foreach($milestoneStages as $sNum => $sName)
                        <label class="relative flex items-center p-3.5 rounded-xl border cursor-pointer transition hover:bg-slate-50"
                               :class="selectedMilestone === '{{ $sName }}' ? 'border-red-500 bg-red-50/30 ring-2 ring-red-100' : 'border-slate-200'">
                            <input type="radio" name="nama_tahap" value="{{ $sName }}" 
                                   @click="selectedMilestone = '{{ $sName }}'"
                                   {{ $loop->first ? 'checked' : '' }} class="text-red-600 focus:ring-red-500 mr-3">
                            <div>
                                <span class="font-bold text-xs text-slate-900 block">{{ $sName }}</span>
                                <span class="text-[10px] text-slate-500 block mt-0.5">Tahap ke-{{ $sNum }} pengiriman</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <!-- Langkah 3: Input Tanggal & Waktu -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-800 text-white text-[9px] mr-1.5">3</span>
                        Input Tanggal dan Waktu Kejadian
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <input type="date" name="tanggal_pembaruan" value="{{ date('Y-m-d') }}" required
                                   class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-red-500 focus:border-red-500 font-medium">
                        </div>
                        <div>
                            <input type="time" name="waktu_pembaruan" value="{{ date('H:i') }}" required
                                   class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-red-500 focus:border-red-500 font-medium">
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Isi sesuai waktu kejadian sebenarnya di lapangan, bukan waktu saat input.</p>
                </div>

                <!-- Langkah 4: Tambahkan Catatan -->
                <div x-data="{ count: 0 }">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-800 text-white text-[9px] mr-1.5">4</span>
                        Tambahkan Catatan Bila Diperlukan
                    </label>
                    <textarea name="keterangan" rows="3" maxlength="250" @input="count = $el.value.length"
                              placeholder="Contoh: Kontainer tiba di gudang DC Padang By-Pass, segel utuh dan kondisi muatan baik..."
                              class="w-full text-xs rounded-xl border-slate-300 p-3 focus:ring-red-500 focus:border-red-500 font-medium"></textarea>
                    <div class="text-right text-[10px] text-slate-400 mt-1">
                        <span x-text="count">0</span> / 250 karakter
                    </div>
                </div>

                <!-- Langkah 5: Tombol Simpan -->
                <div class="pt-4 border-t border-slate-100 flex items-center gap-3">
                    <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-lg transition shadow-sm">
                        Simpan Pembaruan Status
                    </button>
                    <a href="{{ route('monitoring.detail', $shipment->id_pengiriman) }}" class="px-5 py-2.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>

        <!-- SIDEBAR RINGKASAN PENGIRIMAN (1 COLUMN) -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ringkasan Pengiriman</span>
                <h3 class="text-xl font-black text-slate-900 mt-1">{{ $shipment->nomor_kontainer }}</h3>

                <div class="divide-y divide-slate-100 text-xs mt-4">
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">No. Pengiriman</span>
                        <span class="font-bold text-slate-800">SHP-{{ date('Y') }}-{{ str_pad($shipment->id_pengiriman, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Rute</span>
                        <span class="font-bold text-slate-800">Tj. Priok &rarr; Teluk Bayur</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Moda / Ukuran</span>
                        <span class="font-bold text-slate-800">Laut &middot; 40 ft FCL</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">ETA</span>
                        <span class="font-bold text-slate-800">{{ \Carbon\Carbon::parse($shipment->eta_terkini)->translatedFormat('D, d M Y') }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Distributor</span>
                        <span class="font-bold text-slate-800">{{ $shipment->po?->forecast?->distributor?->nama ?? 'PT Minang Niaga Jaya' }}</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Gudang Tujuan</span>
                        <span class="font-bold text-slate-800">DC Padang By-Pass No. 88</span>
                    </div>
                    <div class="py-2.5 flex justify-between">
                        <span class="text-slate-500">Muatan</span>
                        <span class="font-bold text-red-600">4 SKU &middot; 9.500 Ctn</span>
                    </div>
                </div>
            </div>

            <!-- Box Aturan Status (Dokumen A3) -->
            <div class="bg-blue-50/70 border border-blue-200/80 rounded-xl p-4 text-xs text-blue-900">
                <div class="font-bold flex items-center gap-2 mb-1.5">
                    <i class="fa-solid fa-circle-info text-blue-600"></i>
                    <span>Alur Status</span>
                </div>
                <p class="leading-relaxed text-[11px] text-blue-800/90">
                    Status <strong>Selesai</strong> hanya dapat dipilih setelah status <strong>Barang Sudah Sampai</strong> tercatat. Pembaruan dapat dilakukan oleh Tim Logistik Pusat atau Distributor.
                </p>
            </div>
        </div>

    </div>

</div>
@endsection
