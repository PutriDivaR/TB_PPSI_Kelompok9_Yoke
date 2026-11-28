@extends('layouts.app')

@section('title', 'Input E-PO')

@section('content')
<div class="space-y-6">

    {{-- HEADER + STEPPER --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-lg"><i class="fa-solid fa-store"></i></div>
            <div>
                <p class="text-[10px] font-bold tracking-wider uppercase text-slate-500">Distributor Regional &middot; DST-{{ str_pad($distributor->id_distributor, 3, '0', STR_PAD_LEFT) }}</p>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">{{ $distributor->nama }} ({{ $distributor->wilayah->nama_wilayah }})</h1>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4">
            @php $langkah = ['Usulan Forecast', 'Input E-PO', 'Dokumen PO Selesai']; $aktif = 2; @endphp
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
    </div>

    @include('layouts.partials.flash')

    @if (! $forecast)
        <div class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-500"><i class="fa-solid fa-hourglass-half"></i></div>
            <h2 class="mt-4 text-lg font-bold text-slate-900">Belum ada forecast dengan sisa kuota</h2>
            <p class="mt-1 text-sm text-slate-500">E-PO dapat dibuat setelah Logistik Pusat menetapkan kuota dan masih ada SKU yang punya sisa kuota.</p>
            <a href="{{ route('e-po.distributor.hitung-forecast') }}" class="mt-5 inline-block rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold hover:bg-slate-50"><i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Hitung Forecast</a>
        </div>
    @else
    <div class="space-y-6" x-data="epoInput(@js($katalog), @js($keranjang), {{ $minimumOrder }}, {{ $kartonPerKontainer }}, @js($errors->messages()))">

        @if ($daftarForecastEpo->count() > 1)
            <form method="GET" action="{{ route('e-po.distributor.input-epo') }}" class="flex flex-wrap items-end justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div>
                    <label for="forecast-epo" class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Periode Forecast untuk E-PO</label>
                    <p class="mt-1 text-[11px] text-slate-500">E-PO dapat dibuat untuk target kedatangan dalam window lead time 2-3 bulan selama kuota masih tersedia.</p>
                </div>
                <select id="forecast-epo" name="forecast" onchange="this.form.submit()" class="min-w-60 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach ($daftarForecastEpo as $forecastPilihan)
                        <option value="{{ $forecastPilihan->id_forecast }}" @selected($forecastPilihan->id_forecast === $forecast->id_forecast)>
                            {{ $tanggalEpo($forecastPilihan->periode, 'F Y') }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif

        {{-- RINGKASAN KUOTA & VALIDASI --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-xl border bg-white p-5 shadow-sm lg:col-span-2" :class="(overCount || kurangMin) ? 'border-red-300' : 'border-slate-200'">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-gauge-high mr-2 text-red-600"></i>Plafon Kuota Forecast {{ $tanggalEpo($forecast->periode, 'F Y') }}</h3>
                    <span class="rounded px-2 py-1 text-[10px] font-bold" :class="overCount > 0 ? 'bg-red-50 text-red-700' : (kurangMin ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700')"
                          x-text="overCount > 0 ? 'Peringatan: Melebihi Sisa Kuota' : (kurangMin ? 'Belum Memenuhi Minimum Order' : 'Pemesanan Sesuai Kuota')"></span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-[10px] font-bold uppercase text-slate-500">Sisa Kuota Aktif</p>
                        <p class="text-2xl font-extrabold" x-text="fmt(sisaTotal)"></p>
                        <p class="text-[10px] text-slate-500">karton, semua SKU</p>
                    </div>
                    <div class="rounded-lg p-3" :class="overCount > 0 ? 'bg-red-50' : 'bg-slate-50'">
                        <p class="text-[10px] font-bold uppercase" :class="overCount > 0 ? 'text-red-600' : 'text-slate-500'">Total Pesanan</p>
                        <p class="text-2xl font-extrabold" :class="overCount > 0 ? 'text-red-600' : ''" x-text="fmt(total)"></p>
                        <p class="text-[10px] text-slate-500">karton</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-[10px] font-bold uppercase text-slate-500">Estimasi Kontainer</p>
                        <p class="text-2xl font-extrabold" x-text="fmt2(kontainer)"></p>
                        <p class="text-[10px] text-slate-500">&asymp; <span x-text="Math.ceil(kontainer)"></span> kontainer (total / <span x-text="fmt(kpk)"></span>)</p>
                    </div>
                    <div class="rounded-lg p-3" :class="kurangMin ? 'bg-amber-50' : 'bg-emerald-50'">
                        <p class="text-[10px] font-bold uppercase text-slate-500">Minimum Order</p>
                        <p class="text-2xl font-extrabold" :class="kurangMin ? 'text-amber-600' : 'text-emerald-600'" x-text="fmt(minimum)"></p>
                        <p class="text-[10px]" :class="kurangMin ? 'text-amber-700' : 'text-emerald-600'" x-text="kurangMin ? ('kurang ' + fmt(minimum - total) + ' karton') : 'terpenuhi'"></p>
                    </div>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full transition-all" :class="overCount > 0 ? 'bg-red-500' : 'bg-emerald-500'" :style="`width:${pct}%`"></div></div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Ringkasan Nilai E-PO</p>
                <p class="mt-2 text-3xl font-extrabold text-slate-900">Rp <span x-text="fmt(nilai)"></span></p>
                <dl class="mt-4 space-y-2 text-xs">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Distributor</dt><dd class="text-right font-semibold">{{ $distributor->nama }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Jumlah SKU di daftar</dt><dd class="font-semibold"><span x-text="items.length"></span> item</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Draft tersimpan</dt><dd class="font-semibold">{{ $draft ? $draft->nomor_po : '-' }}</dd></div>
                </dl>
            </div>
        </div>

        <form method="POST" action="{{ route('e-po.distributor.submit-epo') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="id_forecast" value="{{ $forecast->id_forecast }}">

            {{-- CARI SKU --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-magnifying-glass-plus mr-2 text-red-600"></i>Cari &amp; Tambah Produk ke Daftar Pesanan</h3>
                        <p class="text-[11px] text-slate-500">Hanya SKU yang masih punya sisa kuota &gt; 0 yang dapat dipilih.</p>
                    </div>
                    <button type="button" @click="modal = true" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-bold hover:bg-slate-50"><i class="fa-solid fa-book-open mr-1 text-red-600"></i> Katalog Produk</button>
                </div>
                <div class="relative mt-4" @click.outside="buka = false">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-xs text-slate-400"></i>
                    <input type="text" x-model="cari" @focus="buka = true" @input="buka = true" placeholder="Ketik kode SKU / nama produk..." autocomplete="off"
                           class="w-full rounded-lg border border-slate-300 py-2 pl-8 pr-3 text-sm focus:border-red-500 focus:ring-red-500">
                    <div x-show="buka" style="display:none" class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                        <template x-for="p in hasilCari" :key="p.id">
                            <button type="button" @click="tambah(p)" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-xs hover:bg-red-50">
                                <span>
                                    <span class="font-mono font-bold text-red-600" x-text="p.kode"></span>
                                    <span class="font-semibold text-slate-800" x-text="p.nama"></span>
                                    <span class="block text-[10px] text-slate-500"><span x-text="p.kategori || '-'"></span> &middot; Rp <span x-text="fmt(p.harga)"></span>/karton</span>
                                </span>
                                <span class="shrink-0 rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">sisa <span x-text="fmt(p.sisa)"></span></span>
                            </button>
                        </template>
                        <p x-show="hasilCari.length === 0" class="px-3 py-3 text-xs text-slate-500">Tidak ada produk tersedia yang cocok (atau semuanya sudah ada di daftar).</p>
                    </div>
                </div>
            </div>

            {{-- KERANJANG --}}
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900"><i class="fa-solid fa-cart-shopping mr-2 text-red-600"></i>Daftar Pesanan <span class="ml-2 rounded bg-slate-100 px-2 py-0.5 text-[10px]"><span x-text="items.length"></span> SKU</span></h3>
                    <span class="text-[10px] text-slate-500"><i class="fa-solid fa-lock mr-1"></i>Tombol kirim terkunci otomatis bila ada qty melebihi sisa kuota SKU</span>
                </div>
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Produk &amp; SKU</th>
                            <th class="px-4 py-3 text-right">Sisa Kuota</th>
                            <th class="px-4 py-3 text-center">Pesanan (Karton)</th>
                            <th class="px-4 py-3 text-right">Harga / Karton</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="it in items" :key="it.id">
                            <tr class="border-t border-slate-100" :class="statusBaris(it) !== 'ok' ? 'bg-red-50 ring-1 ring-inset ring-red-300' : 'hover:bg-slate-50'">
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900" x-text="produk(it).nama"></p>
                                    <p class="text-[10px] text-slate-500"><span class="font-mono text-red-600" x-text="produk(it).kode"></span> &middot; <span x-text="produk(it).kategori || '-'"></span></p>
                                </td>
                                <td class="px-4 py-3 text-right"><span x-text="fmt(produk(it).sisa)"></span> krt</td>
                                <td class="px-4 py-3 text-center">
                                    <input type="number" min="1" step="1" x-model.number="it.qty" :name="`qty[${it.id}]`"
                                           class="w-28 rounded border py-1.5 text-center text-sm font-bold focus:ring-red-500"
                                           :class="statusBaris(it) !== 'ok' ? 'border-red-500 text-red-600 focus:border-red-500' : 'border-slate-200 focus:border-red-500'">
                                    <p x-show="statusBaris(it) === 'over'" style="display:none" class="mt-1 text-[10px] font-bold text-red-600">Lebih <span x-text="fmt(it.qty - produk(it).sisa)"></span> krt dari sisa kuota</p>
                                    <p x-show="statusBaris(it) === 'kosong'" style="display:none" class="mt-1 text-[10px] font-bold text-red-600">Isi minimal 1 karton</p>
                                    <p x-show="galat['qty.' + it.id]" style="display:none" class="mt-1 max-w-[16rem] text-[10px] font-bold text-red-600" x-text="(galat['qty.' + it.id] || [])[0]"></p>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-600" x-text="'Rp ' + fmt(produk(it).harga)"></td>
                                <td class="px-4 py-3 text-right font-semibold" x-text="'Rp ' + fmt(Number(it.qty || 0) * produk(it).harga)"></td>
                                <td class="px-4 py-3 text-center">
                                    <span x-show="statusBaris(it) === 'ok'" class="rounded bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700">&#9679; Sesuai Kuota</span>
                                    <span x-show="statusBaris(it) !== 'ok'" style="display:none" class="rounded bg-red-600 px-2 py-1 text-[10px] font-bold text-white"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Terkunci</span>
                                </td>
                                <td class="px-4 py-3 text-center"><button type="button" @click="hapus(it)" class="text-slate-400 hover:text-red-600" title="Hapus dari daftar"><i class="fa-regular fa-trash-can"></i></button></td>
                            </tr>
                        </template>
                        <tr x-show="items.length === 0">
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">
                                Daftar pesanan masih kosong. Cari produk di atas atau buka <button type="button" @click="modal = true" class="font-bold text-red-600 hover:underline">Katalog Produk</button>.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold">
                        <tr class="border-t border-slate-200">
                            <td class="px-4 py-3 text-xs uppercase">Total Akumulasi Pesanan E-PO</td>
                            <td class="px-4 py-3 text-right"><span x-text="fmt(sisaTotal)"></span> krt</td>
                            <td class="px-4 py-3 text-center" :class="overCount > 0 ? 'text-red-600' : ''"><span x-text="fmt(total)"></span> krt</td>
                            <td class="px-4 py-3 text-right text-[10px] font-normal text-slate-500">&asymp; <span x-text="fmt2(kontainer)"></span> kontainer</td>
                            <td class="px-4 py-3 text-right">Rp <span x-text="fmt(nilai)"></span></td>
                            <td colspan="2" class="px-4 py-3 text-center text-[10px]" :class="bisaKirim ? 'text-emerald-600' : 'text-red-600'" x-text="bisaKirim ? 'VALIDASI LOLOS' : 'BELUM VALID'"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- BANNER VALIDASI --}}
            <div class="flex gap-3 rounded-xl border p-4" :class="bisaKirim ? 'border-emerald-200 bg-emerald-50' : 'border-red-300 bg-red-50'">
                <i class="mt-0.5" :class="bisaKirim ? 'fa-solid fa-circle-check text-emerald-600' : 'fa-solid fa-circle-exclamation text-red-600'"></i>
                <div class="text-xs">
                    <p class="font-bold" :class="bisaKirim ? 'text-emerald-700' : 'text-red-700'" x-text="bisaKirim ? 'Validasi Kuota & Minimum Order Terpenuhi' : 'E-PO Belum Dapat Dikirim'"></p>
                    <ul class="mt-0.5 list-disc space-y-0.5 pl-4 text-slate-600">
                        <li x-show="bisaKirim">Seluruh kuantitas berada dalam sisa kuota dan total memenuhi minimum order. E-PO siap dikirim ke Logistik Pusat.</li>
                        <li x-show="items.length === 0">Tambahkan minimal satu produk ke daftar pesanan.</li>
                        <li x-show="overCount > 0"><span x-text="overCount"></span> SKU melebihi sisa kuota. Turunkan kuantitas (auto-lock aktif).</li>
                        <li x-show="items.length > 0 && kurangMin">Minimum order <span x-text="fmt(minimum)"></span> karton (1 kontainer). Total Anda baru <span x-text="fmt(total)"></span> karton.</li>
                    </ul>
                </div>
            </div>

            {{-- BATAS WAKTU PENGAJUAN & ESTIMASI TIBA --}}
            <div class="grid gap-3 rounded-xl border p-4 {{ $jadwal['is_lewat_cutoff'] ? 'border-amber-300 bg-amber-50' : 'border-blue-200 bg-blue-50' }}">
                @if ($jadwal['is_lewat_cutoff'])
                    <div class="flex gap-3 text-xs text-amber-800">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                        <p><b>Pengajuan melewati batas tanggal {{ str_pad($cutoffDay, 2, '0', STR_PAD_LEFT) }}.</b> E-PO akan otomatis masuk jadwal keberangkatan gelombang bulan berikutnya.</p>
                    </div>
                @else
                    <div class="flex gap-3 text-xs text-blue-800">
                        <i class="fa-solid fa-circle-check mt-0.5"></i>
                        <p><b>Pengajuan masih dalam cutoff gelombang tanggal {{ str_pad($cutoffDay, 2, '0', STR_PAD_LEFT) }}.</b> E-PO masuk jadwal keberangkatan bulan berjalan.</p>
                    </div>
                @endif
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-white/70 bg-white/70 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Target Gelombang Keberangkatan</p>
                        <p class="mt-1 text-sm font-extrabold text-slate-900">{{ $tanggalEpo($jadwal['gelombang_kirim'], 'd F Y') }}</p>
                    </div>
                    <div class="rounded-lg border border-white/70 bg-white/70 p-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Perkiraan Barang Tiba di Gudang (ETA)</p>
                        <p class="mt-1 text-sm font-extrabold text-slate-900">
                            {{ $tanggalEpo($jadwal['eta_min'], 'd F Y') }} - {{ $tanggalEpo($jadwal['eta_max'], 'd F Y') }}
                        </p>
                        <p class="mt-1 text-[10px] text-slate-500">Estimasi lead time 60-75 hari setelah gelombang pengiriman</p>
                    </div>
                </div>
                @if ($jadwal['keterangan_target'])
                    <div class="rounded-lg border px-3 py-2 text-xs {{ $jadwal['target_sesuai'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700' }}">
                        <i class="fa-solid {{ $jadwal['target_sesuai'] ? 'fa-circle-check' : 'fa-triangle-exclamation' }} mr-1"></i>
                        {{ $jadwal['keterangan_target'] }}
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <button type="submit" name="aksi" value="draft" :disabled="overCount > 0 || adaKosong"
                        class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"><i class="fa-regular fa-floppy-disk mr-1"></i> Simpan Draft</button>
                <button type="submit" name="aksi" value="kirim" :disabled="!bisaKirim"
                        class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500"><i class="fa-solid fa-paper-plane mr-1"></i> Kirim E-PO</button>
            </div>
        </form>

        @if ($draft)
            <form method="POST" action="{{ route('e-po.distributor.hapus-epo', $draft) }}" class="flex justify-end" onsubmit="return confirm('Hapus draft E-PO {{ $draft->nomor_po }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="rounded-lg border border-red-300 px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-50"><i class="fa-regular fa-trash-can mr-1"></i> Hapus Draft E-PO</button>
            </form>
        @endif

        {{-- MODAL KATALOG --}}
        <div x-show="modal" style="display:none" class="print-area fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-900/60 p-4 sm:p-8" @keydown.escape.window="modal = false">
            <div class="katalog-sheet relative w-full max-w-4xl rounded-lg bg-white shadow-2xl" @click.outside="modal = false">
                <div class="print-hidden flex items-center justify-between rounded-t-lg border-b border-slate-200 bg-slate-100 px-5 py-2.5">
                    <p class="text-xs font-bold text-slate-600"><i class="fa-regular fa-file-lines mr-2 text-red-600"></i>Katalog_Produk_YFI.pdf</p>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold hover:bg-slate-50"><i class="fa-solid fa-print mr-1"></i> Cetak</button>
                        <button type="button" @click="modal = false" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-900">Tutup</button>
                    </div>
                </div>
                <div class="p-8">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-slate-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded bg-red-600 text-2xl font-extrabold text-white">Y</div>
                            <div>
                                <p class="text-base font-extrabold uppercase">{{ $pabrik['nama'] }}</p>
                                <p class="max-w-sm text-[10px] text-slate-500">{{ $pabrik['alamat'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-extrabold tracking-wider text-red-600">KATALOG PRODUK</p>
                            <p class="text-[10px] text-slate-500">Forecast periode {{ $tanggalEpo($forecast->periode, 'F Y') }}</p>
                            <p class="text-[10px] text-slate-500">Distributor: {{ $distributor->nama }}</p>
                        </div>
                    </div>
                    <table class="mt-5 w-full border border-slate-300 text-xs">
                        <thead class="bg-slate-100 text-[10px] uppercase tracking-wider">
                            <tr>
                                <th class="px-3 py-2 text-left">No</th>
                                <th class="px-3 py-2 text-left">Kode SKU</th>
                                <th class="px-3 py-2 text-left">Nama Produk</th>
                                <th class="px-3 py-2 text-left">Kategori</th>
                                <th class="px-3 py-2 text-right">Harga / Karton</th>
                                <th class="px-3 py-2 text-right">Kuota</th>
                                <th class="px-3 py-2 text-right">Sisa Kuota</th>
                                <th class="print-hidden px-3 py-2 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <template x-for="(p, i) in katalog" :key="p.id">
                                <tr :class="p.sisa > 0 ? '' : 'text-slate-400'">
                                    <td class="px-3 py-2" x-text="i + 1"></td>
                                    <td class="px-3 py-2 font-mono font-bold" :class="p.sisa > 0 ? 'text-red-600' : ''" x-text="p.kode"></td>
                                    <td class="px-3 py-2 font-semibold" x-text="p.nama"></td>
                                    <td class="px-3 py-2" x-text="p.kategori || '-'"></td>
                                    <td class="px-3 py-2 text-right">Rp <span x-text="fmt(p.harga)"></span></td>
                                    <td class="px-3 py-2 text-right" x-text="p.kuota > 0 ? fmt(p.kuota) : '-'"></td>
                                    <td class="px-3 py-2 text-right font-semibold" x-text="p.sisa > 0 ? fmt(p.sisa) : '-'"></td>
                                    <td class="print-hidden px-3 py-2 text-center">
                                        <span x-show="p.sisa <= 0" class="text-[10px]">tidak ada kuota</span>
                                        <span x-show="p.sisa > 0 && sudahAda(p)" style="display:none" class="text-[10px] font-bold text-emerald-600">&#10003; di daftar</span>
                                        <button type="button" x-show="p.sisa > 0 && !sudahAda(p)" style="display:none" @click="tambah(p)" class="rounded bg-red-600 px-2.5 py-1 text-[10px] font-bold text-white hover:bg-red-700">+ Tambah</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p class="mt-3 text-[10px] text-slate-500">
                        Harga dalam rupiah per karton. Minimum order <span x-text="fmt(minimum)"></span> karton (1 kontainer = <span x-text="fmt(kpk)"></span> karton). Hanya SKU dengan sisa kuota yang dapat dipesan.
                    </p>
                    <p class="mt-4 border-t border-slate-200 pt-2 text-[10px] text-slate-400">Dokumen dihasilkan otomatis oleh E-PO &amp; Forecasting Portal Waypoint &middot; {{ $tanggalEpo(now(), 'd/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
    @media print {
        @page { size: A4; margin: 10mm; }
        body * { visibility: hidden; }
        .katalog-sheet, .katalog-sheet * { visibility: visible; }
        .print-area { position: absolute !important; inset: 0 !important; background: #fff !important; padding: 0 !important; overflow: visible !important; }
        .katalog-sheet { box-shadow: none !important; max-width: none !important; }
        .print-hidden { display: none !important; }
    }
</style>

@push('scripts')
<script>
    function epoInput(katalog, keranjang, minimum, kpk, galat) {
        return {
            katalog, items: keranjang, minimum, kpk, galat: galat || {}, modal: false, buka: false, cari: '',
            fmt(n) { return new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0)); },
            fmt2(n) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(n) || 0); },
            produk(it) { return this.katalog.find(p => p.id === it.id) || { nama: '-', kode: '-', kategori: '', harga: 0, sisa: 0 }; },
            sudahAda(p) { return this.items.some(it => it.id === p.id); },
            tambah(p) { if (!this.sudahAda(p) && p.sisa > 0) { this.items.push({ id: p.id, qty: Math.min(p.sisa, 500) }); } this.cari = ''; this.buka = false; },
            hapus(it) { this.items = this.items.filter(x => x.id !== it.id); delete this.galat['qty.' + it.id]; },
            statusBaris(it) { const q = Number(it.qty || 0); if (q < 1) return 'kosong'; return q > this.produk(it).sisa ? 'over' : 'ok'; },
            get hasilCari() { const k = this.cari.trim().toLowerCase(); return this.katalog.filter(p => p.sisa > 0 && !this.sudahAda(p) && (k === '' || (p.kode + ' ' + p.nama + ' ' + (p.kategori || '')).toLowerCase().includes(k))); },
            get sisaTotal() { return this.katalog.reduce((a, p) => a + p.sisa, 0); },
            get total() { return this.items.reduce((a, it) => a + Number(it.qty || 0), 0); },
            get nilai() { return this.items.reduce((a, it) => a + Number(it.qty || 0) * this.produk(it).harga, 0); },
            get kontainer() { return this.total / this.kpk; },
            get overCount() { return this.items.filter(it => this.statusBaris(it) === 'over').length; },
            get adaKosong() { return this.items.some(it => this.statusBaris(it) === 'kosong'); },
            get kurangMin() { return this.items.length > 0 && this.total < this.minimum; },
            get bisaKirim() { return this.items.length > 0 && this.overCount === 0 && !this.adaKosong && this.total >= this.minimum; },
            get pct() { return this.sisaTotal > 0 ? Math.min(100, Math.round(this.total / this.sisaTotal * 100)) : 0; },
        };
    }
</script>
@endpush
@endsection