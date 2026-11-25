@extends('layouts.app')

@section('title', 'Antrean Verifikasi Klaim')

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6" x-data="{ q: '' }">

    @include('layouts.partials.flash')

    <div>
        <h1 class="text-xl font-bold text-slate-900">Antrean Verifikasi Klaim</h1>
        <p class="text-sm text-slate-500">Wilayah kerja Sumatera Barat & Riau</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Klaim Menunggu Kunjungan</p><i class="fa-solid fa-hourglass-half text-red-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['menunggu_kunjungan'], 0, ',', '.') }} <span class="text-xs text-slate-500">Klaim</span></p>
            <p class="text-[10px] text-slate-500">Perlu pemeriksaan fisik di gudang</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Kunjungan Hari Ini</p><i class="fa-solid fa-route text-blue-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['kunjungan_hari_ini'], 0, ',', '.') }} <span class="text-xs text-slate-500">Kunjungan</span></p>
            <p class="text-[10px] text-slate-500">Terjadwal {{ $tanggalEpo(now(), 'd M Y') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Berita Acara Disahkan</p><i class="fa-solid fa-file-signature text-emerald-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['bak_bulan_ini'], 0, ',', '.') }} <span class="text-xs text-slate-500">BAK</span></p>
            <p class="text-[10px] text-slate-500">Bulan {{ $tanggalEpo(now(), 'F Y') }}</p>
        </div>
    </div>

    <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3 flex items-start gap-3">
        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
        <p>Pemeriksaan fisik wajib dilakukan langsung di gudang distributor. Berita Acara Kerusakan hanya dapat disahkan setelah barang diperiksa di tempat.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 pt-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100">
            <p class="pb-3 text-sm font-bold text-slate-900">Klaim Menunggu Verifikasi <span class="ml-1 text-[10px] bg-red-600 text-white rounded-full px-1.5">{{ $antrean->count() }}</span></p>
            <div class="relative pb-3">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input x-model="q" type="text" placeholder="Cari distributor / nomor kontainer..." class="pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-lg w-60">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[1080px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Kode Klaim</th>
                        <th class="px-5 py-3 text-left">Distributor & Kota</th>
                        <th class="px-5 py-3 text-left">Nomor Kontainer</th>
                        <th class="px-5 py-3 text-right">Jumlah</th>
                        <th class="px-5 py-3 text-left">Diajukan</th>
                        <th class="px-5 py-3 text-right">Lama Menunggu</th>
                        <th class="px-5 py-3 text-left">Jadwal Kunjungan</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($antrean as $klaim)
                        <tr class="hover:bg-slate-50" x-show="@js(strtolower($klaim['distributor'].' '.$klaim['nomor_kontainer'])).includes(q.toLowerCase())">
                            <td class="px-5 py-3">
                                <p class="font-mono text-[10px] font-bold text-red-600">{{ $klaim['kode'] }}</p>
                                @if ($klaim['catatan_logistik'])
                                    <p class="mt-1 inline-flex text-[10px] font-bold px-2 py-0.5 rounded bg-red-50 text-red-700" title="{{ $klaim['catatan_logistik'] }}">Dikembalikan Logistik</p>
                                @endif
                            </td>
                            <td class="px-5 py-3"><p class="font-semibold text-slate-900">{{ $klaim['distributor'] }}</p><p class="text-[11px] text-slate-500">{{ $klaim['kota'] }}</p></td>
                            <td class="px-5 py-3">{{ $klaim['nomor_kontainer'] }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ number_format($klaim['jumlah_karton'], 0, ',', '.') }} karton</td>
                            <td class="px-5 py-3 text-slate-500">{{ $tanggalEpo($klaim['tanggal_pengajuan'], 'd M Y') }}</td>
                            <td class="px-5 py-3 text-right {{ $klaim['lama_menunggu'] > $batasHariMenunggu ? 'font-bold text-red-600' : 'text-slate-700' }}">
                                {{ number_format($klaim['lama_menunggu'], 0, ',', '.') }} hari
                            </td>
                            <td class="px-5 py-3">
                                <form method="POST" action="{{ route('klaim.sam.jadwal', $klaim['id']) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="date" name="jadwal_kunjungan" required min="{{ now()->toDateString() }}" value="{{ $klaim['jadwal_kunjungan']?->toDateString() }}"
                                           aria-label="Jadwal kunjungan {{ $klaim['kode'] }}"
                                           class="px-2 py-1 text-xs border rounded-lg {{ $klaim['jadwal_kunjungan']?->isToday() ? 'border-blue-500 bg-blue-50 text-blue-700 font-bold' : 'border-slate-200' }}">
                                    <button class="text-[11px] font-semibold text-slate-600 border border-slate-200 px-2 py-1 rounded hover:bg-slate-50">Simpan</button>
                                </form>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('klaim.sam.verifikasi', $klaim['id']) }}" class="inline-block text-[11px] font-bold text-white bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded">Mulai Verifikasi</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <i class="fa-solid fa-clipboard-check text-3xl text-slate-300"></i>
                                <p class="mt-3 font-bold text-slate-700">Tidak ada klaim yang menunggu verifikasi</p>
                                <p class="mt-1 text-xs text-slate-500">Klaim baru dari distributor di wilayah kerja Anda akan tampil di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
