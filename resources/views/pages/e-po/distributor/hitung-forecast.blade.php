@extends('layouts.app')

@section('title', 'Hitung Forecast')

@section('content')

<div class="p-6 space-y-6">

    @if (! isset($distributor))
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
        <i class="fa-solid fa-store-slash text-3xl text-slate-300"></i>
        <h2 class="mt-3 text-lg font-bold text-slate-900">Belum ada distributor aktif</h2>
        <p class="mt-1 text-sm text-slate-500">Tambahkan data distributor dengan status aktif terlebih dahulu untuk memakai modul E-PO.</p>
    </div>
    @else

    {{-- Hero --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-wrap items-center justify-between gap-6">
        <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider text-red-600 bg-red-50 px-2.5 py-1 rounded-full">
                <i class="fa-solid fa-calendar-days"></i>
                Tahap Forecast - {{ $tahunFiskal ? 'FY-'.$tahunFiskal->tanggal_mulai->year : 'Tahun fiskal belum terdaftar' }} - Periode {{ $tanggalEpo($periode, 'F Y') }}
            </span>
            <h2 class="mt-3 text-xl font-bold text-slate-900">Mulai Penyusunan Estimasi Kebutuhan Produk</h2>
            <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                Sistem menghitung kebutuhan dalam karton dari histori Sell-Out 3 bulan terakhir dengan bobot lebih besar
                untuk bulan terbaru, menambahkan stok pengaman 10%, lalu membulatkannya ke kontainer sesuai kemasan SKU.
            </p>
            @if ($forecastTarget)
                <p class="mt-3 text-xs text-slate-600">Status forecast periode ini:
                    <span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $statusLabel['class'] }}">{{ $statusLabel['label'] }}</span>
                </p>
            @endif
        </div>
        @if (! $forecastTarget || $forecastTarget->status === 'Draft')
            <a href="{{ route('e-po.distributor.ajukan-forecast', ['periode' => $periode->format('Y-m')]) }}"
               class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-6 py-3 rounded-lg shadow transition">
                <i class="fa-solid fa-calculator"></i>
                {{ $forecastTarget ? 'Lanjutkan Draft' : 'Hitung Forecast Baru' }}
            </a>
        @elseif ($forecastTarget->status === 'Disetujui' && ! $poTarget)
            <a href="{{ route('e-po.distributor.input-epo') }}"
               class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-6 py-3 rounded-lg shadow transition">
                <i class="fa-solid fa-file-invoice"></i>
                Lanjutkan ke Input E-PO
            </a>
        @else
            <span class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-4 py-3 text-sm font-bold text-slate-600">
                <i class="fa-solid fa-clock"></i>
                {{ $statusLabel['label'] }}
            </span>
        @endif
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div>
            <p class="text-sm font-bold text-slate-900">Pilih periode forecast</p>
            <p class="text-xs text-slate-500">Gunakan periode lain untuk membuat pengajuan baru, bukan membuka detail pengajuan lama.</p>
        </div>
        <form method="GET" action="{{ route('e-po.distributor.hitung-forecast') }}" class="flex items-center gap-2">
            <select name="periode" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" onchange="this.form.submit()">
                @foreach ($periodePilihan as $pilihan)
                    <option value="{{ $pilihan->format('Y-m') }}" @selected($pilihan->isSameMonth($periode))>{{ $tanggalEpo($pilihan, 'F Y') }}</option>
                @endforeach
            </select>
            <noscript><button class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-bold text-white">Pilih</button></noscript>
        </form>
    </div>

    @if ($forecastTarget)
        @php
            $tahapAktif = match ($forecastTarget->status) {
                'Draft' => 1,
                'Diajukan', 'Diproses' => 2,
                'Disetujui' => $poTarget ? 4 : 3,
                default => 1,
            };
        @endphp
        <div id="status-periode" class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Proses Forecast {{ $tanggalEpo($periode, 'F Y') }}</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Status Anda: <span class="{{ $statusLabel['class'] }}">{{ $statusLabel['label'] }}</span></h3>
                </div>
                @if ($forecastTarget->status === 'Diajukan')
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">Menunggu Logistik Pusat menetapkan kuota.</p>
                @elseif ($forecastTarget->status === 'Diproses')
                    <p class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700">Sedang ditinjau oleh Logistik Pusat.</p>
                @elseif ($forecastTarget->status === 'Disetujui' && ! $poTarget)
                    <p class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">Kuota tersedia. Anda dapat membuat E-PO.</p>
                @endif
            </div>
            <div class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach ([
                    ['Forecast dibuat', 'Data usulan tersimpan'],
                    ['Menunggu penetapan', 'Logistik memeriksa usulan'],
                    ['Kuota disetujui', 'Plafon E-PO tersedia'],
                    ['E-PO diterbitkan', 'Dokumen resmi selesai'],
                ] as $nomor => [$judul, $uraian])
                    <div class="rounded-lg border p-3 {{ $nomor + 1 <= $tahapAktif ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold {{ $nomor + 1 <= $tahapAktif ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500' }}">{{ $nomor + 1 }}</span>
                            <span class="text-xs font-bold {{ $nomor + 1 <= $tahapAktif ? 'text-emerald-800' : 'text-slate-500' }}">{{ $judul }}</span>
                        </div>
                        <p class="mt-2 text-[10px] text-slate-500">{{ $uraian }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Tindak lanjut: kuota sudah ditetapkan --}}
    @if ($siapInputEpo)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 flex flex-wrap items-center justify-between gap-4">
            <div class="text-sm">
                <p class="font-bold text-emerald-800"><i class="fa-solid fa-circle-check mr-2"></i>Kuota periode {{ $tanggalEpo($siapInputEpo->periode, 'F Y') }} sudah ditetapkan</p>
                <p class="text-xs text-emerald-700 mt-1">
                    Total kuota {{ number_format($siapInputEpo->detailForecastRecords->sum('jumlah_disetujui'), 0, ',', '.') }} kontainer. Lanjutkan dengan membuat E-PO.
                </p>
            </div>
            <a href="{{ route('e-po.distributor.input-epo') }}" class="px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                <i class="fa-solid fa-file-invoice mr-1"></i> Input E-PO
            </a>
        </div>
    @endif

    {{-- Stat --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tahun Fiskal Berjalan</p>
            @if ($tahunFiskal)
                <p class="mt-2 text-2xl font-extrabold text-slate-900">FY-{{ $tahunFiskal->tanggal_mulai->year }}</p>
                <p class="text-xs text-slate-500">{{ $tanggalEpo($tahunFiskal->tanggal_mulai, 'd M Y') }} - {{ $tanggalEpo($tahunFiskal->tanggal_selesai, 'd M Y') }}</p>
                <span class="mt-3 inline-block text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded">{{ $tahunFiskal->status ?? 'Aktif' }}</span>
            @else
                <p class="mt-2 text-sm text-red-600 font-semibold">Belum ada tahun fiskal yang mencakup periode ini.</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Batas Waktu Pengajuan</p>
            <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $tanggalEpo($tenggat) }}</p>
            <p class="text-xs text-slate-500">Pukul {{ $tenggat->format('H.i') }} WIB - periode {{ $tanggalEpo($periode, 'F Y') }}</p>
            @if ($hariSisa < 0)
                <span class="mt-3 inline-block text-[10px] font-bold bg-red-50 text-red-700 px-2 py-0.5 rounded">Batas terlewati</span>
            @elseif ($hariSisa <= 3)
                <span class="mt-3 inline-block text-[10px] font-bold bg-amber-50 text-amber-700 px-2 py-0.5 rounded">Segera berakhir ({{ $hariSisa }} hari)</span>
            @else
                <span class="mt-3 inline-block text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded">{{ $hariSisa }} hari lagi</span>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Realisasi Periode Lalu</p>
            @if ($realisasi)
                <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $realisasi['persen'] }}% <span class="text-sm font-semibold text-slate-500">dari kuota</span></p>
                <div class="mt-3 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-emerald-500 rounded-full" style="width: {{ min(100, $realisasi['persen']) }}%"></div></div>
                <p class="mt-1 text-[10px] text-slate-500">{{ $tanggalEpo($realisasi['periode'], 'F Y') }}: {{ $realisasi['dipesan'] }} dari {{ $realisasi['kuota'] }} kontainer dipesan</p>
            @else
                <p class="mt-2 text-sm text-slate-500">Belum ada PO terbit sebagai pembanding.</p>
            @endif
        </div>
    </div>

    {{-- Tahapan --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="text-sm font-bold text-slate-900 mb-4"><i class="fa-solid fa-route text-red-600 mr-2"></i>Tahapan Siklus E-PO Periode Berjalan</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach ([
                ['Forecast & Usulan', 'Distributor mengajukan kontainer per SKU berdasarkan rekomendasi sistem.'],
                ['Penetapan Kuota Pabrik', 'Logistik Pusat mengevaluasi dan menetapkan kuota per SKU.'],
                ['Pemesanan E-PO', 'Distributor membuat pesanan sesuai plafon kuota yang disetujui.'],
                ['Dokumen PO Resmi', 'PO terbit setelah diverifikasi Logistik Pusat.'],
            ] as $i => [$judul, $uraian])
                <div class="rounded-lg border p-4 {{ $i === 0 ? 'bg-red-50/50 border-red-200' : 'border-slate-200' }}">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $i === 0 ? 'bg-red-600 text-white' : 'bg-slate-200 text-slate-600' }}">{{ $i + 1 }}</span>
                    <p class="mt-3 text-sm font-bold text-slate-900">{{ $judul }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $uraian }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Riwayat --}}
    <div id="riwayat" class="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-clock-rotate-left text-red-600 mr-2"></i>Riwayat Pengajuan Forecast</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[800px]">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Kode Forecast</th>
                        <th class="px-5 py-3 text-left">Periode</th>
                        <th class="px-5 py-3 text-left">SKU Diajukan</th>
                        <th class="px-5 py-3 text-left">Tanggal Pengajuan</th>
                        <th class="px-5 py-3 text-right">Usulan (Kontainer)</th>
                        <th class="px-5 py-3 text-right">Kuota (Kontainer)</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Nomor PO</th>
                        <th class="px-5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($riwayat as $f)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono text-xs font-bold text-red-600">FC-{{ str_pad((string) $f->id_forecast, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $tanggalEpo($f->periode, 'F Y') }}</td>
                            <td class="px-5 py-3">
                                @if ($f->detailForecastRecords->isNotEmpty())
                                    <p class="text-xs font-semibold text-slate-700">{{ $f->detailForecastRecords->count() }} SKU</p>
                                    <p class="mt-1 max-w-48 truncate text-[10px] text-slate-500" title="{{ $f->detailForecastRecords->map(fn ($d) => $d->produk->kode_sku)->join(', ') }}">
                                        {{ $f->detailForecastRecords->map(fn ($d) => $d->produk->kode_sku)->join(', ') }}
                                    </p>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $tanggalEpo($f->tanggal_pengajuan, 'd M Y, H:i') }}</td>
                            <td class="px-5 py-3 text-right">{{ number_format($f->total_usulan ?? 0, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-right font-semibold">{{ $f->total_disetujui !== null && $f->status === 'Disetujui' ? number_format($f->total_disetujui, 0, ',', '.') : '-' }}</td>
                            @php
                                $status = match ($f->status) {
                                    'Draft' => ['Draft', 'bg-slate-100 text-slate-700'],
                                    'Diajukan' => ['Menunggu penetapan', 'bg-amber-50 text-amber-700'],
                                    'Diproses' => ['Sedang diproses', 'bg-blue-50 text-blue-700'],
                                    'Disetujui' => ['Disetujui', 'bg-emerald-50 text-emerald-700'],
                                    default => [$f->status, 'bg-slate-100 text-slate-700'],
                                };
                            @endphp
                            <td class="px-5 py-3"><span class="inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $status[1] }}">{{ $status[0] }}</span></td>
                            <td class="px-5 py-3 text-slate-600">
                                @forelse ($f->purchaseOrderRecords->where('status', '!=', 'Draft') as $po)
                                    @php
                                        $poStatus = match ($po->status) {
                                            'Diajukan' => ['Menunggu verifikasi', 'bg-amber-50 text-amber-700'],
                                            'Diproses' => ['Sedang diproses', 'bg-blue-50 text-blue-700'],
                                            'Disetujui' => ['Disetujui', 'bg-emerald-50 text-emerald-700'],
                                            default => [$po->status, 'bg-slate-100 text-slate-700'],
                                        };
                                    @endphp
                                    <div class="mb-1 flex items-center gap-2">
                                        <a href="{{ route('e-po.distributor.epo-selesai', $po) }}" class="font-mono text-xs text-red-600 hover:underline">{{ $po->nomor_po }}</a>
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded {{ $poStatus[1] }}">{{ $poStatus[0] }}</span>
                                    </div>
                                @empty
                                    -
                                @endforelse
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if ($f->status === 'Draft')
                                    <a href="{{ route('e-po.distributor.ajukan-forecast', ['periode' => $f->periode->format('Y-m')]) }}" class="text-xs font-bold text-red-600 hover:underline">Lanjutkan</a>
                                @elseif ($f->status === 'Disetujui' && $f->purchaseOrderRecords->where('status', '!=', 'Draft')->isNotEmpty())
                                    @php $poRiwayat = $f->purchaseOrderRecords->first(fn ($po) => $po->status !== 'Draft'); @endphp
                                    <a href="{{ route('e-po.distributor.epo-selesai', $poRiwayat) }}" class="text-xs font-bold text-emerald-700 hover:underline">Lihat E-PO</a>
                                @elseif ($f->status === 'Disetujui')
                                    <a href="{{ route('e-po.distributor.input-epo') }}" class="text-xs font-bold text-emerald-700 hover:underline">Input E-PO</a>
                                @else
                                    <a href="#status-periode" class="text-xs font-semibold text-slate-500 hover:text-red-600">Lihat proses</a>
                                @endif
                                @if ($f->status === 'Draft')
                                    <form method="POST" action="{{ route('e-po.distributor.hapus-forecast', $f) }}" class="inline ml-2" onsubmit="return confirm('Hapus draft forecast ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-red-600" title="Hapus draft"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-5 py-10 text-center text-slate-500">Belum ada riwayat forecast.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
