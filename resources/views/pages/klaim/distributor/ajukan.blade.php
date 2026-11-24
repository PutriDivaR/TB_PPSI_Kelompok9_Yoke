@extends('layouts.app')

@section('title', 'Ajukan Klaim')

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6"
     x-data="{
        kedatangan: {{ \Illuminate\Support\Js::from($kedatangan) }},
        jenis: {{ \Illuminate\Support\Js::from($jenisKerusakan) }},
        pilihId: @js($pilihAwal),
        baris: [],
        mengirim: false,
        init() { this.muat(); this.$watch('pilihId', () => this.muat()) },
        fmt(n) { return new Intl.NumberFormat('id-ID').format(n) },
        barisBaru(produk = '', jenis = '', karton = 1) { return { uid: Date.now() + Math.random(), produk, jenis, karton, foto: [] } },
        muat() {
            this.baris.forEach(b => b.foto.forEach(f => URL.revokeObjectURL(f.url)));
            const lama = this.terpilih?.dikembalikan;
            this.baris = lama ? lama.baris.map(b => this.barisBaru(String(b.id_detail_penerimaan), b.jenis, b.karton)) : [this.barisBaru()];
        },
        tambah() { this.baris.push(this.barisBaru()) },
        hapus(i) { this.baris[i].foto.forEach(f => URL.revokeObjectURL(f.url)); this.baris.splice(i, 1) },
        pilihFoto(b, e) { [...e.target.files].forEach(f => b.foto.push({ file: f, nama: f.name, url: URL.createObjectURL(f) })); e.target.value = '' },
        hapusFoto(b, j) { URL.revokeObjectURL(b.foto[j].url); b.foto.splice(j, 1) },
        sinkron(el, b) { const dt = new DataTransfer(); b.foto.forEach(f => dt.items.add(f.file)); el.files = dt.files },
        produkDari(id) { return this.terpilih?.baris.find(p => String(p.id) === String(id)) },
        namaProduk(id) { return this.produkDari(id)?.nama ?? 'Produk belum dipilih' },
        get terpilih() { return this.kedatangan.find(k => String(k.id) === String(this.pilihId)) },
        get totalKarton() { return this.baris.reduce((a, b) => a + Number(b.karton || 0), 0) },
        get kelebihan() {
            const total = {};
            this.baris.forEach(b => { if (b.produk) total[b.produk] = (total[b.produk] || 0) + Number(b.karton || 0) });
            return Object.entries(total).filter(([id, n]) => n > (this.produkDari(id)?.maks ?? 0))
                .map(([id, n]) => this.namaProduk(id) + ': ' + this.fmt(n) + ' karton, maksimal ' + this.fmt(this.produkDari(id)?.maks ?? 0));
        },
        get lengkap() { return !!this.terpilih && this.baris.length > 0 && this.baris.every(b => b.produk && b.jenis && Number(b.karton) >= 1) && this.kelebihan.length === 0 }
     }">

    @include('layouts.partials.flash')

    <div>
        <h1 class="text-xl font-bold text-slate-900">Formulir Pengajuan Klaim</h1>
        <p class="text-sm text-slate-500">{{ $distributor->nama }} &middot; {{ $distributor->wilayah?->nama_wilayah }}</p>
    </div>

    @if ($kedatangan->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
        <i class="fa-solid fa-truck-ramp-box text-3xl text-slate-300"></i>
        <h2 class="mt-3 text-lg font-bold text-slate-900">Belum ada kedatangan yang bisa diklaim</h2>
        <p class="mt-1 text-sm text-slate-500">Klaim dapat diajukan setelah penerimaan kontainer dikonfirmasi di gudang.</p>
        <a href="{{ route('klaim.distributor.beranda') }}" class="mt-4 inline-block px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50">Kembali ke Beranda</a>
    </div>
    @else
    <form method="POST" action="{{ route('klaim.distributor.simpan') }}" enctype="multipart/form-data" class="space-y-6" @submit="mengirim = true">
        @csrf

        {{-- Tahap 1 --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">1</span>
                <div>
                    <p class="font-bold text-slate-900">Pilih Kedatangan</p>
                    <p class="text-xs text-slate-500">Kontainer yang sudah dikonfirmasi diterima di gudang.</p>
                </div>
            </div>
            <select name="id_penerimaan" x-model="pilihId" class="mt-4 w-full md:w-[28rem] px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white">
                <option value="">Pilih kontainer...</option>
                @foreach ($kedatangan as $k)
                    <option value="{{ $k['id'] }}">{{ $k['nomor_kontainer'] }} &middot; {{ $k['nomor_po'] }} &middot; tiba {{ $k['tanggal_tiba'] }}{{ $k['dikembalikan'] ? ' (perlu diperbaiki)' : '' }}</option>
                @endforeach
            </select>

            <template x-if="terpilih">
                <div>
                    <div x-show="terpilih.dikembalikan" class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 flex items-start gap-3">
                        <i class="fa-solid fa-rotate-left mt-0.5"></i>
                        <p><span class="font-bold" x-text="terpilih.dikembalikan?.kode"></span> dikembalikan SAM: <span x-text="terpilih.dikembalikan?.catatan"></span> Isian lama sudah dimuat; unggah ulang foto bukti sebelum mengirim.</p>
                    </div>
                    <div class="mt-4 grid grid-cols-2 lg:grid-cols-6 gap-3">
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor PO</p><p class="mt-1 font-mono text-xs font-bold text-red-600" x-text="terpilih.nomor_po"></p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Kontainer</p><p class="mt-1 font-semibold text-slate-900" x-text="terpilih.nomor_kontainer"></p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Tiba</p><p class="mt-1 font-semibold text-slate-900" x-text="terpilih.tanggal_tiba"></p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Menurut Invoice</p><p class="mt-1 font-semibold text-slate-900" x-text="fmt(terpilih.jumlah_invoice) + ' karton'"></p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Fisik Diterima</p><p class="mt-1 font-semibold text-slate-900" x-text="fmt(terpilih.jumlah_diterima) + ' karton'"></p></div>
                        <div class="rounded-lg p-3" :class="terpilih.jumlah_diterima !== terpilih.jumlah_invoice ? 'bg-red-50 text-red-700' : 'bg-slate-50 text-slate-900'">
                            <p class="text-[10px] font-bold uppercase tracking-wider" :class="terpilih.jumlah_diterima !== terpilih.jumlah_invoice ? 'text-red-700' : 'text-slate-500'">Selisih</p>
                            <p class="mt-1 font-semibold" x-text="fmt(terpilih.jumlah_diterima - terpilih.jumlah_invoice) + ' karton'"></p>
                        </div>
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
                        <p class="text-xs text-slate-500">Satu baris untuk setiap produk dan jenis kerusakan, dalam karton. Batas per produk = barang kurang + tidak layak jual.</p>
                    </div>
                </div>
                <button type="button" @click="tambah()" :disabled="!terpilih" class="text-[11px] font-semibold text-slate-600 border border-slate-200 px-3 py-1.5 rounded hover:bg-slate-50 disabled:opacity-50"><i class="fa-solid fa-plus mr-1"></i>Tambah Baris</button>
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
                                    <select :name="'baris[' + i + '][id_detail_penerimaan]'" x-model="b.produk" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg bg-white">
                                        <option value="">Pilih produk...</option>
                                        <template x-for="p in terpilih?.baris ?? []" :key="p.id">
                                            <option :value="String(p.id)" :selected="String(p.id) === b.produk" :disabled="p.maks === 0" x-text="p.kode + ' - ' + p.nama + ' (maks ' + fmt(p.maks) + ' karton)'"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-5 py-3">
                                    <select :name="'baris[' + i + '][jenis_kerusakan]'" x-model="b.jenis" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg bg-white">
                                        <option value="">Pilih jenis...</option>
                                        <template x-for="nama in Object.keys(jenis)" :key="nama"><option :value="nama" :selected="nama === b.jenis" x-text="nama"></option></template>
                                    </select>
                                </td>
                                <td class="px-5 py-3"><input type="number" min="1" :name="'baris[' + i + '][jumlah]'" x-model.number="b.karton" class="w-full px-3 py-1.5 text-sm text-right border border-slate-200 rounded-lg"></td>
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
            <div x-show="kelebihan.length" class="mx-5 mb-5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs px-4 py-3">
                <p class="font-bold">Jumlah klaim melebihi barang kurang atau rusak pada penerimaan:</p>
                <ul class="mt-1 list-disc pl-5"><template x-for="pesan in kelebihan" :key="pesan"><li x-text="pesan"></li></template></ul>
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
                    <p class="text-xs text-slate-500">Unggah foto untuk setiap baris kerusakan (JPG/PNG/WEBP, maks 5 MB, 8 foto per baris).</p>
                </div>
            </div>
            <div class="mt-4 space-y-4">
                <template x-for="(b, i) in baris" :key="b.uid">
                    <div class="rounded-lg border border-slate-200 p-4">
                        <p class="text-sm font-semibold text-slate-900" x-text="'Baris ' + (i + 1) + ' · ' + namaProduk(b.produk) + (b.jenis ? ' · ' + b.jenis : '')"></p>
                        <input type="file" multiple class="hidden" :name="'baris[' + i + '][foto][]'" x-effect="sinkron($el, b)">
                        <div class="mt-3 flex flex-wrap gap-3">
                            <template x-for="(f, j) in b.foto" :key="f.url">
                                <div class="relative w-24 h-24">
                                    <img :src="f.url" :alt="f.nama" class="w-24 h-24 object-cover rounded-lg border border-slate-200">
                                    <button type="button" @click="hapusFoto(b, j)" class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-red-600 text-xs shadow-sm" aria-label="Hapus foto"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                            </template>
                            <label x-show="b.foto.length < 8" class="w-24 h-24 rounded-lg border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-1 text-slate-400 hover:border-red-500 hover:text-red-600 cursor-pointer">
                                <i class="fa-solid fa-camera"></i>
                                <span class="text-[10px] font-bold">Tambah Foto</span>
                                <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="pilihFoto(b, $event)">
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
                <button type="submit" :disabled="!lengkap || mengirim" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-paper-plane"></i> <span x-text="mengirim ? 'Mengirim...' : 'Kirim Klaim'">Kirim Klaim</span>
                </button>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection
