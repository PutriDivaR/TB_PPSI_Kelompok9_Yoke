<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bak->nomor_bak }} - Berita Acara Kerusakan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100/80 font-sans antialiased text-slate-800 print:bg-white" @if ($cetak) onload="window.print()" @endif>
<div class="max-w-3xl mx-auto p-6 print:p-0 space-y-4">

    <div class="flex justify-end gap-2 print:hidden">
        <button type="button" onclick="window.print()" class="px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700">Cetak / Simpan PDF</button>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 print:border-0 print:shadow-none">
        <div class="text-center border-b border-slate-200 pb-4">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">PT Yoke Food Industries Indonesia</p>
            <h1 class="mt-1 text-xl font-extrabold uppercase tracking-wide text-slate-900">Berita Acara Kerusakan Barang</h1>
            <p class="mt-1 font-mono text-sm font-bold text-red-600">{{ $bak->nomor_bak }}</p>
            @if ($bak->status !== \App\Services\KlaimService::BAK_DISAHKAN)
                <p class="mt-2 inline-flex text-xs font-bold px-2 py-1 rounded bg-red-50 text-red-700">{{ $bak->status }} — menunggu verifikasi ulang SAM</p>
            @endif
        </div>

        <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div class="col-span-2"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Distributor</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $distributor->nama }}, {{ $distributor->wilayah?->nama_wilayah }}</dd></div>
            <div class="col-span-2"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Alamat Gudang</dt><dd class="mt-0.5 text-slate-700">{{ $distributor->alamat_gudang ?? '-' }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Kode Klaim</dt><dd class="mt-0.5 font-mono font-bold text-red-600">{{ $kodeKlaim }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor PO</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $klaim->penerimaan->pengiriman->po->nomor_po }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor Kontainer</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $klaim->penerimaan->pengiriman->nomor_kontainer }}</dd></div>
            <div><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tanggal Tiba</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $tanggalEpo($klaim->penerimaan->tanggal_terima, 'd F Y') }}</dd></div>
        </dl>

        <p class="mt-5 text-sm text-slate-700 leading-relaxed">Pada tanggal {{ $tanggalEpo($bak->tanggal_pengesahan, 'd F Y') }} telah dilakukan pemeriksaan fisik langsung di gudang distributor oleh Sales Area Manager dengan hasil sebagai berikut:</p>

        <table class="mt-3 w-full text-sm border border-slate-200">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-3 py-2 text-left">No</th>
                    <th class="px-3 py-2 text-left">Produk</th>
                    <th class="px-3 py-2 text-left">Kerusakan</th>
                    <th class="px-3 py-2 text-right">Diajukan</th>
                    <th class="px-3 py-2 text-right">Terverifikasi</th>
                    <th class="px-3 py-2 text-left">Penanggung</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($baris as $b)
                    <tr>
                        <td class="px-3 py-2">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2 font-semibold text-slate-900">{{ $b['produk'] }}@if ($b['catatan'])<p class="text-xs font-normal text-slate-500">{{ $b['catatan'] }}</p>@endif</td>
                        <td class="px-3 py-2">{{ $b['jenis'] }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($b['diajukan'], 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right font-semibold">{{ number_format($b['terverifikasi'], 0, ',', '.') }}</td>
                        <td class="px-3 py-2">{{ $b['penanggung'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 text-sm">
            @foreach ($perPenanggung as $penanggung => $karton)
                <p class="flex justify-between border-b border-dashed border-slate-200 py-1"><span>Ditanggung {{ $penanggung }}</span><span class="font-bold">{{ number_format($karton, 0, ',', '.') }} karton</span></p>
            @endforeach
        </div>

        <div class="mt-8 flex justify-end">
            <div class="text-center text-sm">
                <p class="text-slate-500">Disahkan secara digital oleh,</p>
                @if ($adaTandaTangan)
                    <img src="{{ route('klaim.tanda-tangan', $klaim) }}" alt="Tanda tangan {{ $bak->pengesah->nama }}" class="mx-auto my-2 h-20">
                @else
                    <p class="my-6 text-xs text-slate-400">(berkas tanda tangan tidak tersedia)</p>
                @endif
                <p class="font-bold text-slate-900">{{ $bak->pengesah->nama }}</p>
                <p class="text-xs text-slate-500">Sales Area Manager</p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
