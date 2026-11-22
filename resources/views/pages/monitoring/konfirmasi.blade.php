@extends('layouts.app')

@section('title', 'Konfirmasi Penerimaan Barang')

@section('module_title', 'Modul Monitoring Pengiriman')

@section('content')
<div class="space-y-6">

    @include('layouts.partials.flash')

    <!-- TOP HEADER -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-4 lg:p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 flex items-center gap-1">
                    <i class="fa-solid fa-clipboard-check"></i> Modul Monitoring Pengiriman
                </span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-emerald-100 text-emerald-800">
                    Khusus Distributor
                </span>
            </div>
            <h1 class="text-xl font-black text-slate-900 mt-1">Konfirmasi Penerimaan Barang</h1>
            <p class="text-xs text-slate-500">
                Konfirmasi bahwa barang telah diterima di gudang distributor, lengkap dengan kondisi dan catatan penerimaan.
            </p>
        </div>
        <a href="{{ route('monitoring.detail', $shipment->id_pengiriman) }}" class="inline-flex items-center gap-2 px-4 py-2 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Detail
        </a>
    </div>

    <!-- MAIN FORM & SIDEBAR (GRID) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- FORM KONFIRMASI (2 COLUMNS) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
                <div>
                    <h2 class="text-base font-extrabold text-slate-900">Form Konfirmasi Penerimaan</h2>
                    <p class="text-xs text-slate-500">Lengkapi data penerimaan aktual barang di gudang distributor.</p>
                </div>
                <span class="text-xs font-bold px-2.5 py-1 rounded bg-slate-100 text-slate-600">5 Langkah</span>
            </div>

            <form method="POST" action="{{ route('monitoring.milestone.store', $shipment->id_pengiriman) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                <input type="hidden" name="nama_tahap" value="Selesai">

                <!-- 1. Tanggal Penerimaan Aktual -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-800 text-white text-[9px] mr-1.5">1</span>
                        Tanggal Penerimaan Aktual
                    </label>
                    <div class="relative max-w-sm">
                        <input type="date" name="tanggal_pembaruan" value="{{ date('Y-m-d') }}" required
                               class="w-full text-xs rounded-xl border-slate-300 py-2.5 px-3 focus:ring-emerald-500 focus:border-emerald-500 font-medium">
                        <span class="absolute right-3 top-2.5 text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">
                            Hari Ini
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Isi tanggal barang benar-benar tiba di gudang, bukan tanggal saat konfirmasi.</p>
                </div>

                <!-- 2. Kondisi Penerimaan Barang -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-800 text-white text-[9px] mr-1.5">2</span>
                        Kondisi Penerimaan Barang
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-data="{ kondisi: 'Diterima Baik' }">
                        <label class="p-3.5 rounded-xl border cursor-pointer transition"
                               :class="kondisi === 'Diterima Baik' ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-100' : 'border-slate-200 hover:bg-slate-50'">
                            <div class="flex items-start gap-2.5">
                                <input type="radio" name="kondisi" value="Diterima Baik" @click="kondisi = 'Diterima Baik'" checked class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                                <div>
                                    <div class="font-bold text-xs text-slate-900">Diterima Baik</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Barang lengkap dan kondisi sesuai</div>
                                </div>
                            </div>
                        </label>

                        <label class="p-3.5 rounded-xl border cursor-pointer transition"
                               :class="kondisi === 'Rusak Sebagian' ? 'border-amber-500 bg-amber-50/40 ring-2 ring-amber-100' : 'border-slate-200 hover:bg-slate-50'">
                            <div class="flex items-start gap-2.5">
                                <input type="radio" name="kondisi" value="Rusak Sebagian" @click="kondisi = 'Rusak Sebagian'" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                <div>
                                    <div class="font-bold text-xs text-slate-900">Rusak Sebagian</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Sebagian barang rusak atau penyok</div>
                                </div>
                            </div>
                        </label>

                        <label class="p-3.5 rounded-xl border cursor-pointer transition"
                               :class="kondisi === 'Jumlah Kurang' ? 'border-amber-500 bg-amber-50/40 ring-2 ring-amber-100' : 'border-slate-200 hover:bg-slate-50'">
                            <div class="flex items-start gap-2.5">
                                <input type="radio" name="kondisi" value="Jumlah Kurang" @click="kondisi = 'Jumlah Kurang'" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                <div>
                                    <div class="font-bold text-xs text-slate-900">Jumlah Kurang</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Tidak sesuai dokumen pengiriman</div>
                                </div>
                            </div>
                        </label>

                        <label class="p-3.5 rounded-xl border cursor-pointer transition"
                               :class="kondisi === 'Ditolak' ? 'border-red-500 bg-red-50/40 ring-2 ring-red-100' : 'border-slate-200 hover:bg-slate-50'">
                            <div class="flex items-start gap-2.5">
                                <input type="radio" name="kondisi" value="Ditolak" @click="kondisi = 'Ditolak'" class="mt-0.5 text-red-600 focus:ring-red-500">
                                <div>
                                    <div class="font-bold text-xs text-slate-900">Ditolak</div>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Barang tidak dapat diterima</div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 3. Catatan Penerimaan -->
                <div x-data="{ count: 0 }">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-800 text-white text-[9px] mr-1.5">3</span>
                        Catatan Penerimaan
                    </label>
                    <textarea name="keterangan" rows="3" maxlength="250" @input="count = $el.value.length"
                              placeholder="Barang diterima lengkap, segel kontainer utuh. Tidak ditemukan kerusakan saat pembongkaran di gudang."
                              class="w-full text-xs rounded-xl border-slate-300 p-3 focus:ring-emerald-500 focus:border-emerald-500 font-medium"></textarea>
                    <div class="text-right text-[10px] text-slate-400 mt-1">
                        <span x-text="count">0</span> / 250 karakter
                    </div>
                </div>

                <!-- 4. Upload Foto Bukti Penerimaan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-800 text-white text-[9px] mr-1.5">4</span>
                        Upload Foto Bukti Penerimaan
                    </label>
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:bg-slate-50 transition cursor-pointer">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 mb-2"></i>
                        <p class="text-xs font-bold text-slate-700">Tarik & lepas foto di sini</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">JPG atau PNG &middot; maks. 5 MB per foto &middot; hingga 5 foto</p>
                        <button type="button" class="mt-3 px-4 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            Pilih Foto
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1.5 flex items-center gap-1">
                        <i class="fa-solid fa-info-circle"></i> Wajib melampirkan minimal 1 foto sebagai bukti penerimaan di gudang.
                    </p>
                </div>

                <!-- 5. Tombol Konfirmasi -->
                <div class="pt-4 border-t border-slate-100 flex items-center gap-3">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg transition shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-circle-check"></i>
                        Konfirmasi Penerimaan
                    </button>
                    <a href="{{ route('monitoring.detail', $shipment->id_pengiriman) }}" class="px-5 py-2.5 border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-xs rounded-lg transition">
                        Kembali
                    </a>
                </div>
            </form>
        </div>

        <!-- SIDEBAR RINGKASAN & MUATAN (1 COLUMN) -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ringkasan Pengiriman</span>
                <div class="flex items-center justify-between mt-1">
                    <h3 class="text-xl font-black text-slate-900">{{ $shipment->nomor_kontainer }}</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                        Menunggu Konfirmasi
                    </span>
                </div>

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
                        <span class="font-bold text-slate-800">Laut &middot; 20 ft FCL</span>
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
                </div>
            </div>

            <!-- MUATAN YANG DITERIMA (Dokumen A3) -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Muatan yang Diterima</span>
                
                <div class="divide-y divide-slate-100 text-xs mt-3">
                    <div class="py-2 flex justify-between">
                        <span class="text-slate-700 font-semibold">Teh Madu Asli Botol 500ml</span>
                        <span class="font-black text-slate-900">3.500 Ctn</span>
                    </div>
                    <div class="py-2 flex justify-between">
                        <span class="text-slate-700 font-semibold">Sparkling Water Kaleng 325ml</span>
                        <span class="font-black text-slate-900">2.500 Ctn</span>
                    </div>
                    <div class="py-2 flex justify-between">
                        <span class="text-slate-700 font-semibold">Jus Apel Segar Kotak 330ml</span>
                        <span class="font-black text-slate-900">2.000 Ctn</span>
                    </div>
                    <div class="py-2 flex justify-between">
                        <span class="text-slate-700 font-semibold">Minuman Isotonik Kaleng 250ml</span>
                        <span class="font-black text-slate-900">1.500 Ctn</span>
                    </div>
                    <div class="pt-3 pb-1 flex justify-between border-t-2 border-slate-200 font-black text-sm">
                        <span class="text-slate-800">Total Diterima:</span>
                        <span class="text-emerald-600">9.500 Ctn</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
