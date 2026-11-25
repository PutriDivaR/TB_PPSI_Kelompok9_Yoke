@extends('layouts.app')

@section('title', 'Antrean Forecast & E-PO')

@section('content')
<div class="space-y-6" x-data="{ tab: 'forecast', q: '' }">

    {{-- HEADER --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg"><i class="fa-solid fa-truck-ramp-box"></i></div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Logistik Pusat &middot; PT Yoke Food Industries Indonesia</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">Antrean Usulan &amp; Verifikasi E-PO</h1>
            </div>
        </div>
        @php $langkah = ['Penetapan Kuota', 'Verifikasi E-PO']; $aktif = $stats['forecast_pending'] > 0 ? 1 : 2; @endphp
        <ol class="flex items-center gap-2 text-xs">
            @foreach ($langkah as $i => $label)
                @php $n = $i + 1; @endphp
                <li class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold {{ $n < $aktif ? 'bg-emerald-500 text-white' : ($n == $aktif ? 'bg-red-600 text-white ring-4 ring-red-100' : 'bg-slate-200 text-slate-500') }}">
                        @if ($n < $aktif) <i class="fa-solid fa-check text-[10px]"></i> @else {{ $n }} @endif
                    </span>
                    <span class="{{ $n == $aktif ? 'font-bold text-slate-900' : 'text-slate-500' }}">{{ $label }}</span>
                    @if (! $loop->last) <span class="w-8 h-px bg-slate-300"></span> @endif
                </li>
            @endforeach
        </ol>
    </div>

    @include('layouts.partials.flash')

    {{-- STATISTIK --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Antrean Usulan Forecast</p><i class="fa-solid fa-hourglass-half text-red-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['forecast_pending'] }} <span class="text-xs text-slate-500">Pengajuan</span></p>
            <p class="text-[10px] text-slate-500">Perlu penetapan kuota</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Volume Usulan Antrean</p><i class="fa-solid fa-cubes text-blue-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['volume_antrean'], 0, ',', '.') }} <span class="text-xs text-slate-500">Karton</span></p>
            <p class="text-[10px] text-slate-500">&asymp; {{ number_format($stats['volume_antrean'] / $kartonPerKontainer, 1, ',', '.') }} kontainer</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">E-PO Menunggu Verifikasi</p><i class="fa-solid fa-file-circle-question text-amber-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['po_verifikasi'] }} <span class="text-xs text-slate-500">PO</span></p>
            <p class="text-[10px] text-slate-500">Siap diperiksa Logistik</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex justify-between"><p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Utilisasi Kapasitas {{ $tanggalEpo($periodeIni, 'M Y') }}</p><i class="fa-solid fa-industry text-emerald-500"></i></div>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $stats['utilisasi'] }}%</p>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-emerald-500" style="width: {{ min(100, $stats['utilisasi']) }}%"></div></div>
            <p class="mt-1 text-[10px] text-slate-500">{{ number_format($stats['terpakai'], 0, ',', '.') }} dari {{ number_format($stats['kapasitas'], 0, ',', '.') }} karton</p>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 pt-4">
            <div class="flex gap-6 text-sm">
                <button type="button" @click="tab='forecast'" class="border-b-2 pb-3 font-bold transition" :class="tab==='forecast' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500'">
                    Pengajuan Forecasting <span class="ml-1 rounded-full bg-red-600 px-1.5 text-[10px] text-white">{{ $stats['forecast_pending'] }}</span>
                </button>
                <button type="button" @click="tab='epo'" class="border-b-2 pb-3 font-bold transition" :class="tab==='epo' ? 'border-red-600 text-red-600' : 'border-transparent text-slate-500'">
                    Daftar Transaksi E-PO <span class="ml-1 rounded-full bg-slate-200 px-1.5 text-[10px] text-slate-700">{{ $pos->count() }}</span>
                </button>
            </div>
            <div class="relative pb-3">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-xs text-slate-400"></i>
                <input x-model="q" type="text" placeholder="Cari distributor / nomor PO..." class="w-60 rounded-lg border border-slate-200 py-1.5 pl-8 pr-3 text-xs">
            </div>
        </div>

        <div x-show="tab==='forecast'" class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Distributor &amp; Wilayah</th>
                        <th class="px-5 py-3 text-left">Periode</th>
                        <th class="px-5 py-3 text-right">Usulan (Karton)</th>
                        <th class="px-5 py-3 text-right">Kuota (Karton)</th>
                        <th class="px-5 py-3 text-right">Nilai Usulan</th>
                        <th class="px-5 py-3 text-left">Diajukan</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($forecasts as $f)
                        @php $bf = $badge($f->status, 'forecast'); $menunggu = in_array($f->status, ['Diajukan', 'Diproses'], true); @endphp
                        <tr class="hover:bg-slate-50" x-show="@js(strtolower($f->distributor->nama.' '.$f->distributor->wilayah->nama_wilayah)).includes(q.toLowerCase())">
                            <td class="px-5 py-3"><p class="font-semibold text-slate-900">{{ $f->distributor->nama }}</p><p class="text-[11px] text-slate-500">{{ $f->distributor->wilayah->nama_wilayah }}</p></td>
                            <td class="px-5 py-3">{{ $tanggalEpo($f->periode, 'M Y') }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ number_format($f->totals['usulan'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right">{{ $f->status === 'Disetujui' ? number_format($f->totals['disetujui'], 0, ',', '.') : '-' }}</td>
                            <td class="px-5 py-3 text-right">Rp {{ number_format($f->totals['nilai_usulan'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $tanggalEpo($f->tanggal_pengajuan, 'd M Y') }}</td>
                            <td class="px-5 py-3"><span class="inline-block rounded px-2 py-1 text-[10px] font-bold {{ $bf['class'] }}">&#9679; {{ $bf['label'] }}</span></td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('e-po.logistik.detail-forecast', $f) }}" class="{{ $menunggu ? 'inline-block rounded bg-red-600 px-3 py-1.5 text-[11px] font-bold leading-tight text-white hover:bg-red-700' : 'rounded border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50' }}">
                                    {!! $menunggu ? 'Tinjau &amp;<br>Tetapkan Kuota' : '<i class="fa-regular fa-eye mr-1"></i>Lihat' !!}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Belum ada forecast yang diajukan distributor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab==='epo'" style="display:none" class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Nomor PO</th>
                        <th class="px-5 py-3 text-left">Distributor &amp; Wilayah</th>
                        <th class="px-5 py-3 text-left">Periode</th>
                        <th class="px-5 py-3 text-right">Volume (Karton)</th>
                        <th class="px-5 py-3 text-right">Nilai PO</th>
                        <th class="px-5 py-3 text-left">Diajukan</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pos as $po)
                        @php $bp = $badge($po->status, 'po'); $menunggu = in_array($po->status, ['Diajukan', 'Diproses'], true); @endphp
                        <tr class="hover:bg-slate-50" x-show="@js(strtolower($po->nomor_po.' '.$po->forecast->distributor->nama)).includes(q.toLowerCase())">
                            <td class="px-5 py-3 font-bold text-red-600">{{ $po->nomor_po }}</td>
                            <td class="px-5 py-3"><p class="font-semibold text-slate-900">{{ $po->forecast->distributor->nama }}</p><p class="text-[11px] text-slate-500">{{ $po->forecast->distributor->wilayah->nama_wilayah }}</p></td>
                            <td class="px-5 py-3">{{ $tanggalEpo($po->forecast->periode, 'M Y') }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ number_format($po->total_karton, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right">Rp {{ number_format($po->total_nilai, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $tanggalEpo($po->tanggal_pengajuan, 'd M Y') }}</td>
                            <td class="px-5 py-3"><span class="inline-block rounded px-2 py-1 text-[10px] font-bold {{ $bp['class'] }}">&#9679; {{ $bp['label'] }}</span></td>
                            <td class="px-5 py-3 text-center">
                                <a href="{{ route('e-po.logistik.verifikasi-epo', $po) }}" class="{{ $menunggu ? 'inline-block rounded bg-red-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-red-700' : 'rounded border border-slate-200 px-3 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50' }}">
                                    {!! $menunggu ? 'Verifikasi E-PO' : '<i class="fa-regular fa-eye mr-1"></i>Lihat Dokumen' !!}
                                </a>
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