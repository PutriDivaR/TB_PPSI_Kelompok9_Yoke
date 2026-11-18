@extends('layouts.app')

@section('title', 'Verifikasi E-PO')

@section('content')

<div class="p-6 space-y-6" x-data="{ stok: false, jadwal: false, get lengkap() { return this.stok && this.jadwal } }">

    

    

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            @php $sesuai = $dok['total_kuota'] > 0 ? min(100, (int) round($dok['total_kontainer'] / $dok['total_kuota'] * 100)) : 0; @endphp
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pesanan vs Kuota</p>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $dok['total_kontainer'] <= $dok['total_kuota'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $sesuai }}% terpakai</span></div>
            <p class="mt-2 text-3xl font-extrabold">{{ number_format($dok['total_kontainer'], 0, ',', '.') }} <span class="text-xs text-slate-500">/ {{ number_format($dok['total_kuota'], 0, ',', '.') }} Kontainer (kuota)</span></p>
            <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-emerald-500" style="width: {{ $sesuai }}%"></div></div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Ekspektasi Pengiriman</p>
            <p class="mt-2 text-lg font-extrabold text-slate-900">{{ number_format($dok['total_kontainer'], 0, ',', '.') }} kontainer</p>
            <p class="text-[11px] text-slate-500">Setara {{ number_format($dok['total_karton'], 0, ',', '.') }} karton - tujuan {{ $po->forecast->distributor->wilayah->nama_wilayah }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nilai Dokumen</p>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">Rp {{ number_format($dok['total_nilai'], 0, ',', '.') }}</p>
            <p class="text-[11px] text-slate-500">{{ $po->nomor_po }}</p>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3">
        <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-file-lines text-red-600 mr-2"></i>Pratinjau Lembar Surat Pesanan Elektronik</h3>
        <span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $po->status === 'Disetujui' ? 'bg-emerald-50 text-emerald-700' : ($po->status === 'Diproses' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700') }}">
            {{ $po->status === 'Disetujui' ? 'Disetujui' : ($po->status === 'Diproses' ? 'Sedang diproses' : 'Menunggu verifikasi') }}
        </span>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-slate-800">
        <div class="flex justify-between border-b-2 border-slate-800 pb-4">
            <div><p class="font-extrabold text-lg">{{ $pabrik['nama'] }}</p><p class="text-xs text-slate-500">{{ $pabrik['alamat'] }}</p></div>
            <div class="text-right"><p class="text-xs font-bold text-red-600">PURCHASE ORDER</p><p class="font-extrabold">{{ $po->nomor_po }}</p><span class="text-[10px] font-bold">{{ $po->status }}</span></div>
        </div>
        <table class="w-full mt-6 text-xs border border-slate-200">
            <thead class="bg-slate-100"><tr><th class="px-3 py-2 text-left">No</th><th class="px-3 py-2 text-left">SKU & Produk</th><th class="px-3 py-2 text-right">Kontainer</th><th class="px-3 py-2 text-right">Kuota</th><th class="px-3 py-2 text-right">Subtotal</th></tr></thead>
            <tbody>@foreach($dok['rows'] as $r)<tr class="border-t"><td class="px-3 py-2">{{ $r['no'] }}</td><td class="px-3 py-2"><b>{{ $r['nama'] }}</b><br>{{ $r['kode'] }}</td><td class="px-3 py-2 text-right">{{ number_format($r['kontainer'],0,',','.') }}</td><td class="px-3 py-2 text-right">{{ number_format($r['kuota'],0,',','.') }}</td><td class="px-3 py-2 text-right">Rp {{ number_format($r['subtotal'],0,',','.') }}</td></tr>@endforeach</tbody>
            <tfoot class="font-bold bg-slate-50"><tr><td colspan="2" class="px-3 py-2">TOTAL DRAF</td><td class="px-3 py-2 text-right">{{ number_format($dok['total_kontainer'],0,',','.') }}</td><td class="px-3 py-2 text-right">{{ number_format($dok['total_kuota'],0,',','.') }}</td><td class="px-3 py-2 text-right">Rp {{ number_format($dok['total_nilai'],0,',','.') }}</td></tr></tfoot>
        </table>
    </div>

    @if ($po->status === 'Disetujui')
        <div class="rounded-xl border-2 border-emerald-500 bg-emerald-50 p-4 text-emerald-800 text-center font-black tracking-widest">
            <i class="fa-solid fa-stamp mr-2"></i> DISETUJUI LOGISTIK PUSAT · {{ $tanggalEpo($po->tanggal_disetujui, 'd F Y') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-bold text-slate-900">Pemeriksaan Otomatis Sistem</h3>
                <span class="text-[10px] font-bold px-2 py-1 rounded {{ $semuaLolos ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $semuaLolos ? 'Semua Lolos' : 'Ada Temuan' }}</span>
            </div>
            <ul class="rounded-lg bg-slate-50 p-3 text-xs space-y-2">
                @foreach ($pemeriksaan as $cek)
                    <li class="flex items-start gap-2">
                        <i class="mt-0.5 {{ $cek['lolos'] ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-xmark text-red-600' }}"></i>
                        <span class="{{ $cek['lolos'] ? 'text-slate-700' : 'text-red-700 font-semibold' }}">{{ $cek['label'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-sm font-bold text-slate-900">Konfirmasi Logistik</h3>
                <span class="text-[10px] font-bold px-2 py-1 rounded" :class="lengkap ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'" x-text="lengkap ? 'Terkonfirmasi' : 'Menunggu Anda'"></span>
            </div>
            <div class="rounded-lg bg-slate-50 p-3 text-xs space-y-2">
                <label class="flex items-center gap-2"><input type="checkbox" x-model="stok" class="rounded text-red-600 focus:ring-red-500"> Ketersediaan stok pabrik untuk seluruh SKU terkonfirmasi</label>
                <label class="flex items-center gap-2"><input type="checkbox" x-model="jadwal" class="rounded text-red-600 focus:ring-red-500"> Jadwal produksi dan slot muat kontainer terkonfirmasi</label>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-2">
            <a href="{{ route('e-po.logistik.antrean') }}" class="px-4 py-2.5 text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali</a>
            <button type="button" onclick="window.print()" class="px-4 py-2.5 text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50"><i class="fa-solid fa-print mr-1"></i> Cetak Draf Bukti PO</button>
        </div>
        @if ($bisaDisetujui)
            <form method="POST" action="{{ route('e-po.logistik.setujui-epo', $po) }}">
                @csrf
                <input type="hidden" name="konfirmasi" :value="lengkap ? 1 : ''">
                <button type="submit" :disabled="!lengkap || {{ $semuaLolos ? 'false' : 'true' }}"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:bg-slate-300 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-stamp mr-1"></i> Setujui E-PO & Terbitkan Dokumen
                </button>
            </form>
        @else
            <span class="text-xs text-slate-500"><i class="fa-solid fa-lock mr-1"></i>E-PO sudah diproses.</span>
        @endif
    </div>
</div>
@endsection
