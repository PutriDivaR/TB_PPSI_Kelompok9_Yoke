@extends('layouts.app')

@section('title', 'Proses Klaim & BAK')

@section('module_title', 'Modul Manajemen Klaim & Retur')

@section('content')

<div class="p-6 space-y-6" x-data="{ pilih: 0 }">

    @include('layouts.partials.flash')

    <div>
        <h1 class="text-xl font-bold text-slate-900">Pemrosesan & Penyelesaian Klaim</h1>
        <p class="text-sm text-slate-500">Berita Acara Kerusakan yang sudah disahkan Sales Area Manager</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Dokumen Belum Diproses</p><i class="fa-solid fa-inbox text-red-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['belum_diproses'], 0, ',', '.') }} <span class="text-xs text-slate-500">BAK</span></p>
            <p class="text-[10px] text-slate-500">Menunggu penyelesaian Logistik</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Klaim Selesai Bulan Ini</p><i class="fa-solid fa-circle-check text-emerald-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['selesai_bulan_ini'], 0, ',', '.') }} <span class="text-xs text-slate-500">Klaim</span></p>
            <p class="text-[10px] text-slate-500">Diselesaikan pada {{ $tanggalEpo(now(), 'F Y') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Karton Diklaim {{ $tahunFiskal['label'] }}</p><i class="fa-solid fa-boxes-stacked text-blue-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['karton_tahun_fiskal'], 0, ',', '.') }} <span class="text-xs text-slate-500">Karton</span></p>
            <p class="text-[10px] text-slate-500">Akumulasi tahun fiskal berjalan</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 items-start">

        {{-- Kotak masuk --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 pt-4 pb-3 border-b border-slate-100">
                <p class="text-sm font-bold text-slate-900">Kotak Masuk BAK <span class="ml-1 text-[10px] bg-red-600 text-white rounded-full px-1.5">{{ $kotakMasuk->count() }}</span></p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($kotakMasuk as $i => $dok)
                    <button type="button" @click="pilih = {{ $i }}" :aria-pressed="pilih === {{ $i }}"
                            class="w-full text-left px-5 py-3 border-l-4 transition"
                            :class="pilih === {{ $i }} ? 'bg-slate-100 border-red-500' : 'border-transparent hover:bg-slate-50'">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-[10px] font-bold text-red-600">{{ $dok['kode_bak'] }}</span>
                            <span class="text-[11px] text-slate-500">{{ $tanggalEpo($dok['tanggal_disahkan'], 'd M Y') }}</span>
                        </div>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dok['distributor'] }}</p>
                        <p class="text-[11px] text-slate-500">{{ $dok['nomor_kontainer'] }} &middot; {{ number_format($dok['total_karton'], 0, ',', '.') }} karton</p>
                    </button>
                @empty
                    <div class="px-5 py-10 text-center">
                        <i class="fa-solid fa-inbox text-3xl text-slate-300"></i>
                        <p class="mt-3 text-sm font-bold text-slate-700">Kotak masuk kosong</p>
                        <p class="mt-1 text-xs text-slate-500">Berita acara yang disahkan SAM akan tampil di sini.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Rincian dokumen terpilih --}}
        <div class="lg:col-span-2">
            @forelse ($kotakMasuk as $i => $dok)
                <div x-show="pilih === {{ $i }}" @if ($i > 0) style="display:none" @endif class="space-y-6">

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Identitas Klaim</p>
                        <dl class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                            <div><dt class="text-[11px] text-slate-500">Kode Klaim</dt><dd class="mt-0.5 font-mono text-xs font-bold text-red-600">{{ $dok['kode_klaim'] }}</dd></div>
                            <div class="md:col-span-2"><dt class="text-[11px] text-slate-500">Distributor</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $dok['distributor'] }}, {{ $dok['kota'] }}</dd></div>
                            <div><dt class="text-[11px] text-slate-500">Nomor PO</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $dok['nomor_po'] }}</dd></div>
                            <div><dt class="text-[11px] text-slate-500">Nomor Kontainer</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $dok['nomor_kontainer'] }}</dd></div>
                            <div><dt class="text-[11px] text-slate-500">Tanggal Tiba</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $tanggalEpo($dok['tanggal_tiba'], 'd M Y') }}</dd></div>
                        </dl>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                        <div class="px-5 pt-4 pb-3 border-b border-slate-100">
                            <p class="text-sm font-bold text-slate-900">Baris Kerusakan Terverifikasi</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm min-w-[560px]">
                                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                                    <tr>
                                        <th class="px-5 py-3 text-left">Produk</th>
                                        <th class="px-5 py-3 text-left">Jenis</th>
                                        <th class="px-5 py-3 text-right">Diajukan</th>
                                        <th class="px-5 py-3 text-right">Terverifikasi</th>
                                        <th class="px-5 py-3 text-left">Penanggung</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($dok['baris'] as $baris)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $baris['produk'] }}</td>
                                            <td class="px-5 py-3">{{ $baris['jenis'] }}</td>
                                            <td class="px-5 py-3 text-right text-slate-500">{{ number_format($baris['diajukan'], 0, ',', '.') }}</td>
                                            <td class="px-5 py-3 text-right font-semibold {{ $baris['terverifikasi'] !== $baris['diajukan'] ? 'text-red-600' : '' }}">{{ number_format($baris['terverifikasi'], 0, ',', '.') }}</td>
                                            <td class="px-5 py-3">{{ $baris['penanggung'] }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-slate-50">
                                        <td colspan="3" class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Terverifikasi</td>
                                        <td class="px-5 py-3 text-right font-bold text-slate-900">{{ number_format($dok['total_karton'], 0, ',', '.') }} karton</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center"><i class="fa-solid fa-file-signature"></i></span>
                            <div>
                                <p class="font-mono text-[10px] font-bold text-red-600">{{ $dok['kode_bak'] }}</p>
                                <p class="text-sm text-slate-700">Disahkan {{ $tanggalEpo($dok['tanggal_disahkan'], 'd M Y') }} oleh <span class="font-semibold text-slate-900">{{ $dok['pengesah'] }}</span></p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            {{-- Dokumen BAK belum dibuat; kedua tombol sementara href="#". --}}
                            <a href="#" class="text-[11px] font-semibold text-slate-600 border border-slate-200 px-3 py-1.5 rounded hover:bg-slate-50"><i class="fa-regular fa-eye mr-1"></i>Pratinjau</a>
                            <a href="#" class="text-[11px] font-bold text-white bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded"><i class="fa-solid fa-download mr-1"></i>Unduh</a>
                        </div>
                    </div>

                    {{-- Tindakan penyelesaian --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4" x-data="{ bentuk: '', catatan: '' }">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tindakan Penyelesaian</p>
                        <div class="grid sm:grid-cols-2 gap-3">
                            @foreach ($bentukPenyelesaian as $nilai => $label)
                                <label class="flex items-center gap-3 px-4 py-3 rounded-lg border-2 cursor-pointer text-sm font-semibold transition"
                                       :class="bentuk === @js($nilai) ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50'">
                                    <input type="radio" name="bentuk_{{ $i }}" value="{{ $nilai }}" x-model="bentuk" class="accent-red-600">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <label class="block">
                            <span class="text-sm font-semibold text-slate-700">Catatan</span>
                            <textarea x-model="catatan" rows="3" placeholder="Catatan penyelesaian atau alasan pengembalian ke SAM" class="mt-1 w-full px-3 py-2 text-sm border border-slate-200 rounded-lg"></textarea>
                        </label>
                        {{-- Penyimpanan belum dibuat; Selesai butuh bentuk penyelesaian, Kembalikan butuh catatan. --}}
                        <div class="flex flex-wrap justify-end gap-3">
                            <button type="button" :disabled="!catatan.trim()" class="px-5 py-2.5 rounded-lg border-2 border-red-600 bg-white text-sm font-bold text-red-600 hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fa-solid fa-rotate-left mr-1"></i>Kembalikan ke SAM
                            </button>
                            <button type="button" :disabled="!bentuk" class="px-5 py-2.5 rounded-lg bg-emerald-600 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fa-solid fa-circle-check mr-1"></i>Tandai Klaim Selesai
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-12 text-center">
                    <i class="fa-solid fa-file-circle-check text-3xl text-slate-300"></i>
                    <p class="mt-3 font-bold text-slate-700">Tidak ada dokumen untuk diproses</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
