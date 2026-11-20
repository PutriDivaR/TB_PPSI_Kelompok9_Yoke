@extends('layouts.app')

@section('title', 'Ajukan Klaim')

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6"
     x-data="{
        kedatangan: {{ \Illuminate\Support\Js::from($kedatangan) }},
        produk: {{ \Illuminate\Support\Js::from($produk) }},
        jenis: {{ \Illuminate\Support\Js::from($jenisKerusakan) }},
        pilihId: '',
        baris: [],
        fmt(n) { return new Intl.NumberFormat('id-ID').format(n) },
        tambah() { this.baris.push({ uid: Date.now() + Math.random(), produk: '', jenis: '', karton: 1, foto: [] }) },
        hapus(i) { this.baris[i].foto.forEach(f => URL.revokeObjectURL(f.url)); this.baris.splice(i, 1) },
        pilihFoto(b, e) { [...e.target.files].forEach(f => b.foto.push({ nama: f.name, url: URL.createObjectURL(f) })); e.target.value = '' },
        hapusFoto(b, j) { URL.revokeObjectURL(b.foto[j].url); b.foto.splice(j, 1) },
        namaProduk(kode) { return this.produk.find(p => p.kode === kode)?.nama ?? 'Produk belum dipilih' },
        get terpilih() { return this.kedatangan.find(k => String(k.id) === String(this.pilihId)) },
        get totalKarton() { return this.baris.reduce((a, b) => a + Number(b.karton || 0), 0) },
        get lengkap() { return !!this.terpilih && this.baris.length > 0 && this.baris.every(b => b.produk && b.jenis && Number(b.karton) >= 1) },
        init() { this.tambah() }
     }">

    @include('layouts.partials.flash')

    <div>
        <h1 class="text-xl font-bold text-slate-900">Formulir Pengajuan Klaim</h1>
        <p class="text-sm text-slate-500">{{ $distributor['nama'] }} &middot; {{ $distributor['wilayah'] }}</p>
    </div>

    {{-- Tahap 1 --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <span class="w-7 h-7 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">1</span>
            <div>
                <p class="font-bold text-slate-900">Pilih Kedatangan</p>
                <p class="text-xs text-slate-500">Kontainer yang sudah dikonfirmasi diterima di gudang.</p>
            </div>
        </div>
        <select x-model="pilihId" class="mt-4 w-full md:w-[28rem] px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white">
            <option value="">Pilih kontainer...</option>
            @foreach ($kedatangan as $k)
                <option value="{{ $k['id'] }}">{{ $k['nomor_kontainer'] }} &middot; {{ $k['nomor_po'] }} &middot; tiba {{ $k['tanggal_tiba'] }}</option>
            @endforeach
        </select>

        <template x-if="terpilih">
            <div class="mt-4 grid grid-cols-2 lg:grid-cols-6 gap-3">
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor PO</p><p class="mt-1 font-mono text-xs font-bold text-red-600" x-text="terpilih.nomor_po"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Kontainer</p><p class="mt-1 font-semibold text-slate-900" x-text="terpilih.nomor_kontainer"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Tiba</p><p class="mt-1 font-semibold text-slate-900" x-text="terpilih.tanggal_tiba"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Menurut Invoice</p><p class="mt-1 font-semibold text-slate-900" x-text="fmt(terpilih.jumlah_invoice) + ' karton'"></p></div>
                <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Fisik Diterima</p><p class="mt-1 font-semibold text-slate-900" x-text="fmt(terpilih.jumlah_diterima) + ' karton'"></p></div>
                <div class="rounded-lg p-3" :class="terpilih.selisih !== 0 ? 'bg-red-50 text-red-700' : 'bg-slate-50 text-slate-900'">
                    <p class="text-[10px] font-bold uppercase tracking-wider" :class="terpilih.selisih !== 0 ? 'text-red-700' : 'text-slate-500'">Selisih</p>
                    <p class="mt-1 font-semibold" x-text="(terpilih.selisih > 0 ? '+' : '') + fmt(terpilih.selisih) + ' karton'"></p>
                </div>
            </div>
        </template>
    </div>

    {{-- Tahap 2 --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="p-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">2</span>
                <div>
                    <p class="font-bold text-slate-900">Rincian Kerusakan</p>
                    {{-- Sementara: klaim dicatat dalam karton; satuan kaleng menunggu wawancara lanjutan. --}}
                    <p class="text-xs text-slate-500">Satu baris untuk setiap produk dan jenis kerusakan, dalam karton.</p>
                </div>
            </div>
            <button type="button" @click="tambah()" class="text-[11px] font-semibold text-slate-600 border border-slate-200 px-3 py-1.5 rounded hover:bg-slate-50"><i class="fa-solid fa-plus mr-1"></i>Tambah Baris</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left w-12">No</th>
                        <th class="px-5 py-3 text-left">Produk</th>
                        <th class="px-5 py-3 text-left">Jenis Kerusakan</th>
                        <th class="px-5 py-3 text-right w-40">Jumlah Karton</th>
                        <th class="px-5 py-3 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(b, i) in baris" :key="b.uid">
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-500" x-text="i + 1"></td>
                            <td class="px-5 py-3">
                                <select x-model="b.produk" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg bg-white">
                                    <option value="">Pilih produk...</option>
                                    <template x-for="p in produk" :key="p.kode"><option :value="p.kode" x-text="p.kode + ' - ' + p.nama"></option></template>
                                </select>
                            </td>
                            <td class="px-5 py-3">
                                <select x-model="b.jenis" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg bg-white">
                                    <option value="">Pilih jenis...</option>
                                    <template x-for="nama in Object.keys(jenis)" :key="nama"><option :value="nama" x-text="nama"></option></template>
                                </select>
                            </td>
                            <td class="px-5 py-3"><input type="number" min="1" x-model.number="b.karton" class="w-full px-3 py-1.5 text-sm text-right border border-slate-200 rounded-lg"></td>
                            <td class="px-5 py-3 text-center">
                                <button type="button" @click="hapus(i)" class="text-slate-400 hover:text-red-600" aria-label="Hapus baris"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="baris.length === 0">
                        <td colspan="5" class="px-5 py-10 text-center text-slate-500">Belum ada baris kerusakan. Klik Tambah Baris untuk memulai.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tahap 3 --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <span class="w-7 h-7 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">3</span>
            <div>
                <p class="font-bold text-slate-900">Penanggung Jawab</p>
                <p class="text-xs text-slate-500">Ditentukan otomatis dari jenis kerusakan pada tahap 2.</p>
            </div>
        </div>
        <div class="mt-4 space-y-2">
            <template x-for="(b, i) in baris" :key="b.uid">
                <div class="rounded-lg border border-slate-200 p-3 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-900" x-text="'Baris ' + (i + 1) + ' · ' + namaProduk(b.produk) + (b.jenis ? ' · ' + b.jenis : '')"></p>
                        <p class="text-xs text-slate-500" x-text="b.jenis ? jenis[b.jenis].alasan : 'Pilih jenis kerusakan pada tahap 2.'"></p>
                    </div>
                    <span x-show="b.jenis" class="inline-flex text-[10px] font-bold px-2 py-1 rounded"
                          :class="b.jenis && jenis[b.jenis].penanggung === 'Perusahaan' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700'"
                          x-text="b.jenis ? 'Ditanggung ' + jenis[b.jenis].penanggung : ''"></span>
                </div>
            </template>
            <p x-show="baris.length === 0" class="text-sm text-slate-500">Belum ada baris kerusakan.</p>
        </div>
    </div>

    {{-- Tahap 4 --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <div class="flex items-center gap-3">
            <span class="w-7 h-7 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">4</span>
            <div>
                <p class="font-bold text-slate-900">Foto Bukti</p>
                {{-- Sementara: belum ada jumlah foto minimal, menunggu wawancara lanjutan. --}}
                <p class="text-xs text-slate-500">Unggah foto untuk setiap baris kerusakan.</p>
            </div>
        </div>
        <div class="mt-4 space-y-4">
            <template x-for="(b, i) in baris" :key="b.uid">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-900" x-text="'Baris ' + (i + 1) + ' · ' + namaProduk(b.produk) + (b.jenis ? ' · ' + b.jenis : '')"></p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <template x-for="(f, j) in b.foto" :key="f.url">
                            <div class="relative w-24 h-24">
                                <img :src="f.url" :alt="f.nama" class="w-24 h-24 object-cover rounded-lg border border-slate-200">
                                <button type="button" @click="hapusFoto(b, j)" class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-red-600 text-xs shadow-sm" aria-label="Hapus foto"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </template>
                        <label class="w-24 h-24 rounded-lg border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-1 text-slate-400 hover:border-red-500 hover:text-red-600 cursor-pointer">
                            <i class="fa-solid fa-camera"></i>
                            <span class="text-[10px] font-bold">Tambah Foto</span>
                            <input type="file" accept="image/*" multiple class="hidden" @change="pilihFoto(b, $event)">
                        </label>
                    </div>
                </div>
            </template>
            <p x-show="baris.length === 0" class="text-sm text-slate-500">Belum ada baris kerusakan.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Karton Diklaim</p>
            <p class="mt-1 text-3xl font-extrabold text-slate-900"><span x-text="fmt(totalKarton)">0</span> <span class="text-xs text-slate-500">Karton</span></p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('klaim.distributor.beranda') }}" class="px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50">Batal</a>
            {{-- Pengiriman ke server belum dibuat; tombol aktif setelah kedatangan dan semua baris terisi. --}}
            <button type="button" :disabled="!lengkap" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fa-solid fa-paper-plane"></i> Kirim Klaim
            </button>
        </div>
    </div>
</div>
@endsection
