@extends('layouts.admin')

@section('title', 'Data Distributor')

@section('content')
<div x-data="{
    distributors: [],
    selectedWilayahFilter: 'Semua',
    showForm: false,
    formData: {
        nama: '',
        kontak: '',
        telepon: '',
        wilayah: 'Sumbar'
    },

    init() {
        this.distributors = adminStore.load('distributors');

        // Check if query param opened the form
        const params = new URLSearchParams(window.location.search);
        if (params.get('openForm')) {
            this.showForm = true;
        }
    },

    get filteredDistributors() {
        if (this.selectedWilayahFilter === 'Semua') {
            return this.distributors;
        }
        return this.distributors.filter(d => d.wilayah === this.selectedWilayahFilter);
    },

    openCreateForm() {
        this.formData = {
            nama: '',
            kontak: '',
            telepon: '',
            wilayah: 'Sumbar'
        };
        this.showForm = true;
        this.$nextTick(() => {
            document.getElementById('inputPerusahaan')?.focus();
        });
    },

    cancelForm() {
        this.showForm = false;
        this.formData = { nama: '', kontak: '', telepon: '', wilayah: 'Sumbar' };
    },

    saveDistributor() {
        if (!this.formData.nama.trim() || !this.formData.kontak.trim() || !this.formData.telepon.trim()) {
            showToast('Harap lengkapi semua data distributor.', 'error');
            return;
        }

        // Generate kode distributor berikutnya (DST-00X)
        const nextNumber = this.distributors.length + 1;
        const kode = 'DST-' + String(nextNumber).padStart(3, '0');

        const newDist = {
            id: Date.now(),
            kode: kode,
            nama: this.formData.nama,
            kontak: this.formData.kontak,
            telepon: this.formData.telepon,
            wilayah: this.formData.wilayah
        };

        this.distributors.push(newDist);
        adminStore.save('distributors', this.distributors);
        adminStore.logActivity('Tambah', 'Master Distributor', `Menambahkan ${newDist.nama} (${newDist.wilayah})`);
        showToast(`Distributor ${newDist.nama} berhasil didaftarkan.`, 'success');

        this.cancelForm();
    },

    deleteDistributor(dist) {
        if (confirm(`Apakah Anda yakin ingin menghapus data distributor ${dist.nama}?`)) {
            this.distributors = this.distributors.filter(d => d.id !== dist.id);
            adminStore.save('distributors', this.distributors);
            adminStore.logActivity('Hapus', 'Master Distributor', `Menghapus distributor ${dist.nama} (${dist.wilayah})`);
            showToast(`Distributor ${dist.nama} berhasil dihapus.`, 'success');
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
                Data Distributor
            </h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Kelola profil distributor, cakupan wilayah operasional (Sumbar dan Riau), dan kontak resmi perusahaan.
            </p>
        </div>

        <button x-show="!showForm" 
                @click="openCreateForm()" 
                type="button"
                class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-plus text-[11px]"></i>
            <span>Tambah distributor</span>
        </button>
    </div>

    <!-- Form Distributor Baru (Sesuai Mockup Layar 4) -->
    <div x-cloak x-show="showForm" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-2xl p-6 border-2 border-emerald-500/60 shadow-sm">
        
        <h2 class="text-sm font-bold text-slate-900 mb-4">
            Distributor baru
        </h2>

        <form @submit.prevent="saveDistributor()" class="space-y-4">
            <!-- 3 Fields Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        NAMA PERUSAHAAN
                    </label>
                    <input type="text" 
                           id="inputPerusahaan"
                           x-model="formData.nama" 
                           placeholder="PT / CV ..."
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        NAMA KONTAK RESMI
                    </label>
                    <input type="text" 
                           x-model="formData.kontak" 
                           placeholder="Nama penanggung jawab"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        TELEPON
                    </label>
                    <input type="text" 
                           x-model="formData.telepon" 
                           placeholder="0xxx-xxx-xxx"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <!-- Cakupan Wilayah Selector -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                    CAKUPAN WILAYAH
                </label>
                <div class="flex items-center space-x-2">
                    <button type="button" 
                            @click="formData.wilayah = 'Sumbar'"
                            class="px-4 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="formData.wilayah === 'Sumbar' ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-200/80 hover:bg-slate-300 text-slate-700'">
                        Sumbar
                    </button>
                    <button type="button" 
                            @click="formData.wilayah = 'Riau'"
                            class="px-4 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="formData.wilayah === 'Riau' ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-200/80 hover:bg-slate-300 text-slate-700'">
                        Riau
                    </button>
                </div>
            </div>

            <!-- Buttons -->
            <div class="pt-2 flex items-center space-x-2">
                <button type="submit" 
                        class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-6 py-2 rounded-xl text-xs shadow-xs transition">
                    Simpan
                </button>
                <button type="button" 
                        @click="cancelForm()"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-6 py-2 rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <!-- Table Card Container (Sesuai Mockup Layar 3 & 4) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">
        
        <!-- Filter Tabs Wilayah -->
        <div class="flex items-center space-x-2">
            <template x-for="tab in ['Semua', 'Sumbar', 'Riau']" :key="tab">
                <button type="button" 
                        @click="selectedWilayahFilter = tab"
                        class="px-4 py-1.5 rounded-xl text-xs font-semibold transition"
                        :class="selectedWilayahFilter === tab ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        x-text="tab">
                </button>
            </template>
        </div>

        <!-- Table Data Distributor -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-4 w-28">KODE</th>
                        <th class="pb-3 px-4">DISTRIBUTOR</th>
                        <th class="pb-3 px-4">WILAYAH</th>
                        <th class="pb-3 px-4">KONTAK RESMI</th>
                        <th class="pb-3 px-4 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="dist in filteredDistributors" :key="dist.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Kode -->
                            <td class="py-3.5 px-4 font-semibold text-slate-700" x-text="dist.kode"></td>

                            <!-- Nama Distributor -->
                            <td class="py-3.5 px-4 font-bold text-slate-900" x-text="dist.nama"></td>

                            <!-- Wilayah Badge -->
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block"
                                      :class="dist.wilayah === 'Sumbar' ? 'bg-emerald-600' : 'bg-red-600'"
                                      x-text="dist.wilayah">
                                </span>
                            </td>

                            <!-- Kontak Resmi (Nama + Telp) -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900" x-text="dist.kontak"></div>
                                <div class="text-[11px] text-slate-500" x-text="dist.telepon"></div>
                            </td>

                            <!-- Aksi (Red Trash Button) -->
                            <td class="py-3.5 px-4 text-right">
                                <button @click="deleteDistributor(dist)" 
                                        title="Hapus distributor"
                                        type="button" 
                                        class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 text-white inline-flex items-center justify-center transition">
                                    <i class="fa-solid fa-trash text-[10px]"></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredDistributors.length === 0">
                        <td colspan="5" class="py-8 text-center text-slate-400">
                            Tidak ada data distributor untuk wilayah yang dipilih.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

