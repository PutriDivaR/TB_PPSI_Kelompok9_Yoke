@extends('layouts.app')

@section('title', 'Klaim & Retur Barang')

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6" x-data="{ q: '' }">

    @include('layouts.partials.flash')

    @if (! $distributor)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
        <i class="fa-solid fa-store-slash text-3xl text-slate-300"></i>
        <h2 class="mt-3 text-lg font-bold text-slate-900">Belum ada distributor aktif</h2>
        <p class="mt-1 text-sm text-slate-500">Tambahkan data distributor dengan status aktif terlebih dahulu untuk memakai modul klaim.</p>
    </div>
    @else

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Beranda Klaim & Retur Barang</h1>
            <p class="text-sm text-slate-500">{{ $distributor->nama }} &middot; {{ $distributor->wilayah?->nama_wilayah }}</p>
        </div>
        <a href="{{ route('klaim.distributor.ajukan') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700">
            <i class="fa-solid fa-plus"></i> Ajukan Klaim Baru
        </a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Klaim Menunggu Verifikasi</p><i class="fa-solid fa-hourglass-half text-amber-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['menunggu_verifikasi'], 0, ',', '.') }} <span class="text-xs text-slate-500">Klaim</span></p>
            <p class="text-[10px] text-slate-500">Menunggu pemeriksaan fisik oleh SAM</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Sisa Waktu Penyortiran</p><i class="fa-solid fa-stopwatch text-red-500"></i></div>
            @if ($stats['sisa_hari_sortir'] === null)
                <p class="mt-2 text-3xl font-extrabold text-slate-900">-</p>
                <p class="text-[10px] text-slate-500">Tidak ada kedatangan yang belum diklaim</p>
            @else
                @if ($stats['sisa_hari_sortir'] >= 0)
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['sisa_hari_sortir'], 0, ',', '.') }} <span class="text-xs text-slate-500">Hari</span></p>
                @else
                    <p class="mt-2 text-3xl font-extrabold text-red-600">Lewat <span class="text-xs text-slate-500">{{ number_format(abs($stats['sisa_hari_sortir']), 0, ',', '.') }} hari</span></p>
                @endif
                <p class="text-[10px] text-slate-500">{{ $kedatanganTerakhir->pengiriman->nomor_kontainer }} &middot; batas {{ $tanggalEpo($batasPenyortiran, 'd M Y') }}</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Klaim Selesai Bulan Ini</p><i class="fa-solid fa-circle-check text-emerald-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['selesai_bulan_ini'], 0, ',', '.') }} <span class="text-xs text-slate-500">Klaim</span></p>
            <p class="text-[10px] text-slate-500">Diselesaikan pada {{ $tanggalEpo(now(), 'F Y') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Karton Diklaim {{ $tahunFiskal ? 'FY-'.$tahunFiskal->tanggal_mulai->year : '' }}</p><i class="fa-solid fa-boxes-stacked text-blue-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['karton_tahun_fiskal'], 0, ',', '.') }} <span class="text-xs text-slate-500">Karton</span></p>
            <p class="text-[10px] text-slate-500">Akumulasi pengajuan tahun fiskal berjalan</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pembagian Tanggung Jawab Klaim</p>
        <div class="mt-4 grid md:grid-cols-2 gap-4">
            <div class="rounded-lg border border-slate-200 p-4">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center"><i class="fa-solid fa-building"></i></span>
                    <p class="font-bold text-slate-900">Ditanggung Perusahaan</p>
                </div>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li class="flex gap-3"><i class="fa-solid fa-truck-fast w-4 mt-0.5 text-slate-400"></i><span>Kerusakan selama Barang Dalam Perjalanan</span></li>
                    <li class="flex gap-3"><i class="fa-solid fa-file-invoice w-4 mt-0.5 text-slate-400"></i><span>Barang kurang terhadap invoice</span></li>
                </ul>
            </div>
            <div class="rounded-lg border border-slate-200 p-4">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center"><i class="fa-solid fa-warehouse"></i></span>
                    <p class="font-bold text-slate-900">Ditanggung Distributor</p>
                </div>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li class="flex gap-3"><i class="fa-solid fa-box-open w-4 mt-0.5 text-slate-400"></i><span>Kerusakan akibat penyimpanan gudang</span></li>
                    <li class="flex gap-3"><i class="fa-solid fa-calendar-xmark w-4 mt-0.5 text-slate-400"></i><span>Retur kedaluwarsa dari toko</span></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 pt-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100">
            <p class="pb-3 text-sm font-bold text-slate-900">Riwayat Pengajuan Klaim</p>
            <div class="relative pb-3">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input x-model="q" type="text" placeholder="Cari kode klaim / nomor kontainer..." class="pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-lg w-60">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[850px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Kode Klaim & Kontainer</th>
                        <th class="px-5 py-3 text-left">Tanggal Pengajuan</th>
                        <th class="px-5 py-3 text-right">Jumlah</th>
                        <th class="px-5 py-3 text-left">Penanggung Jawab</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($riwayat as $klaim)
                        <tr class="hover:bg-slate-50" x-show="@js(strtolower($klaim['kode'].' '.$klaim['nomor_kontainer'].' '.$klaim['jenis'].' '.$klaim['status'])).includes(q.toLowerCase())">
                            <td class="px-5 py-3"><p class="font-mono text-[10px] font-bold text-red-600">{{ $klaim['kode'] }}</p><p class="mt-1 font-semibold text-slate-900">{{ $klaim['nomor_kontainer'] }}</p></td>
                            <td class="px-5 py-3"><p>{{ $tanggalEpo($klaim['tanggal_pengajuan'], 'd M Y') }}</p><p class="text-[11px] text-slate-500">Tiba {{ $tanggalEpo($klaim['tanggal_tiba'], 'd M Y') }}</p></td>
                            <td class="px-5 py-3 text-right font-semibold">{{ number_format($klaim['jumlah_karton'], 0, ',', '.') }} karton</td>
                            <td class="px-5 py-3"><p class="font-semibold text-slate-900">{{ $klaim['penanggung'] }}</p><p class="text-[11px] text-slate-500">{{ $klaim['jenis'] }}</p></td>
                            <td class="px-5 py-3"><span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $klaim['lencana'][1] }}">{{ $klaim['lencana'][0] }}</span></td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('klaim.distributor.detail', $klaim['id']) }}" class="text-[11px] font-semibold text-slate-600 border border-slate-200 px-3 py-1.5 rounded hover:bg-slate-50"><i class="fa-regular fa-eye mr-1"></i>{{ $klaim['status'] === \App\Services\KlaimService::STATUS_DIKEMBALIKAN ? 'Perbaiki' : 'Lihat Detail' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <i class="fa-solid fa-box-open text-3xl text-slate-300"></i>
                                <p class="mt-3 font-bold text-slate-700">Belum ada pengajuan klaim</p>
                                <p class="mt-1 text-xs text-slate-500">Klaim yang Anda ajukan setelah penyortiran barang akan tampil di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
