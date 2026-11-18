@extends('layouts.app')

@section('title', 'Antrean Forecast & E-PO')

@section('content')

<div class="p-6 space-y-6" x-data="{ tab: 'forecast', q: '' }">

    

    

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Antrean Usulan Forecast</p><i class="fa-solid fa-hourglass-half text-red-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['forecast_pending'] }} <span class="text-xs text-slate-500">Pengajuan</span></p>
            <p class="text-[10px] text-slate-500">Perlu penetapan kuota</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Volume Usulan Antrean</p><i class="fa-solid fa-cubes text-blue-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['volume_antrean'], 0, ',', '.') }} <span class="text-xs text-slate-500">Kontainer</span></p>
            <p class="text-[10px] text-slate-500">Akumulasi usulan yang menunggu</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">E-PO Menunggu Verifikasi</p><i class="fa-solid fa-file-circle-question text-amber-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['po_verifikasi'] }} <span class="text-xs text-slate-500">PO</span></p>
            <p class="text-[10px] text-slate-500">Siap diperiksa Logistik</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Utilisasi Kapasitas {{ $tanggalEpo($periodeIni, 'M Y') }}</p><i class="fa-solid fa-industry text-emerald-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['utilisasi'] }}%</p>
            <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-emerald-500" style="width: {{ min(100, $stats['utilisasi']) }}%"></div></div>
            <p class="text-[10px] text-slate-500 mt-1">{{ $stats['terpakai'] }} dari {{ $stats['kapasitas'] }} kontainer</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 pt-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-100">
            <div class="flex gap-6 text-sm">
                <button type="button" @click="tab='forecast'" class="pb-3 font-bold border-b-2 transition" :class="tab==='forecast' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500'">
                    Pengajuan Forecasting <span class="ml-1 text-[10px] bg-red-600 text-white rounded-full px-1.5">{{ $stats['forecast_pending'] }}</span>
                </button>
                <button type="button" @click="tab='epo'" class="pb-3 font-bold border-b-2 transition" :class="tab==='epo' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500'">
                    Daftar Transaksi E-PO <span class="ml-1 text-[10px] bg-slate-200 text-slate-700 rounded-full px-1.5">{{ $pos->count() }}</span>
                </button>
            </div>
            <div class="relative pb-3">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input x-model="q" type="text" placeholder="Cari distributor / nomor PO..." class="pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-lg w-60">
            </div>
        </div>

        <div x-show="tab==='forecast'" class="overflow-x-auto">
            <table class="w-full text-sm min-w-[850px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Distributor & Wilayah</th>
                        <th class="px-5 py-3 text-left">Periode</th>
                        <th class="px-5 py-3 text-right">Usulan</th>
                        <th class="px-5 py-3 text-right">Kuota</th>
                        <th class="px-5 py-3 text-right">Nilai Usulan</th>
                        <th class="px-5 py-3 text-left">Diajukan</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($forecasts as $f)
                        <tr class="hover:bg-slate-50" x-show="@js(strtolower($f->distributor->nama.' '.$f->distributor->wilayah->nama_wilayah)).includes(q.toLowerCase())">
                            <td class="px-5 py-3"><p class="font-mono text-[10px] font-bold text-red-600">FC-{{ str_pad((string) $f->id_forecast, 5, '0', STR_PAD_LEFT) }}</p><p class="mt-1 font-semibold text-slate-900">{{ $f->distributor->nama }}</p><p class="text-[11px] text-slate-500">{{ $f->distributor->wilayah->nama_wilayah }}</p></td>
                            <td class="px-5 py-3">{{ $tanggalEpo($f->periode, 'M Y') }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ number_format($f->totals['usulan'], 0, ',', '.') }} ktr</td>
                            <td class="px-5 py-3 text-right">{{ $f->status === 'Disetujui' ? number_format($f->totals['disetujui'], 0, ',', '.').' ktr' : '-' }}</td>
                            <td class="px-5 py-3 text-right">Rp {{ number_format($f->totals['nilai_usulan'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $tanggalEpo($f->tanggal_pengajuan, 'd M Y') }}</td>
                            @php
                                $status = match ($f->status) {
                                    'Diajukan' => ['Menunggu penetapan', 'bg-amber-50 text-amber-700'],
                                    'Diproses' => ['Sedang diproses', 'bg-blue-50 text-blue-700'],
                                    'Disetujui' => ['Disetujui', 'bg-emerald-50 text-emerald-700'],
                                    default => [$f->status, 'bg-slate-100 text-slate-700'],
                                };
                            @endphp
                            <td class="px-5 py-3"><span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $status[1] }}">{{ $status[0] }}</span></td>
                            <td class="px-5 py-3 text-center">
                                @if ($f->status === 'Diajukan')
                                    <form method="POST" action="{{ route('e-po.logistik.mulai-forecast', $f) }}">
                                        @csrf
                                        <button class="text-[11px] font-bold text-white bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded">Mulai Penetapan</button>
                                    </form>
                                @else
                                    <a href="{{ route('e-po.logistik.detail-forecast', $f) }}" class="text-[11px] font-semibold text-slate-600 border border-slate-200 px-3 py-1.5 rounded hover:bg-slate-50"><i class="fa-regular fa-eye mr-1"></i>{{ $f->status === 'Diproses' ? 'Lanjutkan' : 'Lihat' }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada forecast yang diajukan distributor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab==='epo'" style="display:none" class="overflow-x-auto">
            <table class="w-full text-sm min-w-[850px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Nomor PO</th>
                        <th class="px-5 py-3 text-left">Distributor & Wilayah</th>
                        <th class="px-5 py-3 text-left">Periode</th>
                        <th class="px-5 py-3 text-right">Volume</th>
                        <th class="px-5 py-3 text-right">Nilai PO</th>
                        <th class="px-5 py-3 text-left">Diajukan</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pos as $po)
                        <tr class="hover:bg-slate-50" x-show="@js(strtolower($po->nomor_po.' '.$po->forecast->distributor->nama)).includes(q.toLowerCase())">
                            <td class="px-5 py-3 font-bold text-red-600">{{ $po->nomor_po }}</td>
                            <td class="px-5 py-3"><p class="font-semibold text-slate-900">{{ $po->forecast->distributor->nama }}</p><p class="text-[11px] text-slate-500">{{ $po->forecast->distributor->wilayah->nama_wilayah }}</p></td>
                            <td class="px-5 py-3">{{ $tanggalEpo($po->forecast->periode, 'M Y') }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ number_format($po->total_kontainer, 0, ',', '.') }} ktr</td>
                            <td class="px-5 py-3 text-right">Rp {{ number_format($po->total_nilai, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $tanggalEpo($po->tanggal_pengajuan, 'd M Y') }}</td>
                            @php
                                $status = match ($po->status) {
                                    'Diajukan' => ['Menunggu verifikasi', 'bg-amber-50 text-amber-700'],
                                    'Diproses' => ['Sedang diproses', 'bg-blue-50 text-blue-700'],
                                    'Disetujui' => ['Disetujui', 'bg-emerald-50 text-emerald-700'],
                                    default => [$po->status, 'bg-slate-100 text-slate-700'],
                                };
                            @endphp
                            <td class="px-5 py-3"><span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $status[1] }}">{{ $status[0] }}</span></td>
                            <td class="px-5 py-3 text-center">
                                @if ($po->status === 'Diajukan')
                                    <form method="POST" action="{{ route('e-po.logistik.mulai-epo', $po) }}">
                                        @csrf
                                        <button class="inline-block text-[11px] font-bold text-white bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded">Mulai Verifikasi</button>
                                    </form>
                                @else
                                    <a href="{{ route('e-po.logistik.verifikasi-epo', $po) }}" class="text-[11px] font-semibold text-slate-600 border border-slate-200 px-3 py-1.5 rounded hover:bg-slate-50"><i class="fa-regular fa-eye mr-1"></i>{{ $po->status === 'Diproses' ? 'Lanjutkan' : 'Lihat Arsip' }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada E-PO yang masuk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
