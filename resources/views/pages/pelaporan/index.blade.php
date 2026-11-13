@extends('layouts.app')
@section('title', 'Pelaporan & Stok')
@section('module_title', 'Pelaporan & Stok')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Modul Pelaporan · MHD. Hasbi</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Ringkasan Penjualan & Stok</h1>
            <p class="mt-2 text-sm text-slate-500">Data pada halaman ini dibaca langsung dari database MySQL, bukan localStorage.</p>
        </div>
        <a href="{{ route('pelaporan.export', request()->query()) }}" class="rounded-lg bg-red-600 px-5 py-3 text-xs font-bold text-white hover:bg-red-700">Ekspor CSV</a>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-900">
        <strong>Integrasi lokal tanpa login.</strong> Pemilih aktor masih merupakan simulasi repo kelompok. Formulir halaman ini benar-benar menyimpan data ke MySQL. Akses produksi dinonaktifkan sampai autentikasi diterapkan. Modul E-PO dan Admin lain masih memakai data contoh masing-masing.
    </div>

    @if(session('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <strong>Data belum disimpan.</strong>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($role === 'distributor')
    <form method="POST" action="{{ route('pelaporan.context') }}" class="flex flex-wrap items-end gap-4 rounded-xl border border-slate-200 bg-white p-5">
        @csrf
        <div class="min-w-56 flex-1">
            <label for="context-distributor" class="mb-2 block text-xs font-bold text-slate-600">Perusahaan distributor pada sesi simulasi</label>
            <select id="context-distributor" name="id_distributor" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm">
                @forelse($distributors->where('status_aktif', true) as $company)
                    <option value="{{ $company->id_distributor }}" @selected($company->id_distributor == $activeDistributor)>{{ $company->nama }}</option>
                @empty<option value="">Belum ada master distributor di MySQL</option>@endforelse
            </select>
        </div>
        <button @disabled($distributors->isEmpty()) class="rounded-lg border border-slate-300 px-4 py-2.5 text-xs font-bold disabled:opacity-40">Pilih Perusahaan</button>
    </form>
    @endif

    <form method="GET" action="{{ route('pelaporan.index') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 md:grid-cols-2 xl:grid-cols-5">
        <div><label for="mulai" class="mb-2 block text-xs font-bold text-slate-600">Tanggal mulai</label><input id="mulai" name="mulai" type="date" value="{{ $start }}" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div>
        <div><label for="selesai" class="mb-2 block text-xs font-bold text-slate-600">Tanggal akhir</label><input id="selesai" name="selesai" type="date" value="{{ $end }}" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div>
        <div><label for="filter-distributor" class="mb-2 block text-xs font-bold text-slate-600">Distributor</label><select id="filter-distributor" name="id_distributor" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm">
            @if($role !== 'distributor')<option value="">Semua distributor</option>@endif
            @foreach($distributors as $company)
                @if($role !== 'distributor' || $company->id_distributor == $activeDistributor)
                <option value="{{ $company->id_distributor }}" @selected($company->id_distributor == $selectedDistributor)>{{ $company->nama }}</option>
                @endif
            @endforeach
        </select></div>
        <div><label for="fiskal" class="mb-2 block text-xs font-bold text-slate-600">Tahun fiskal</label><select id="fiskal" name="id_tahun_fiskal" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Sesuai rentang tanggal</option>@foreach($years as $year)<option value="{{ $year->id_tahun_fiskal }}" @selected(request('id_tahun_fiskal') == $year->id_tahun_fiskal)>{{ $year->tanggal_mulai->format('Y') }}/{{ $year->tanggal_selesai->format('Y') }}</option>@endforeach</select></div>
        <div class="flex items-end"><button class="w-full rounded-lg bg-red-600 px-4 py-3 text-xs font-bold text-white">Terapkan Filter</button></div>
    </form>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach([['SELL-IN', $rows->sum('sell_in'), 'Dikirim dari pabrik'], ['SELL-OUT', $rows->sum('sell_out'), 'Penjualan distributor'], ['PENERIMAAN BAIK', $rows->sum('penerimaan_baik'), 'Sudah diterima di gudang']] as [$label, $amount, $description])
        <article class="rounded-xl border border-slate-200 bg-white p-6"><p class="text-xs font-bold text-slate-500">{{ $label }}</p><p class="mt-4 text-3xl font-bold">{{ number_format($amount, 0, ',', '.') }} <small class="text-xs font-normal text-slate-500">karton</small></p><p class="mt-2 text-xs text-slate-500">{{ $description }}</p></article>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 p-5"><h2 class="text-sm font-bold">Ringkasan per Distributor & Produk</h2><p class="mt-1 text-xs text-slate-500">Stok terakhir tidak dijumlahkan antar-snapshot. Rekonsiliasi memerlukan saldo awal dan stok pada tanggal akhir filter.</p></div>
        <div class="overflow-x-auto"><table class="w-full whitespace-nowrap text-left text-xs">
            <thead class="bg-slate-50 uppercase text-slate-500"><tr>@foreach(['Distributor / SKU','Sell-In','Sell-Out','Fisik','Layak Jual','Tanggal Stok','Ekspektasi','Selisih','Status'] as $column)<th class="px-4 py-3">{{ $column }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse($rows as $row)
                <tr><td class="px-4 py-4"><strong>{{ $row['distributor'] }}</strong><div class="mt-1 text-slate-500">{{ $row['sku'] }} · {{ $row['produk'] }}</div></td>
                @foreach(['sell_in','sell_out','stok_fisik','stok_layak_jual'] as $key)<td class="px-4 py-4">{{ $row[$key] === null ? '—' : number_format($row[$key], 0, ',', '.') }}</td>@endforeach
                <td class="px-4 py-4">{{ $row['tanggal_stok'] ?? '—' }}</td>
                @foreach(['ekspektasi_layak_jual','selisih'] as $key)<td class="px-4 py-4">{{ $row[$key] === null ? '—' : number_format($row[$key], 0, ',', '.') }}</td>@endforeach
                <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 font-semibold">{{ $row['status'] }}</span></td></tr>
            @empty<tr><td colspan="9" class="px-5 py-14 text-center text-slate-500"><strong class="block text-base text-slate-700">Belum ada data transaksi di MySQL</strong><p class="mt-2">Lengkapi master distributor dan produk, lalu catat Sell-Out atau stok gudang.</p></td></tr>@endforelse
            </tbody>
        </table></div>
    </section>

    @if($role === 'distributor')
    <div class="grid gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ route('pelaporan.sell-out.store') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6">
            @csrf
            <h2 class="font-bold">Catat Sell-Out</h2><p class="text-xs text-slate-500">Setiap penyimpanan menambah satu transaksi penjualan.</p>
            <input type="hidden" name="id_distributor" value="{{ $activeDistributor }}">
            <label class="block text-xs font-semibold" for="sale-product">Produk / SKU</label><select id="sale-product" name="id_produk" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id_produk }}" @selected(old('id_produk') == $product->id_produk)>{{ $product->kode_sku }} — {{ $product->nama_produk }}</option>@endforeach</select>
            <div class="grid gap-4 md:grid-cols-2"><div><label class="mb-2 block text-xs font-semibold" for="sale-date">Tanggal penjualan</label><input id="sale-date" name="tanggal" type="date" required value="{{ old('tanggal', now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div><div><label class="mb-2 block text-xs font-semibold" for="sale-qty">Jumlah (karton)</label><input id="sale-qty" name="jumlah" type="number" required min="1" step="1" value="{{ old('jumlah') }}" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div></div>
            <label class="block text-xs font-semibold" for="sale-channel">Kanal</label><select id="sale-channel" name="kanal" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm">@foreach(['Retail','General Trade','Modern Trade'] as $channel)<option @selected(old('kanal') === $channel)>{{ $channel }}</option>@endforeach</select>
            <button @disabled(!$activeDistributor || $products->isEmpty()) class="w-full rounded-lg bg-red-600 px-4 py-3 text-xs font-bold text-white disabled:opacity-40">Simpan Sell-Out ke MySQL</button>
        </form>
        <form method="POST" action="{{ route('pelaporan.stok.store') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6">
            @csrf
            <h2 class="font-bold">Catat Stok Gudang</h2><p class="text-xs text-slate-500">Tanggal dan produk yang sama akan memperbarui snapshot yang ada.</p>
            <input type="hidden" name="id_distributor" value="{{ $activeDistributor }}">
            <label class="block text-xs font-semibold" for="stock-product">Produk / SKU</label><select id="stock-product" name="id_produk" required class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id_produk }}" @selected(old('id_produk') == $product->id_produk)>{{ $product->kode_sku }} — {{ $product->nama_produk }}</option>@endforeach</select>
            <label class="block text-xs font-semibold" for="stock-date">Tanggal batas pencatatan</label><input id="stock-date" name="periode" type="date" required value="{{ old('periode', now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm">
            <div class="grid gap-4 md:grid-cols-2"><div><label class="mb-2 block text-xs font-semibold" for="physical">Stok fisik (karton)</label><input id="physical" name="stok_fisik" type="number" required min="0" step="1" value="{{ old('stok_fisik') }}" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div><div><label class="mb-2 block text-xs font-semibold" for="saleable">Stok layak jual (karton)</label><input id="saleable" name="stok_layak_jual" type="number" required min="0" step="1" value="{{ old('stok_layak_jual') }}" class="w-full rounded-lg border border-slate-300 p-2.5 text-sm"></div></div>
            <p class="text-xs text-slate-500">0 ≤ stok layak jual ≤ stok fisik. Pengajuan klaim tidak otomatis mengurangi stok fisik.</p>
            <button @disabled(!$activeDistributor || $products->isEmpty()) class="w-full rounded-lg bg-slate-800 px-4 py-3 text-xs font-bold text-white disabled:opacity-40">Simpan Stok ke MySQL</button>
        </form>
    </div>
    @endif

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 p-5"><h2 class="text-sm font-bold">Transaksi Sell-Out Tersimpan</h2></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-xs"><thead class="bg-slate-50 uppercase text-slate-500"><tr>@foreach(['Tanggal','Distributor','Produk','Kanal','Karton'] as $column)<th class="px-5 py-3">{{ $column }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">@forelse($sales as $sale)<tr><td class="px-5 py-4">{{ $sale->tanggal->format('d M Y') }}</td><td class="px-5 py-4">{{ $sale->distributor->nama }}</td><td class="px-5 py-4">{{ $sale->produk->nama_produk }}</td><td class="px-5 py-4">{{ $sale->kanal }}</td><td class="px-5 py-4">{{ $sale->jumlah }}</td></tr>@empty<tr><td colspan="5" class="p-8 text-center text-slate-500">Belum ada transaksi pada rentang tanggal ini.</td></tr>@endforelse</tbody></table></div><div class="p-5">{{ $sales->withQueryString()->links() }}</div>
    </section>
</div>
@endsection
