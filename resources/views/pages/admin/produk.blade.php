@extends('layouts.admin')

@section('title', 'Data Produk (SKU)')

@section('content')
<div x-data="{
    showForm: new URLSearchParams(window.location.search).get('openForm') === '1',
    formData: {
        harga: 150000,
        isi: 2000
    }
}" class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">MASTER DATA</div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Data Produk (SKU)</h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Atur katalog produk master, struktur harga, dan formula konversi satuan isi kontainer.
            </p>
        </div>

        <button x-show="!showForm" 
                @click="showForm = true" 
                type="button"
                class="bg-[#0B132B] hover:bg-slate-900 text-white font-semibold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-plus text-[11px]"></i>
            <span>Tambah SKU</span>
        </button>
    </div>

    {{-- Form SKU Baru (Frame Dark Navy) --}}
    <div x-cloak x-show="showForm" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-2xl p-6 border-2 border-slate-900 shadow-sm">
        
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-900">Tambah Master Produk SKU Baru</h2>
            <button type="button" @click="showForm = false" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.produk.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        KODE SKU <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="kode_sku"
                           value="{{ old('kode_sku', 'SKU-' . str_pad($products->count() + 1, 3, '0', STR_PAD_LEFT)) }}"
                           required
                           placeholder="Contoh: SKU-001"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-800">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        NAMA PRODUK <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="nama_produk"
                           value="{{ old('nama_produk') }}"
                           placeholder="Contoh: Yoke Apple Cider 330ml"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        HARGA / KARTON (RP) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="harga"
                           value="{{ old('harga', 150000) }}"
                           min="1000"
                           step="1000"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-800">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        ISI / KONTAINER <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="karton_per_kontainer"
                           value="{{ old('karton_per_kontainer', 2000) }}"
                           min="1"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-800">
                </div>
            </div>

            <div class="pt-2 flex items-center space-x-2">
                <button type="submit" 
                        class="bg-[#0B132B] hover:bg-slate-900 text-white font-semibold px-6 py-2 rounded-xl text-xs shadow-xs transition">
                    Simpan SKU
                </button>
                <button type="button" 
                        @click="showForm = false"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-6 py-2 rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>

    {{-- Master Products Table Card --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-4 w-28">SKU</th>
                        <th class="pb-3 px-4">PRODUK</th>
                        <th class="pb-3 px-4">KATEGORI</th>
                        <th class="pb-3 px-4">HARGA / KARTON</th>
                        <th class="pb-3 px-4">ISI / KONTAINER</th>
                        <th class="pb-3 px-4">NILAI / KONTAINER</th>
                        <th class="pb-3 px-4 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $prod)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-slate-700">{{ $prod->kode_sku }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">{{ $prod->nama_produk }}</td>
                            <td class="py-3.5 px-4 text-slate-500">{{ $prod->kategori ?? 'Minuman' }}</td>
                            <td class="py-3.5 px-4 text-slate-700 font-medium">Rp {{ number_format($prod->harga, 0, ',', '.') }}</td>
                            <td class="py-3.5 px-4 text-slate-700 font-medium">{{ number_format($prod->karton_per_kontainer, 0, ',', '.') }} karton</td>
                            <td class="py-3.5 px-4 font-extrabold text-slate-900">Rp {{ number_format($prod->harga * $prod->karton_per_kontainer, 0, ',', '.') }}</td>
                            <td class="py-3.5 px-4 text-right">
                                <form action="{{ route('admin.produk.delete', $prod) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk SKU {{ $prod->kode_sku }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            title="Hapus master SKU"
                                            class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 text-white inline-flex items-center justify-center transition">
                                        <i class="fa-solid fa-trash text-[10px]"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Belum ada data produk SKU terdaftar di basis data. Klik tombol "Tambah SKU" di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Kalkulator Konversi Kontainer Card --}}
    @if($products->count() > 0)
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5"
         x-data="{
             selectedSkuIndex: 0,
             jumlahKontainer: 5,
             products: @js($products->map(fn($p) => [
                 'kode' => $p->kode_sku,
                 'nama' => $p->nama_produk,
                 'harga' => (int)$p->harga,
                 'isi' => $p->karton_per_kontainer,
             ])->values()),
             get selectedProduct() { return this.products[this.selectedSkuIndex] || this.products[0]; },
             get totalCartons() { return (parseInt(this.jumlahKontainer) || 0) * (this.selectedProduct?.isi || 0); },
             get totalValue() { return this.totalCartons * (this.selectedProduct?.harga || 0); },
             formatRp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); },
             formatNum(n) { return Number(n).toLocaleString('id-ID'); }
         }">
        <div>
            <h2 class="text-base font-bold text-slate-900 tracking-tight">Kalkulator konversi kontainer</h2>
            <p class="text-xs text-slate-500 mt-0.5">Karton = jumlah kontainer &times; isi karton per kontainer</p>
        </div>

        {{-- SKU Selection Pills --}}
        <div class="flex flex-wrap items-center gap-2">
            <template x-for="(p, idx) in products" :key="idx">
                <button type="button" @click="selectedSkuIndex = idx"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition"
                        :class="selectedSkuIndex === idx ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        x-text="p.kode + ' · ' + p.nama">
                </button>
            </template>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-stretch pt-2">
            <div class="bg-[#EBF3FB] rounded-2xl p-5 border border-blue-100 flex flex-col justify-center">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">JUMLAH KONTAINER</label>
                <input type="number" x-model.number="jumlahKontainer" min="1" max="500"
                       class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-lg font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0B132B]">
                <span class="text-[10px] text-slate-400 mt-1.5">
                    1 kontainer = <span x-text="formatNum(selectedProduct?.isi || 0)"></span> karton
                </span>
            </div>

            <div class="bg-[#0B132B] text-white rounded-2xl p-5 shadow-xs flex flex-col justify-center">
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total karton</div>
                <div class="mt-2 text-2xl font-black tracking-tight" x-text="formatNum(totalCartons) + ' karton'"></div>
                <div class="mt-1 text-[11px] text-slate-400" x-text="`${jumlahKontainer || 0} kontainer × ${formatNum(selectedProduct?.isi || 0)} karton`"></div>
            </div>

            <div class="bg-emerald-700 text-white rounded-2xl p-5 shadow-xs flex flex-col justify-center">
                <div class="text-[11px] font-semibold text-emerald-100 uppercase tracking-wider">Total nilai</div>
                <div class="mt-2 text-2xl font-black tracking-tight" x-text="formatRp(totalValue)"></div>
                <div class="mt-1 text-[11px] text-emerald-100" x-text="`${formatNum(totalCartons)} karton × ${formatRp(selectedProduct?.harga || 0)}`"></div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
