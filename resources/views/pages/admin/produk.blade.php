@extends('layouts.admin')

@section('title', 'Data Produk (SKU)')

@section('content')
<div x-data="{
    products: [],
    showForm: false,
    
    // Form Baru
    formData: {
        kode: '',
        nama: '',
        harga: 150000,
        isi: 2000
    },

    // State Kalkulator Konversi
    selectedSkuId: 1,
    jumlahKontainer: 5,

    init() {
        this.products = adminStore.load('products');
        if (this.products.length > 0) {
            this.selectedSkuId = this.products[0].id;
        }

        // Check if query param opened the form
        const params = new URLSearchParams(window.location.search);
        if (params.get('openForm')) {
            this.showForm = true;
        }
    },

    get selectedProduct() {
        return this.products.find(p => p.id === this.selectedSkuId) || this.products[0] || null;
    },

    get calculatedTotalCartons() {
        if (!this.selectedProduct) return 0;
        const count = parseInt(this.jumlahKontainer) || 0;
        return count * (this.selectedProduct.isi || 2000);
    },

    get calculatedTotalValue() {
        if (!this.selectedProduct) return 0;
        return this.calculatedTotalCartons * (this.selectedProduct.harga || 0);
    },

    openCreateForm() {
        const nextNum = this.products.length + 1;
        this.formData = {
            kode: 'SKU-' + String(nextNum).padStart(3, '0'),
            nama: '',
            harga: 120000,
            isi: 2000
        };
        this.showForm = true;
        this.$nextTick(() => {
            document.getElementById('inputNamaProduk')?.focus();
        });
    },

    cancelForm() {
        this.showForm = false;
        this.formData = { kode: '', nama: '', harga: 0, isi: 2000 };
    },

    saveProduct() {
        if (!this.formData.nama.trim() || !this.formData.harga) {
            showToast('Harap lengkapi nama produk dan harga per karton.', 'error');
            return;
        }

        const harga = parseInt(this.formData.harga) || 0;
        const isi = parseInt(this.formData.isi) || 2000;
        const nilai = harga * isi;

        const newProd = {
            id: Date.now(),
            kode: this.formData.kode,
            nama: this.formData.nama,
            harga: harga,
            isi: isi,
            nilai: nilai
        };

        this.products.push(newProd);
        adminStore.save('products', this.products);
        adminStore.logActivity('Tambah', 'Master Produk (SKU)', `Menambahkan ${newProd.kode} - ${newProd.nama}`);
        showToast(`Produk SKU ${newProd.kode} berhasil ditambahkan.`, 'success');

        this.cancelForm();
    },

    deleteProduct(prod) {
        if (this.products.length <= 1) {
            showToast('Minimal harus ada satu master produk SKU.', 'error');
            return;
        }

        if (confirm(`Apakah Anda yakin ingin menghapus produk ${prod.kode} - ${prod.nama}?`)) {
            this.products = this.products.filter(p => p.id !== prod.id);
            if (this.selectedSkuId === prod.id && this.products.length > 0) {
                this.selectedSkuId = this.products[0].id;
            }
            adminStore.save('products', this.products);
            adminStore.logActivity('Hapus', 'Master Produk (SKU)', `Menghapus produk ${prod.kode} - ${prod.nama}`);
            showToast(`Produk SKU ${prod.kode} berhasil dihapus.`, 'success');
        }
    }
}" x-init="init()" class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">
                MASTER DATA
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Data Produk (SKU)
            </h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Atur katalog produk master, struktur harga, dan formula konversi satuan isi kontainer.
            </p>
        </div>

        <button x-show="!showForm" 
                @click="openCreateForm()" 
                type="button"
                class="bg-[#0B132B] hover:bg-slate-900 text-white font-semibold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-plus text-[11px]"></i>
            <span>Tambah SKU</span>
        </button>
    </div>

    <!-- Form SKU Baru (Collapsible) -->
    <div x-cloak x-show="showForm" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-2xl p-6 border-2 border-slate-900 shadow-sm">
        
        <h2 class="text-sm font-bold text-slate-900 mb-4">
            Tambah Master Produk SKU Baru
        </h2>

        <form @submit.prevent="saveProduct()" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        KODE SKU
                    </label>
                    <input type="text" 
                           x-model="formData.kode" 
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-800">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        NAMA PRODUK
                    </label>
                    <input type="text" 
                           id="inputNamaProduk"
                           x-model="formData.nama" 
                           placeholder="Contoh: FAN Orange 325ml"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        HARGA / KARTON (RP)
                    </label>
                    <input type="number" 
                           x-model="formData.harga" 
                           min="1000"
                           step="1000"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-800">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        ISI / KONTAINER
                    </label>
                    <input type="number" 
                           x-model="formData.isi" 
                           value="2000"
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
                        @click="cancelForm()"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-6 py-2 rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <!-- Master Products Table Card (Sesuai Mockup Layar 5) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-4 w-28">SKU</th>
                        <th class="pb-3 px-4">PRODUK</th>
                        <th class="pb-3 px-4">HARGA / KARTON</th>
                        <th class="pb-3 px-4">ISI / KONTAINER</th>
                        <th class="pb-3 px-4">NILAI / KONTAINER</th>
                        <th class="pb-3 px-4 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="prod in products" :key="prod.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- SKU -->
                            <td class="py-3.5 px-4 font-semibold text-slate-700" x-text="prod.kode"></td>

                            <!-- Nama Produk -->
                            <td class="py-3.5 px-4 font-bold text-slate-900" x-text="prod.nama"></td>

                            <!-- Harga / Karton -->
                            <td class="py-3.5 px-4 text-slate-700 font-medium" x-text="adminStore.formatRupiah(prod.harga)"></td>

                            <!-- Isi / Kontainer -->
                            <td class="py-3.5 px-4 text-slate-700 font-medium" x-text="adminStore.formatNumber(prod.isi) + ' karton'"></td>

                            <!-- Nilai / Kontainer -->
                            <td class="py-3.5 px-4 font-extrabold text-slate-900" x-text="adminStore.formatRupiah(prod.nilai)"></td>

                            <!-- Aksi (Red Trash Button) -->
                            <td class="py-3.5 px-4 text-right">
                                <button @click="deleteProduct(prod)" 
                                        title="Hapus master SKU"
                                        type="button" 
                                        class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 text-white inline-flex items-center justify-center transition">
                                    <i class="fa-solid fa-trash text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Kalkulator Konversi Kontainer Card (Sesuai Mockup Layar 5) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">
        <div>
            <h2 class="text-base font-bold text-slate-900 tracking-tight">
                Kalkulator konversi kontainer
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Karton = jumlah kontainer &times; isi karton per kontainer
            </p>
        </div>

        <!-- SKU Selection Pills -->
        <div class="flex flex-wrap items-center gap-2">
            <template x-for="p in products" :key="p.id">
                <button type="button" 
                        @click="selectedSkuId = p.id"
                        class="px-4 py-2 rounded-xl text-xs font-semibold transition"
                        :class="selectedSkuId === p.id ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        x-text="p.kode + ' · ' + p.nama">
                </button>
            </template>
        </div>

        <!-- Kalkulasi Inputs & Result Cards (Grid 3 Kolom) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-stretch pt-2">
            
            <!-- JUMLAH KONTAINER Input -->
            <div class="bg-[#EBF3FB] rounded-2xl p-5 border border-blue-100 flex flex-col justify-center">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                    JUMLAH KONTAINER
                </label>
                <input type="number" 
                       x-model.number="jumlahKontainer" 
                       min="1"
                       max="500"
                       class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-lg font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#0B132B]">
                <span class="text-[10px] text-slate-400 mt-1.5">
                    1 kontainer = <span x-text="adminStore.formatNumber(selectedProduct?.isi || 2000)"></span> karton
                </span>
            </div>

            <!-- Total karton (Dark Navy Card) -->
            <div class="bg-[#0B132B] text-white rounded-2xl p-5 shadow-xs flex flex-col justify-center">
                <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    Total karton
                </div>
                <div class="mt-2 text-2xl font-black tracking-tight" x-text="adminStore.formatNumber(calculatedTotalCartons) + ' karton'">
                    10.000 karton
                </div>
                <div class="mt-1 text-[11px] text-slate-400" x-text="`${jumlahKontainer || 0} kontainer × ${adminStore.formatNumber(selectedProduct?.isi || 2000)} karton`">
                </div>
            </div>

            <!-- Total nilai (Green Card) -->
            <div class="bg-emerald-700 text-white rounded-2xl p-5 shadow-xs flex flex-col justify-center">
                <div class="text-[11px] font-semibold text-emerald-100 uppercase tracking-wider">
                    Total nilai
                </div>
                <div class="mt-2 text-2xl font-black tracking-tight" x-text="adminStore.formatRupiah(calculatedTotalValue)">
                    Rp 1.850.000.000
                </div>
                <div class="mt-1 text-[11px] text-emerald-100" x-text="`${adminStore.formatNumber(calculatedTotalCartons)} karton × ${adminStore.formatRupiah(selectedProduct?.harga || 0)}`">
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

