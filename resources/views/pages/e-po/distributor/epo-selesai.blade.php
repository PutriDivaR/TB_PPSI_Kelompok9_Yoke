@extends('layouts.app')

@section('title', 'Dokumen E-PO')

@section('content')
<div class="p-6 space-y-6">
    @if (! $po)
        <div class="bg-white rounded-xl border border-slate-200 p-10 text-center">
            <h2 class="text-lg font-bold text-slate-900">Belum ada E-PO yang diajukan</h2>
            <a href="{{ route('e-po.distributor.input-epo') }}" class="mt-4 inline-flex px-5 py-2.5 bg-red-600 text-white rounded-lg text-sm font-bold">Input E-PO</a>
        </div>
    @else
        @php
            $disetujui = $po->status === 'Disetujui';
            $status = $disetujui ? ['Disetujui', 'bg-emerald-50 text-emerald-700'] : ($po->status === 'Diproses' ? ['Sedang diproses', 'bg-blue-50 text-blue-700'] : ['Menunggu verifikasi', 'bg-amber-50 text-amber-700']);
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 print:hidden">
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor E-PO</p>
                <p class="text-xl font-extrabold text-slate-900">{{ $po->nomor_po }}</p>
                <span class="mt-2 inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $status[1] }}">{{ $status[0] }}</span>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Nilai Pesanan</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900">Rp {{ number_format($dok['total_nilai'], 0, ',', '.') }}</p>
                <p class="text-xs text-slate-500">{{ number_format($dok['total_karton'], 0, ',', '.') }} karton</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Periode Forecast</p>
                <p class="mt-2 text-xl font-extrabold text-slate-900">{{ $tanggalEpo($po->forecast->periode, 'F Y') }}</p>
                <p class="text-xs text-slate-500">Diajukan {{ $tanggalEpo($po->tanggal_pengajuan, 'd M Y, H:i') }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm max-w-5xl mx-auto p-8 text-slate-800 print:border-0 print:shadow-none">
            <div class="flex justify-between items-start border-b-2 border-slate-800 pb-4">
                <div>
                    <p class="text-base font-extrabold">{{ $pabrik['nama'] }}</p>
                    <p class="text-[10px] text-slate-500 max-w-xs">{{ $pabrik['alamat'] }}</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-bold bg-red-600 text-white px-2 py-1 rounded">PURCHASE ORDER</span>
                    <p class="mt-2 text-lg font-extrabold text-red-600">{{ $po->nomor_po }}</p>
                    <span class="mt-1 inline-flex text-[10px] font-bold px-2 py-1 rounded {{ $status[1] }}">{{ $status[0] }}</span>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mt-5 text-xs">
                <div>
                    <p class="text-[10px] font-bold uppercase text-slate-500 mb-1">Pemesan</p>
                    <p class="font-bold text-sm">{{ $po->forecast->distributor->nama }}</p>
                    <p class="text-slate-600">{{ $po->forecast->distributor->alamat_gudang }}</p>
                    <p class="text-slate-600">MoU: {{ $po->forecast->distributor->nomor_mou ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-slate-500 mb-1">Informasi Dokumen</p>
                    <p><b>Periode:</b> {{ $tanggalEpo($po->forecast->periode, 'F Y') }}</p>
                    <p><b>Tanggal terbit:</b> {{ $tanggalEpo($po->tanggal_disetujui, 'd F Y, H:i') }}</p>
                </div>
            </div>

            <table class="w-full text-xs mt-6 border border-slate-200">
                <thead class="bg-slate-100 uppercase text-[10px]">
                    <tr>
                        <th class="px-3 py-2 text-left">No</th>
                        <th class="px-3 py-2 text-left">Deskripsi SKU</th>
                        <th class="px-3 py-2 text-right">Kontainer</th>
                        <th class="px-3 py-2 text-right">Karton</th>
                        <th class="px-3 py-2 text-right">Harga/Karton</th>
                        <th class="px-3 py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($dok['rows'] as $r)
                        <tr>
                            <td class="px-3 py-2">{{ $r['no'] }}</td>
                            <td class="px-3 py-2"><p class="font-semibold">{{ $r['nama'] }}</p><p class="text-[10px] text-slate-500">{{ $r['kode'] }}</p></td>
                            <td class="px-3 py-2 text-right">{{ number_format($r['kontainer'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($r['karton'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">Rp {{ number_format($r['harga'], 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right font-semibold">Rp {{ number_format($r['subtotal'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 font-bold">
                    <tr>
                        <td colspan="2" class="px-3 py-2">TOTAL</td>
                        <td class="px-3 py-2 text-right">{{ number_format($dok['total_kontainer'], 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($dok['total_karton'], 0, ',', '.') }}</td>
                        <td></td>
                        <td class="px-3 py-2 text-right">Rp {{ number_format($dok['total_nilai'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="mt-10 flex justify-end">
                <div class="text-center text-xs min-w-48">
                    <p>{{ $pabrik['plant'] }}, {{ $tanggalEpo($po->tanggal_disetujui ?? $po->tanggal_pengajuan, 'd F Y') }}</p>
                    @if ($disetujui)
                        <div class="mx-auto my-2 w-24 h-16 border-2 border-emerald-600 text-emerald-600 rounded-full rotate-[-8deg] flex items-center justify-center font-black text-[10px]">DISETUJUI<br>LOGISTIK PUSAT</div>
                        <p class="font-bold">{{ $po->penyetuju?->nama ?? 'Logistik Pusat' }}</p>
                    @else
                        <div class="mx-auto my-2 w-24 h-16 border border-dashed border-slate-400 text-slate-400 rounded flex items-center justify-center text-[10px]">MENUNGGU<br>PERSETUJUAN</div>
                        <p class="text-slate-500">Belum diterbitkan</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex justify-center gap-3 print:hidden">
            <button onclick="window.print()" class="px-6 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700"><i class="fa-solid fa-print mr-1"></i> Cetak / Simpan PDF</button>
            <a href="{{ route('e-po.distributor.hitung-forecast') }}" class="px-6 py-2.5 text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">Kembali</a>
        </div>
    @endif
</div>
@endsection
