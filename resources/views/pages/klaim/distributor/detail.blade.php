@extends('layouts.app')

@section('title', 'Detail Klaim '.$klaim['kode'])

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6">

    @include('layouts.partials.flash')

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('klaim.distributor.beranda') }}" class="text-xs font-semibold text-slate-500 hover:text-red-600"><i class="fa-solid fa-arrow-left mr-1"></i>Kembali ke Beranda Klaim</a>
            <h1 class="mt-2 text-xl font-bold text-slate-900">Detail Klaim <span class="font-mono text-red-600">{{ $klaim['kode'] }}</span></h1>
            <p class="text-sm text-slate-500">Kontainer {{ $klaim['nomor_kontainer'] }} &middot; diajukan {{ $tanggalEpo($klaim['tanggal_pengajuan'], 'd M Y') }}</p>
        </div>
        <span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $klaim['lencana'][1] }}">{{ $klaim['lencana'][0] }}</span>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 items-start">

        {{-- Garis waktu --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pelacakan Status</p>
            <ol class="mt-5">
                @foreach ($tahap as $t)
                    <li class="relative flex gap-3 {{ $loop->last ? '' : 'pb-6' }}">
                        @unless ($loop->last)
                            <span class="absolute left-3.5 top-7 bottom-0 w-0.5 -translate-x-1/2 {{ $t['status'] === 'lewat' ? 'bg-emerald-500' : 'bg-slate-200' }}"></span>
                        @endunless
                        @if ($t['status'] === 'lewat')
                            <span class="relative w-7 h-7 shrink-0 rounded-full bg-emerald-500 text-white text-xs flex items-center justify-center"><i class="fa-solid fa-check"></i></span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $t['nama'] }}</p>
                                <p class="text-[11px] text-emerald-700">{{ $tanggalEpo($t['tanggal'], 'd M Y') }}</p>
                            </div>
                        @elseif ($t['status'] === 'berjalan')
                            <span class="relative w-7 h-7 shrink-0 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center ring-4 ring-red-50">{{ $loop->iteration }}</span>
                            <div class="flex-1 rounded-lg bg-red-50 px-3 py-2 -mt-1">
                                <p class="text-sm font-bold text-red-600">{{ $t['nama'] }}</p>
                                <p class="text-[11px] text-red-700">Sedang berjalan</p>
                            </div>
                        @else
                            <span class="relative w-7 h-7 shrink-0 rounded-full bg-slate-100 border border-slate-200 text-slate-400 text-xs font-bold flex items-center justify-center">{{ $loop->iteration }}</span>
                            <div>
                                <p class="text-sm text-slate-500">{{ $t['nama'] }}</p>
                                <p class="text-[11px] text-slate-400">Belum dimulai</p>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="lg:col-span-2 space-y-6">

            {{-- Informasi klaim --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Informasi Klaim</p>
                <dl class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><dt class="text-[11px] text-slate-500">Kode Klaim</dt><dd class="mt-0.5 font-mono text-xs font-bold text-red-600">{{ $klaim['kode'] }}</dd></div>
                    <div><dt class="text-[11px] text-slate-500">Nomor PO</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $klaim['nomor_po'] }}</dd></div>
                    <div><dt class="text-[11px] text-slate-500">Nomor Kontainer</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $klaim['nomor_kontainer'] }}</dd></div>
                    <div><dt class="text-[11px] text-slate-500">Tanggal Tiba</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $tanggalEpo($klaim['tanggal_tiba'], 'd M Y') }}</dd></div>
                    <div><dt class="text-[11px] text-slate-500">Tanggal Pengajuan</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $tanggalEpo($klaim['tanggal_pengajuan'], 'd M Y') }}</dd></div>
                    <div><dt class="text-[11px] text-slate-500">Total Diklaim</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ number_format($klaim['jumlah_karton'], 0, ',', '.') }} karton</dd></div>
                    <div class="col-span-2"><dt class="text-[11px] text-slate-500">Jenis Klaim</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $klaim['jenis'] }}</dd></div>
                    <div><dt class="text-[11px] text-slate-500">Penanggung Jawab</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $klaim['penanggung'] }}</dd></div>
                </dl>
            </div>

            {{-- Baris kerusakan --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="px-5 pt-4 pb-3 border-b border-slate-100">
                    <p class="text-sm font-bold text-slate-900">Rincian Kerusakan & Foto Bukti</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($klaim['baris'] as $baris)
                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-mono text-[10px] font-bold text-slate-500">{{ $baris['kode'] }}</p>
                                    <p class="font-semibold text-slate-900">{{ $baris['nama'] }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex text-[10px] font-bold px-2 py-1 rounded bg-slate-100 text-slate-700">{{ $baris['jenis'] }}</span>
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($baris['karton'], 0, ',', '.') }} karton</span>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-3">
                                {{-- shortcut: data contoh belum punya berkas foto, jadi tampil placeholder; ganti dengan <img> dari FotoBukti saat tersambung. --}}
                                @foreach ($baris['foto'] as $foto)
                                    <div class="w-24 h-24 rounded-lg border border-slate-200 bg-slate-50 flex flex-col items-center justify-center gap-1 px-1 text-center" title="{{ $foto }}">
                                        <i class="fa-solid fa-image text-xl text-slate-300"></i>
                                        <span class="text-[9px] text-slate-500 break-all leading-tight">{{ $foto }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Berita Acara Kerusakan --}}
            @if ($klaim['bak'])
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center"><i class="fa-solid fa-file-signature"></i></span>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Berita Acara Kerusakan</p>
                            <p class="mt-1 font-mono text-[10px] font-bold text-red-600">{{ $klaim['bak']['kode'] }}</p>
                            <p class="text-sm text-slate-700">Disahkan {{ $tanggalEpo($klaim['bak']['tanggal'], 'd M Y') }} oleh <span class="font-semibold text-slate-900">{{ $klaim['bak']['pengesah'] }}</span></p>
                            <p class="text-[11px] text-slate-500">{{ $klaim['bak']['jabatan'] }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        {{-- Dokumen BAK belum dibuat; kedua tombol sementara href="#". --}}
                        <a href="#" class="text-[11px] font-semibold text-slate-600 border border-slate-200 px-3 py-1.5 rounded hover:bg-slate-50"><i class="fa-regular fa-eye mr-1"></i>Pratinjau</a>
                        <a href="#" class="text-[11px] font-bold text-white bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded"><i class="fa-solid fa-download mr-1"></i>Unduh</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
