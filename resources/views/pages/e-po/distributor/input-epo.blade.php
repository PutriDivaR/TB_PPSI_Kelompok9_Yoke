@extends('layouts.app')

@section('title', 'Input E-PO')

@section('content')

<div class="p-6 space-y-6">

    

    

    @if (! $forecast)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center text-xl"><i class="fa-solid fa-hourglass-half"></i></div>
            <h2 class="mt-4 text-lg font-bold text-slate-900">Belum ada forecast yang siap dipesan</h2>
            <p class="mt-1 text-sm text-slate-500">E-PO dapat dibuat setelah Logistik Pusat menetapkan kuota atas forecast yang Anda ajukan.</p>
            <a href="{{ route('e-po.distributor.hitung-forecast') }}" class="mt-5 inline-block px-5 py-2.5 text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Hitung Forecast
            </a>
        </div>
    @else
        <div class="space-y-6"
             x-data="{
                rows: {{ \Illuminate\Support\Js::from($baris) }},
                fmt(n) { return new Intl.NumberFormat('id-ID').format(n) },
                over(r) { return Number(r.input || 0) > r.kuota },
                get plafon() { return this.rows.reduce((a, r) => a + r.kuota, 0) },
                get total() { return this.rows.reduce((a, r) => a + Number(r.input || 0), 0) },
                get karton() { return this.rows.reduce((a, r) => a + Number(r.input || 0) * r.kpk, 0) },
                get nilai() { return this.rows.reduce((a, r) => a + Number(r.input || 0) * r.kpk * r.harga, 0) },
                get overCount() { return this.rows.filter(r => this.over(r)).length },
                get sisa() { return this.plafon - this.total },
                get pct() { return this.plafon ? Math.min(100, Math.round(this.total / this.plafon * 100)) : 0 }
             }">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-white rounded-xl border shadow-sm p-5" :class="overCount ? 'border-red-300' : 'border-slate-200'">
                    <div class="flex flex-wrap justify-between items-start gap-2">
                        <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-gauge-high text-red-600 mr-2"></i>Sisa Kuota E-PO {{ $tanggalEpo($forecast->periode, 'F Y') }}</h3>
                        <span class="text-[10px] font-bold px-2 py-1 rounded" :class="overCount ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'"
                              x-text="overCount ? 'Peringatan: Melebihi Kuota SKU' : 'Pemesanan Sesuai Kuota'"></span>
                    </div>
                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="text-[10px] font-bold uppercase text-slate-500">Sisa Kuota</p>
                            <p class="text-2xl font-extrabold"><span x-text="fmt(plafon)"></span> <span class="text-xs text-slate-500">Kontainer</span></p>
                            <p class="text-[10px] text-slate-500">Setelah E-PO sebelumnya</p>
                        </div>
                        <div class="rounded-lg p-3" :class="overCount ? 'bg-red-50' : 'bg-slate-50'">
                            <p class="text-[10px] font-bold uppercase" :class="overCount ? 'text-red-600' : 'text-slate-500'">Total Pesanan Input</p>
                            <p class="text-2xl font-extrabold" :class="overCount ? 'text-red-600' : ''"><span x-text="fmt(total)"></span> <span class="text-xs text-slate-500">Kontainer</span></p>
                            <p class="text-[10px] text-slate-500">Setara <span x-text="fmt(karton)"></span> karton</p>
                        </div>
                        <div class="rounded-lg p-3" :class="sisa < 0 ? 'bg-red-50' : 'bg-emerald-50'">
                            <p class="text-[10px] font-bold uppercase text-slate-500">Sisa Kuota</p>
                            <p class="text-2xl font-extrabold" :class="sisa < 0 ? 'text-red-600' : 'text-emerald-600'" x-text="fmt(sisa)"></p>
                            <p class="text-[10px]" :class="sisa < 0 ? 'text-red-600' : 'text-emerald-600'" x-text="sisa < 0 ? 'Melebihi sisa kuota' : 'Lolos validasi'"></p>
                        </div>
                    </div>
                    <div class="mt-4 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full rounded-full transition-all" :class="overCount ? 'bg-red-500' : 'bg-emerald-500'" :style="`width:${pct}%`"></div></div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Ringkasan Nilai E-PO</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">Rp <span x-text="fmt(nilai)"></span></p>
                    <dl class="mt-4 space-y-2 text-xs">
                        <div class="flex justify-between"><dt class="text-slate-500">Distributor</dt><dd class="font-semibold text-right">{{ $distributor->nama }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Jumlah SKU</dt><dd class="font-semibold">{{ count($baris) }} Item</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Draft tersimpan</dt><dd class="font-semibold">{{ $draft ? $draft->nomor_po : '-' }}</dd></div>
                    </dl>
                </div>
            </div>

            @if ($draft)
                <form id="hapus-draft-form" method="POST" action="{{ route('e-po.distributor.hapus-epo', $draft) }}">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
            <form method="POST" action="{{ route('e-po.distributor.submit-epo') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="id_forecast" value="{{ $forecast->id_forecast }}">

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-x-auto">
                    <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap justify-between items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-boxes-stacked text-red-600 mr-2"></i>Pengajuan E-PO Baru
                            <span class="ml-2 text-[10px] bg-slate-100 px-2 py-0.5 rounded">{{ count($baris) }} SKU Aktif</span></h3>
                        <span class="text-[10px] text-slate-500"><i class="fa-solid fa-circle-info mr-1"></i>Bisa mengajukan beberapa E-PO dalam periode yang sama selama sisa kuota tersedia.</span>
                    </div>
                    <table class="w-full text-sm min-w-[900px]">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Produk & SKU</th>
                                <th class="px-4 py-3 text-right">Batas Kuota</th>
                                <th class="px-4 py-3 text-center">Pesanan (Kontainer)</th>
                                <th class="px-4 py-3 text-right">Harga / Karton</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="r in rows" :key="r.id">
                                <tr class="border-t border-slate-100" :class="over(r) ? 'bg-red-50 ring-1 ring-inset ring-red-300' : 'hover:bg-slate-50'">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-900" x-text="r.nama"></p>
                                        <p class="text-[10px] text-slate-500"><span class="font-mono text-red-600" x-text="r.kode"></span> - <span x-text="fmt(r.kpk)"></span> karton/kontainer</p>
                                    </td>
                                    <td class="px-4 py-3 text-right"><span x-text="fmt(r.kuota)"></span> ktr</td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="number" min="0" step="1" x-model.number="r.input" :name="`qty[${r.id}]`"
                                               class="w-24 text-center font-bold text-sm rounded py-1.5 border focus:ring-red-500 focus:border-red-500"
                                               :class="over(r) ? 'border-red-500 text-red-600 bg-white' : 'border-slate-200'">
                                        <p x-show="over(r)" style="display:none" class="mt-1 text-[10px] font-bold text-red-600">Lebih <span x-text="fmt(r.input - r.kuota)"></span> ktr dari kuota</p>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600" x-text="'Rp ' + fmt(r.harga)"></td>
                                    <td class="px-4 py-3 text-right font-semibold" x-text="'Rp ' + fmt(Number(r.input || 0) * r.kpk * r.harga)"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span x-show="!over(r)" class="text-[10px] font-bold bg-emerald-50 text-emerald-700 px-2 py-1 rounded">Sesuai sisa kuota</span>
                                        <span x-show="over(r)" style="display:none" class="text-[10px] font-bold bg-red-600 text-white px-2 py-1 rounded"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Melebihi Kuota</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-slate-50 font-bold">
                            <tr class="border-t border-slate-200">
                                <td class="px-4 py-3 text-xs uppercase">Total Akumulasi Pesanan E-PO</td>
                                <td class="px-4 py-3 text-right"><span x-text="fmt(plafon)"></span> ktr</td>
                                <td class="px-4 py-3 text-center" :class="overCount ? 'text-red-600' : ''"><span x-text="fmt(total)"></span> ktr</td>
                                <td></td>
                                <td class="px-4 py-3 text-right">Rp <span x-text="fmt(nilai)"></span></td>
                                <td class="px-4 py-3 text-center text-[10px]" :class="overCount ? 'text-red-600' : 'text-emerald-600'" x-text="overCount ? 'VALIDASI GAGAL' : 'VALIDASI LOLOS'"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="rounded-xl border p-4 flex gap-3" :class="overCount ? 'bg-red-50 border-red-300' : 'bg-emerald-50 border-emerald-200'">
                    <i class="mt-0.5" :class="overCount ? 'fa-solid fa-circle-exclamation text-red-600' : 'fa-solid fa-circle-check text-emerald-600'"></i>
                    <div class="text-xs">
                        <p class="font-bold" :class="overCount ? 'text-red-700' : 'text-emerald-700'"
                           x-text="overCount ? 'Validasi gagal - pesanan melebihi sisa kuota' : 'Validasi kuota terpenuhi'"></p>
                        <p class="text-slate-600 mt-0.5"
                           x-text="overCount ? (overCount + ' SKU melebihi kuota. Turunkan kuantitas agar E-PO dapat dikirim.') : 'Seluruh kuantitas berada dalam batas kuota. E-PO siap dikirim ke Logistik Pusat.'"></p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    @if ($draft)
                        <button type="submit" form="hapus-draft-form" onclick="return confirm('Hapus draft E-PO ini?')" class="px-4 py-2.5 text-sm font-semibold text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                            <i class="fa-regular fa-trash-can mr-1"></i> Hapus Draft
                        </button>
                    @endif
                    <button type="submit" name="aksi" value="draft" class="px-4 py-2.5 text-sm font-semibold border border-slate-300 rounded-lg hover:bg-slate-50">
                        <i class="fa-regular fa-floppy-disk mr-1"></i> Simpan Draft
                    </button>
                    <button type="submit" name="aksi" value="kirim" :disabled="overCount > 0 || total < 1"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-paper-plane mr-1"></i> Kirim E-PO
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
