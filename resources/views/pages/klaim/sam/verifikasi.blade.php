@extends('layouts.app')

@section('title', 'Verifikasi Lapangan '.$klaim['kode'])

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6">
<div class="max-w-xl mx-auto space-y-5"
     x-data="{
        periksa: {{ \Illuminate\Support\Js::from(collect($klaim['baris'])->map(fn (array $b) => ['hasil' => '', 'karton' => $b['karton'], 'catatan' => ''])) }},
        foto: [],
        kembalikan: false,
        catatanPerbaikan: '',
        pilihFoto(e) { [...e.target.files].forEach(f => this.foto.push({ nama: f.name, url: URL.createObjectURL(f) })); e.target.value = '' },
        hapusFoto(j) { URL.revokeObjectURL(this.foto[j].url); this.foto.splice(j, 1) },
        get semuaDiperiksa() { return this.periksa.every(p => p.hasil !== '') }
     }">

    @include('layouts.partials.flash')

    <a href="{{ route('klaim.sam.antrean') }}" class="inline-flex items-center gap-2 py-3 text-sm font-semibold text-slate-500 hover:text-red-600"><i class="fa-solid fa-arrow-left"></i>Kembali ke Antrean</a>

    {{-- Identitas klaim --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="font-mono text-xs font-bold text-red-600">{{ $klaim['kode'] }}</p>
        <p class="mt-1 text-lg font-bold text-slate-900">{{ $klaim['distributor'] }}</p>
        <p class="text-sm text-slate-500">{{ $klaim['kota'] }}</p>
        <dl class="mt-4 grid grid-cols-2 gap-4">
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Kontainer</dt><dd class="mt-1 text-sm font-semibold text-slate-900">{{ $klaim['nomor_kontainer'] }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Tiba</dt><dd class="mt-1 text-sm font-semibold text-slate-900">{{ $tanggalEpo($klaim['tanggal_tiba'], 'd M Y') }}</dd></div>
        </dl>
    </div>

    {{-- Galeri foto distributor --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Foto Bukti dari Distributor</p>
        <div class="mt-3 flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2">
            {{-- shortcut: data contoh belum punya berkas foto, jadi tampil placeholder; ganti dengan <img> dari FotoBukti saat tersambung. --}}
            @foreach ($klaim['baris'] as $i => $baris)
                @foreach ($baris['foto'] as $foto)
                    <figure class="w-full shrink-0 snap-center">
                        <div class="aspect-[4/3] rounded-lg border border-slate-200 bg-slate-50 flex flex-col items-center justify-center gap-2">
                            <i class="fa-solid fa-image text-4xl text-slate-300"></i>
                            <span class="text-sm text-slate-500 break-all px-4 text-center">{{ $foto }}</span>
                        </div>
                        <figcaption class="mt-2 text-sm text-slate-600">Baris {{ $i + 1 }} &middot; {{ $baris['produk'] }} &middot; {{ $baris['jenis'] }}</figcaption>
                    </figure>
                @endforeach
            @endforeach
        </div>
        <p class="text-xs text-slate-400"><i class="fa-solid fa-hand-pointer mr-1"></i>Geser untuk melihat foto lainnya</p>
    </div>

    {{-- Pemeriksaan per baris --}}
    @foreach ($klaim['baris'] as $i => $baris)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Baris {{ $i + 1 }}</p>
                <p class="mt-1 text-base font-bold text-slate-900">{{ $baris['produk'] }}</p>
                <p class="text-sm text-slate-600">{{ $baris['jenis'] }} &middot; diajukan <span class="font-semibold text-slate-900">{{ number_format($baris['karton'], 0, ',', '.') }} karton</span></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="periksa[{{ $i }}].hasil = 'sesuai'; periksa[{{ $i }}].karton = {{ $baris['karton'] }}"
                        :aria-pressed="periksa[{{ $i }}].hasil === 'sesuai'"
                        class="py-3 rounded-lg border-2 text-sm font-bold transition"
                        :class="periksa[{{ $i }}].hasil === 'sesuai' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'border-slate-200 text-slate-600'">
                    <i class="fa-solid fa-check mr-1"></i>Sesuai
                </button>
                <button type="button" @click="periksa[{{ $i }}].hasil = 'tidak_sesuai'"
                        :aria-pressed="periksa[{{ $i }}].hasil === 'tidak_sesuai'"
                        class="py-3 rounded-lg border-2 text-sm font-bold transition"
                        :class="periksa[{{ $i }}].hasil === 'tidak_sesuai' ? 'bg-red-50 border-red-500 text-red-700' : 'border-slate-200 text-slate-600'">
                    <i class="fa-solid fa-xmark mr-1"></i>Tidak Sesuai
                </button>
            </div>

            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Jumlah karton terverifikasi</span>
                <input type="number" inputmode="numeric" min="0" x-model.number="periksa[{{ $i }}].karton" class="mt-1 w-full px-4 py-3 text-sm border border-slate-200 rounded-lg">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Catatan singkat</span>
                <input type="text" x-model="periksa[{{ $i }}].catatan" placeholder="Contoh: 5 karton penyok ringan, masih layak jual" class="mt-1 w-full px-4 py-3 text-sm border border-slate-200 rounded-lg">
            </label>
        </div>
    @endforeach

    {{-- Foto dokumentasi lokasi --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Foto Dokumentasi di Lokasi</p>
        {{-- Sementara: belum ada jumlah foto minimal, menunggu wawancara lanjutan. --}}
        <label class="mt-3 flex flex-col items-center justify-center gap-2 py-6 rounded-lg border-2 border-dashed border-slate-200 text-slate-500 hover:border-red-500 hover:text-red-600 cursor-pointer">
            <i class="fa-solid fa-camera text-2xl"></i>
            <span class="text-sm font-bold">Ambil Foto</span>
            <input type="file" accept="image/*" capture="environment" class="hidden" @change="pilihFoto($event)">
        </label>
        <div x-show="foto.length" class="mt-3 grid grid-cols-3 gap-3">
            <template x-for="(f, j) in foto" :key="f.url">
                <div class="relative">
                    <img :src="f.url" :alt="f.nama" class="w-full aspect-square object-cover rounded-lg border border-slate-200">
                    <button type="button" @click="hapusFoto(j)" class="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-red-600 shadow-sm" aria-label="Hapus foto"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </template>
        </div>
    </div>

    {{-- Aksi --}}
    <div class="space-y-3">
        <div class="grid sm:grid-cols-2 gap-3">
            <button type="button" @click="kembalikan = !kembalikan" class="py-3 rounded-lg border-2 border-red-600 bg-white text-sm font-bold text-red-600 hover:bg-red-50">
                <i class="fa-solid fa-rotate-left mr-1"></i>Kembalikan untuk Diperbaiki
            </button>
            {{-- Hasil pemeriksaan belum dikirim ke server; tombol aktif setelah semua baris diperiksa. --}}
            <button type="button" :disabled="!semuaDiperiksa" @click="window.location.href = '{{ route('klaim.sam.pengesahan', $klaim['id']) }}'" class="py-3 rounded-lg bg-red-600 text-sm font-bold text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                Lanjut ke Pengesahan BAK<i class="fa-solid fa-arrow-right ml-1"></i>
            </button>
        </div>
        <p x-show="!semuaDiperiksa" class="text-xs text-slate-500 text-center">Tandai Sesuai atau Tidak Sesuai pada setiap baris untuk melanjutkan.</p>
        <div x-show="kembalikan" style="display:none" class="bg-white rounded-xl border border-red-200 shadow-sm p-5">
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Catatan perbaikan untuk distributor</span>
                <textarea x-model="catatanPerbaikan" rows="4" placeholder="Jelaskan apa yang perlu diperbaiki pada pengajuan klaim ini" class="mt-1 w-full px-4 py-3 text-sm border border-slate-200 rounded-lg"></textarea>
            </label>
        </div>
    </div>
</div>
</div>
@endsection
