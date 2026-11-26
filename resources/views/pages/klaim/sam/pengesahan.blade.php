@extends('layouts.app')

@section('title', 'Pengesahan BAK '.$klaim['kode'])

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6">
<div class="max-w-xl mx-auto space-y-5"
     x-data="{
        kosong: true,
        menggambar: false,
        mengirim: false,
        ctx: null,
        init() {
            const c = this.$refs.kanvas, r = window.devicePixelRatio || 1;
            c.width = c.offsetWidth * r;
            c.height = c.offsetHeight * r;
            this.ctx = c.getContext('2d');
            Object.assign(this.ctx, { lineWidth: 2.5 * r, lineCap: 'round', lineJoin: 'round', strokeStyle: '#0f172a' });
        },
        titik(e) {
            const c = this.$refs.kanvas, b = c.getBoundingClientRect();
            return [(e.clientX - b.left) * c.width / b.width, (e.clientY - b.top) * c.height / b.height];
        },
        mulai(e) { this.menggambar = true; e.target.setPointerCapture(e.pointerId); this.ctx.beginPath(); this.ctx.moveTo(...this.titik(e)) },
        gerak(e) { if (!this.menggambar) return; this.ctx.lineTo(...this.titik(e)); this.ctx.stroke(); this.kosong = false },
        berhenti() { this.menggambar = false },
        hapus() { const c = this.$refs.kanvas; this.ctx.clearRect(0, 0, c.width, c.height); this.kosong = true },
        kirim() { this.$refs.ttd.value = this.$refs.kanvas.toDataURL('image/png'); this.mengirim = true }
     }">

    @include('layouts.partials.flash')

    <a href="{{ route('klaim.sam.verifikasi', $klaim['id']) }}" class="inline-flex items-center gap-2 py-3 text-sm font-semibold text-slate-500 hover:text-red-600"><i class="fa-solid fa-arrow-left"></i>Kembali ke Verifikasi Lapangan</a>

    {{-- Pratinjau dokumen --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
        <div class="text-center border-b border-slate-200 pb-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">PT Yoke Food Industries Indonesia</p>
            <p class="mt-1 text-base font-extrabold uppercase tracking-wide text-slate-900">Berita Acara Kerusakan Barang</p>
            @if ($bak['kode'])
                <p class="mt-1 font-mono text-xs font-bold text-red-600">{{ $bak['kode'] }}</p>
            @else
                <p class="mt-1 text-xs text-slate-500">Nomor berita acara diberikan saat disahkan</p>
            @endif
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <div class="col-span-2"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Distributor</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $klaim['distributor'] }}{{ $klaim['kota'] ? ', '.$klaim['kota'] : '' }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Kontainer</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $klaim['nomor_kontainer'] }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Kode Klaim</dt><dd class="mt-0.5 font-mono text-xs font-bold text-red-600">{{ $klaim['kode'] }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Tiba</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $tanggalEpo($klaim['tanggal_tiba'], 'd M Y') }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Verifikasi</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $tanggalEpo($bak['tanggal_verifikasi'], 'd M Y') }}</dd></div>
        </dl>

        <p class="mt-4 text-sm text-slate-600 leading-relaxed">Telah dilakukan pemeriksaan fisik langsung di gudang distributor oleh Sales Area Manager dengan hasil sebagai berikut:</p>

        <table class="mt-3 w-full text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-3 py-2 text-left">Produk & Kerusakan</th>
                    <th class="px-3 py-2 text-right">Terverifikasi</th>
                    <th class="px-3 py-2 text-left">Penanggung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($bak['baris'] as $baris)
                    <tr>
                        <td class="px-3 py-2"><p class="font-semibold text-slate-900">{{ $baris['produk'] }}</p><p class="text-xs text-slate-500">{{ $baris['jenis'] }} &middot; diajukan {{ number_format($baris['karton'], 0, ',', '.') }}</p></td>
                        <td class="px-3 py-2 text-right font-semibold whitespace-nowrap {{ $baris['terverifikasi'] !== $baris['karton'] ? 'text-red-600' : '' }}">{{ number_format($baris['terverifikasi'], 0, ',', '.') }} karton</td>
                        <td class="px-3 py-2 text-slate-700">{{ $baris['penanggung'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 rounded-lg bg-slate-50 p-3 text-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pihak Penanggung Jawab</p>
            @foreach ($bak['per_penanggung'] as $penanggung => $karton)
                <p class="mt-1 flex justify-between"><span class="font-semibold text-slate-900">{{ $penanggung }}</span><span>{{ number_format($karton, 0, ',', '.') }} karton</span></p>
            @endforeach
        </div>
    </div>

    <form method="POST" action="{{ route('klaim.sam.sahkan', $klaim['id']) }}" class="space-y-5" @submit="kirim()">
        @csrf
        <input type="hidden" name="tanda_tangan" x-ref="ttd">

        {{-- Tanda tangan --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanda Tangan Digital</p>
                <button type="button" @click="hapus()" class="px-4 py-3 -my-3 text-sm font-semibold text-slate-500 hover:text-red-600"><i class="fa-solid fa-eraser mr-1"></i>Hapus Tanda Tangan</button>
            </div>
            <div class="relative">
                <canvas x-ref="kanvas" aria-label="Area tanda tangan"
                        @pointerdown="mulai($event)" @pointermove="gerak($event)" @pointerup="berhenti()" @pointercancel="berhenti()"
                        class="block w-full h-48 touch-none bg-white rounded-lg border-2 border-dashed border-slate-200 cursor-crosshair"></canvas>
                <p x-show="kosong" class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-slate-400">Tanda tangan di sini</p>
            </div>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Nama penanda tangan</span>
                <input type="text" value="{{ $pengesah['nama'] }}" readonly class="mt-1 w-full px-4 py-3 text-sm border border-slate-200 rounded-lg bg-slate-50">
            </label>
            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Jabatan</span>
                <input type="text" value="{{ $pengesah['jabatan'] }}" readonly class="mt-1 w-full px-4 py-3 text-sm border border-slate-200 rounded-lg bg-slate-50">
            </label>
        </div>

        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 flex items-start gap-3">
            <i class="fa-solid fa-lock mt-0.5"></i>
            <p>Berita acara yang sudah disahkan bersifat final dan tidak dapat diubah. Dokumen akan diteruskan ke Tim Logistik Pusat Jakarta secara otomatis.</p>
        </div>

        <button type="submit" :disabled="kosong || mengirim" class="w-full py-3 rounded-lg bg-red-600 text-sm font-bold text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fa-solid fa-file-signature mr-1"></i><span x-text="mengirim ? 'Mengesahkan...' : 'Sahkan Berita Acara'">Sahkan Berita Acara</span>
        </button>
    </form>
</div>
</div>
@endsection
