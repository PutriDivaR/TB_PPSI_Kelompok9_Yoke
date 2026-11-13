@extends('layouts.admin')

@section('title', 'Periode Tahun Fiskal')

@section('content')
<div x-data="{
    fiscalPeriods: [],
    showForm: false,
    inputTahunMulai: 2028,
    months: ['Okt', 'Nov', 'Des', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep'],
    selectedMonth: 'Okt',

    init() {
        this.fiscalPeriods = adminStore.load('fiscalPeriods');

        // Check if query param opened the form
        const params = new URLSearchParams(window.location.search);
        if (params.get('openForm')) {
            this.showForm = true;
        }
    },

    get activePeriod() {
        return this.fiscalPeriods.find(p => p.status === 'Aktif') || this.fiscalPeriods[0];
    },

    get calculatedRentang() {
        const y = parseInt(this.inputTahunMulai) || 2028;
        return `1 Oktober ${y} – 30 September ${y + 1} (${y}/${y + 1})`;
    },

    openCreateForm() {
        this.inputTahunMulai = 2028;
        this.showForm = true;
        this.$nextTick(() => {
            document.getElementById('inputTahun')?.focus();
        });
    },

    cancelForm() {
        this.showForm = false;
        this.inputTahunMulai = 2028;
    },

    savePeriod() {
        const y = parseInt(this.inputTahunMulai);
        if (!y || y < 2020 || y > 2100) {
            showToast('Harap masukkan tahun mulai yang valid (misal: 2028).', 'error');
            return;
        }

        const tahunLabel = `${y}/${y + 1}`;
        const rentangLabel = `1 Oktober ${y} – 30 September ${y + 1}`;

        // Cek duplikasi
        if (this.fiscalPeriods.some(p => p.tahun === tahunLabel)) {
            showToast(`Periode tahun fiskal ${tahunLabel} sudah terdaftar.`, 'error');
            return;
        }

        const newPeriod = {
            id: Date.now(),
            tahun: tahunLabel,
            rentang: rentangLabel,
            status: 'Draf'
        };

        this.fiscalPeriods.unshift(newPeriod);
        adminStore.save('fiscalPeriods', this.fiscalPeriods);
        adminStore.logActivity('Tambah', 'Tahun Fiskal', `Membuat draf periode tahun fiskal ${tahunLabel}`);
        showToast(`Draf periode tahun fiskal ${tahunLabel} berhasil disimpan.`, 'success');

        this.cancelForm();
    },

    activatePeriod(period) {
        if (confirm(`Apakah Anda yakin ingin mengaktifkan periode tahun fiskal ${period.tahun}? Periode berjalan saat ini akan dialihkan menjadi selesai.`)) {
            this.fiscalPeriods.forEach(p => {
                if (p.status === 'Aktif') {
                    p.status = 'Selesai';
                }
            });

            period.status = 'Aktif';
            period.aktifSejak = '01 Okt ' + period.tahun.split('/')[0];
            period.persentase = 6;
            period.hariKe = 23;
            period.totalHari = 365;
            period.bulanAktif = 'Okt';

            adminStore.save('fiscalPeriods', this.fiscalPeriods);
            adminStore.logActivity('Ubah', 'Tahun Fiskal', `Mengaktifkan periode tahun fiskal ${period.tahun}`);
            showToast(`Periode tahun fiskal ${period.tahun} sekarang aktif.`, 'success');
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
                Periode Tahun Fiskal
            </h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Rentang tahun fiskal 1 Oktober &ndash; 30 September menjadi acuan perhitungan kuota dan agregasi laporan.
            </p>
        </div>

        <button x-show="!showForm" 
                @click="openCreateForm()" 
                type="button"
                class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-plus text-[11px]"></i>
            <span>Buat periode</span>
        </button>
    </div>

    <!-- Top Card: Periode Berjalan (Sesuai Mockup Layar 6, 7 & 8) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                    PERIODE BERJALAN
                </div>
                <div class="text-3xl font-black text-slate-900 tracking-tight mt-1" x-text="activePeriod?.tahun || '2026/2027'">
                    2026/2027
                </div>
                <div class="text-xs text-slate-500 mt-0.5" x-text="activePeriod?.rentang || '1 Oktober 2026 – 30 September 2027'">
                    1 Oktober 2026 – 30 September 2027
                </div>
            </div>

            <div class="sm:text-right">
                <div class="text-3xl font-black text-slate-900">
                    6%
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                    hari ke-23 dari 365
                </div>
            </div>
        </div>

        <!-- Progress Bar (Thin grey bar with red segment) -->
        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
            <div class="bg-red-600 h-2 rounded-full transition-all duration-500" style="width: 6%;"></div>
        </div>

        <!-- 12 Bulan Fiskal Pills (Okt - Sep) -->
        <div class="flex flex-wrap items-center gap-2 pt-1">
            <template x-for="m in months" :key="m">
                <button type="button" 
                        @click="selectedMonth = m"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition"
                        :class="selectedMonth === m ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        x-text="m">
                </button>
            </template>
        </div>
    </div>

    <!-- Form Periode Baru (Border Merah Sesuai Mockup Layar 6 & 8) -->
    <div x-cloak x-show="showForm" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-2xl p-6 border-2 border-red-500 shadow-sm">
        
        <h2 class="text-sm font-bold text-slate-900 mb-4">
            Periode baru
        </h2>

        <form @submit.prevent="savePeriod()" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <!-- TAHUN MULAI -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        TAHUN MULAI
                    </label>
                    <input type="number" 
                           id="inputTahun"
                           x-model="inputTahunMulai" 
                           min="2020"
                           max="2050"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <!-- Rentang Otomatis (Preview Box) -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Rentang otomatis
                    </label>
                    <div class="bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-800"
                         x-text="calculatedRentang">
                        1 Oktober 2028 – 30 September 2029 (2028/2029)
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="pt-2 flex items-center space-x-2">
                <button type="submit" 
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-xl text-xs shadow-xs transition">
                    Simpan sebagai draf
                </button>
                <button type="button" 
                        @click="cancelForm()"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-6 py-2 rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <!-- Grid Kartu Periode Tahun Fiskal (4 Kolom Sesuai Mockup) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <template x-for="p in fiscalPeriods" :key="p.id">
            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div>
                    <!-- Header Kartu: Tahun & Status Badge -->
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-xl font-black text-slate-900 tracking-tight" x-text="p.tahun">
                            2027/2028
                        </div>
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold text-white uppercase tracking-wider"
                              :class="{
                                  'bg-[#0B132B]': p.status === 'Draf' || p.status === 'Selesai',
                                  'bg-emerald-600': p.status === 'Aktif'
                              }"
                              x-text="p.status">
                        </span>
                    </div>

                    <!-- Rentang Tanggal -->
                    <div class="text-xs text-slate-500 leading-relaxed" x-text="p.rentang">
                        1 Oktober 2027 – 30 September 2028
                    </div>
                </div>

                <!-- Tombol Aksi Jika Draf -->
                <div class="mt-6">
                    <template x-if="p.status === 'Draf'">
                        <button type="button" 
                                @click="activatePeriod(p)"
                                class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 rounded-xl text-xs text-center transition shadow-2xs">
                            Aktifkan periode
                        </button>
                    </template>
                    <template x-if="p.status === 'Aktif'">
                        <div class="text-emerald-700 text-xs font-bold py-2 flex items-center space-x-1.5">
                            <i class="fa-solid fa-circle-check text-xs"></i>
                            <span>Sedang berjalan</span>
                        </div>
                    </template>
                    <template x-if="p.status === 'Selesai'">
                        <div class="text-slate-400 text-xs py-2 font-medium">
                            Tutup buku & diarsipkan
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

</div>
@endsection

