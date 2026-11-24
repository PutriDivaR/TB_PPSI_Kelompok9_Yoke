@extends('layouts.app')

@section('title', 'Verifikasi Lapangan '.$klaim['kode'])

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6">
<div class="max-w-xl mx-auto space-y-5"
     x-data="{
        periksa: {{ \Illuminate\Support\Js::from(collect($klaim['baris'])->map(fn (array $b) => ['hasil' => $b['hasil'], 'karton' => $b['jumlah_terverifikasi'], 'catatan' => $b['catatan'], 'foto' => []])) }},
        kembalikan: false,
        mengirim: false,
        pilihFoto(p, e) { [...e.target.files].forEach(f => p.foto.push({ file: f, nama: f.name, url: URL.createObjectURL(f) })); e.target.value = '' },
        hapusFoto(p, j) { URL.revokeObjectURL(p.foto[j].url); p.foto.splice(j, 1) },
        sinkron(el, p) { const dt = new DataTransfer(); p.foto.forEach(f => dt.items.add(f.file)); el.files = dt.files },
        get semuaDiperiksa() { return this.periksa.every(p => p.hasil !== '') }
     }">

    @include('layouts.partials.flash')

    <a href="{{ route('klaim.sam.antrean') }}" class="inline-flex items-center gap-2 py-3 text-sm font-semibold text-slate-500 hover:text-red-600"><i class="fa-solid fa-arrow-left"></i>Kembali ke Antrean</a>

    @if ($klaim['catatan_logistik'])
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 flex items-start gap-3">
            <i class="fa-solid fa-rotate-left mt-0.5"></i>
            <p><span class="font-bold">Dikembalikan Logistik Pusat:</span> {{ $klaim['catatan_logistik'] }}</p>
        </div>
    @endif

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
        @if (collect($klaim['baris'])->pluck('foto_distributor')->flatten()->isEmpty())
            <p class="mt-3 text-sm text-slate-500">Distributor tidak melampirkan foto bukti.</p>
        @else
            <div class="mt-3 flex gap-3 overflow-x-auto snap-x snap-mandatory pb-2">
                @foreach ($klaim['baris'] as $i => $baris)
                    @foreach ($baris['foto_distributor'] as $url)
                        <figure class="w-full shrink-0 snap-center">
                            <img src="{{ $url }}" alt="Foto bukti {{ $baris['produk'] }}" class="w-full aspect-[4/3] object-cover rounded-lg border border-slate-200">
                            <figcaption class="mt-2 text-sm text-slate-600">Baris {{ $i + 1 }} &middot; {{ $baris['produk'] }} &middot; {{ $baris['jenis'] }}</figcaption>
                        </figure>
                    @endforeach
                @endforeach
            </div>
            <p class="text-xs text-slate-400"><i class="fa-solid fa-hand-pointer mr-1"></i>Geser untuk melihat foto lainnya</p>
        @endif
    </div>

    <form method="POST" action="{{ route('klaim.sam.simpan-verifikasi', $klaim['id']) }}" enctype="multipart/form-data" class="space-y-5" @submit="mengirim = true">
        @csrf

        {{-- Pemeriksaan per baris --}}
        @foreach ($klaim['baris'] as $i => $baris)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Baris {{ $i + 1 }}</p>
                    <p class="mt-1 text-base font-bold text-slate-900">{{ $baris['produk'] }}</p>
                    <p class="text-sm text-slate-600">{{ $baris['jenis'] }} &middot; diajukan <span class="font-semibold text-slate-900">{{ number_format($baris['karton'], 0, ',', '.') }} karton</span></p>
                </div>

                <input type="hidden" name="hasil[{{ $baris['id'] }}][status_verifikasi]" :value="periksa[{{ $i }}].hasil === 'sesuai' ? 'Disetujui' : (periksa[{{ $i }}].hasil === 'tidak_sesuai' ? 'Ditolak' : '')">
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
                    <input type="number" inputmode="numeric" min="0" max="{{ $baris['karton'] }}" name="hasil[{{ $baris['id'] }}][jumlah_terverifikasi]"
                           x-model.number="periksa[{{ $i }}].karton" :readonly="periksa[{{ $i }}].hasil === 'sesuai'"
                           class="mt-1 w-full px-4 py-3 text-sm border border-slate-200 rounded-lg read-only:bg-slate-50">
                </label>
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Catatan singkat</span>
                    <input type="text" maxlength="255" name="hasil[{{ $baris['id'] }}][catatan]" x-model="periksa[{{ $i }}].catatan" placeholder="Contoh: 5 karton penyok ringan, masih layak jual" class="mt-1 w-full px-4 py-3 text-sm border border-slate-200 rounded-lg">
                </label>

                {{-- Foto dokumentasi lokasi per baris, karena foto_bukti menggantung pada baris kerusakan. Sementara: belum ada jumlah foto minimal. --}}
                <div>
                    <span class="text-sm font-semibold text-slate-700">Foto dokumentasi di lokasi</span>
                    @if ($baris['jumlah_foto_sam'] > 0)
                        <p class="text-xs text-slate-500">{{ $baris['jumlah_foto_sam'] }} foto dari pemeriksaan sebelumnya sudah tersimpan.</p>
                    @endif
                    <input type="file" multiple class="hidden" name="foto_lokasi[{{ $baris['id'] }}][]" x-effect="sinkron($el, periksa[{{ $i }}])">
                    <div class="mt-2 grid grid-cols-3 gap-3">
                        <template x-for="(f, j) in periksa[{{ $i }}].foto" :key="f.url">
                            <div class="relative">
                                <img :src="f.url" :alt="f.nama" class="w-full aspect-square object-cover rounded-lg border border-slate-200">
                                <button type="button" @click="hapusFoto(periksa[{{ $i }}], j)" class="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-red-600 shadow-sm" aria-label="Hapus foto"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </template>
                        <label x-show="periksa[{{ $i }}].foto.length < 8" class="aspect-square flex flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-slate-200 text-slate-500 hover:border-red-500 hover:text-red-600 cursor-pointer">
                            <i class="fa-solid fa-camera text-xl"></i>
                            <span class="text-sm font-bold">Ambil Foto</span>
                            <input type="file" accept="image/*" capture="environment" class="hidden" @change="pilihFoto(periksa[{{ $i }}], $event)">
                        </label>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Aksi --}}
        <div class="space-y-3">
            <div class="grid sm:grid-cols-2 gap-3">
                <button type="button" @click="kembalikan = !kembalikan" class="py-3 rounded-lg border-2 border-red-600 bg-white text-sm font-bold text-red-600 hover:bg-red-50">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Kembalikan untuk Diperbaiki
                </button>
                <button type="submit" :disabled="!semuaDiperiksa || mengirim" class="py-3 rounded-lg bg-red-600 text-sm font-bold text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    Lanjut ke Pengesahan BAK<i class="fa-solid fa-arrow-right ml-1"></i>
                </button>
            </div>
            <p x-show="!semuaDiperiksa" class="text-xs text-slate-500 text-center">Tandai Sesuai atau Tidak Sesuai pada setiap baris untuk melanjutkan.</p>
        </div>
    </form>

    <form x-show="kembalikan" style="display:none" method="POST" action="{{ route('klaim.sam.kembalikan', $klaim['id']) }}" class="bg-white rounded-xl border border-red-200 shadow-sm p-5 space-y-3">
        @csrf
        <label class="block">
            <span class="text-sm font-semibold text-slate-700">Catatan perbaikan untuk distributor</span>
            <textarea name="catatan" required maxlength="1000" rows="4" placeholder="Jelaskan apa yang perlu diperbaiki pada pengajuan klaim ini" class="mt-1 w-full px-4 py-3 text-sm border border-slate-200 rounded-lg"></textarea>
        </label>
        <button type="submit" class="w-full py-3 rounded-lg border-2 border-red-600 bg-white text-sm font-bold text-red-600 hover:bg-red-50">Kirim ke Distributor</button>
    </form>
</div>
</div>
@endsection
