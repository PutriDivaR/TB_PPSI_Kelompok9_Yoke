@extends('layouts.admin')

@section('title', 'Ringkasan Portal Admin')

@section('content')
<div x-data="{
    users: [],
    distributors: [],
    products: [],
    fiscalPeriods: [],
    logs: [],
    activePeriod: null,

    init() {
        this.users = adminStore.load('users');
        this.distributors = adminStore.load('distributors');
        this.products = adminStore.load('products');
        this.fiscalPeriods = adminStore.load('fiscalPeriods');
        this.logs = adminStore.load('logs');
        this.activePeriod = this.fiscalPeriods.find(p => p.status === 'Aktif') || this.fiscalPeriods[1];
    },

    get activeUsersCount() {
        return this.users.filter(u => u.status === 'Aktif').length;
    },

    get sumbarDistributorCount() {
        return this.distributors.filter(d => d.wilayah === 'Sumbar').length;
    },

    get riauDistributorCount() {
        return this.distributors.filter(d => d.wilayah === 'Riau').length;
    },

    get roleCounts() {
        const counts = { Admin: 0, Distributor: 0, 'Logistik Pusat': 0, SAM: 0 };
        this.users.forEach(u => {
            if (counts[u.peran] !== undefined) counts[u.peran]++;
        });
        return counts;
    },

    get maxRoleCount() {
        return Math.max(...Object.values(this.roleCounts), 1);
    }
}" x-init="init()" class="space-y-6">

    <!-- Page Header -->
    <div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">
            RINGKASAN
        </div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
            Selamat datang, Rina
        </h1>
        <p class="text-sm text-slate-500 font-normal mt-1">
            Pantau kondisi master data dan aktivitas pengguna sistem.
        </p>
    </div>

    <!-- 4 Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- PENGGUNA AKTIF -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs hover:shadow-sm transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                PENGGUNA AKTIF
            </div>
            <div class="mt-2 text-4xl font-black text-slate-900" x-text="activeUsersCount">
                7
            </div>
            <div class="mt-1 text-xs text-slate-500">
                dari <span x-text="users.length">8</span> akun terdaftar
            </div>
        </div>

        <!-- DISTRIBUTOR -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs hover:shadow-sm transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                DISTRIBUTOR
            </div>
            <div class="mt-2 text-4xl font-black text-slate-900" x-text="distributors.length">
                6
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Sumbar <span x-text="sumbarDistributorCount">3</span> · Riau <span x-text="riauDistributorCount">3</span>
            </div>
        </div>

        <!-- PRODUK (SKU) -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs hover:shadow-sm transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                PRODUK (SKU)
            </div>
            <div class="mt-2 text-4xl font-black text-slate-900" x-text="products.length">
                3
            </div>
            <div class="mt-1 text-xs text-slate-500">
                katalog master aktif
            </div>
        </div>

        <!-- TAHUN FISKAL -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs hover:shadow-sm transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                TAHUN FISKAL
            </div>
            <div class="mt-2 text-3xl font-black text-slate-900 tracking-tight" x-text="activePeriod?.tahun || '2026/2027'">
                2026/2027
            </div>
            <div class="mt-1 text-xs text-slate-500">
                hari ke-23 dari 365
            </div>
        </div>
    </div>

    <!-- Middle Section: Pengguna per peran & Aksi cepat -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Pengguna per peran Card -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 tracking-tight mb-5">
                Pengguna per peran
            </h2>

            <div class="space-y-4 text-xs font-medium">
                <!-- Admin -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="font-bold text-slate-900">Admin</span>
                        <span class="text-slate-500" x-text="roleCounts['Admin'] + ' akun'">1 akun</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-red-600 h-2 rounded-full transition-all duration-500" 
                             :style="`width: ${(roleCounts['Admin'] / users.length) * 100}%`"></div>
                    </div>
                </div>

                <!-- Distributor -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="font-bold text-slate-900">Distributor</span>
                        <span class="text-slate-500" x-text="roleCounts['Distributor'] + ' akun'">3 akun</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-emerald-600 h-2 rounded-full transition-all duration-500" 
                             :style="`width: ${(roleCounts['Distributor'] / users.length) * 100}%`"></div>
                    </div>
                </div>

                <!-- Logistik Pusat -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="font-bold text-slate-900">Logistik Pusat</span>
                        <span class="text-slate-500" x-text="roleCounts['Logistik Pusat'] + ' akun'">2 akun</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-[#0B132B] h-2 rounded-full transition-all duration-500" 
                             :style="`width: ${(roleCounts['Logistik Pusat'] / users.length) * 100}%`"></div>
                    </div>
                </div>

                <!-- SAM -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="font-bold text-slate-900">SAM</span>
                        <span class="text-slate-500" x-text="roleCounts['SAM'] + ' akun'">2 akun</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-[#0B132B] h-2 rounded-full transition-all duration-500" 
                             :style="`width: ${(roleCounts['SAM'] / users.length) * 100}%`"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aksi cepat Card -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs flex flex-col justify-between">
            <h2 class="text-base font-bold text-slate-900 tracking-tight mb-5">
                Aksi cepat
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 my-auto">
                <!-- Tambah pengguna (Red) -->
                <a href="{{ route('admin.pengguna') }}?openForm=1" 
                   class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-xl text-xs text-center shadow-xs transition flex items-center justify-center">
                    Tambah pengguna
                </a>

                <!-- Tambah distributor (Green) -->
                <a href="{{ route('admin.distributor') }}?openForm=1" 
                   class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-3 px-4 rounded-xl text-xs text-center shadow-xs transition flex items-center justify-center">
                    Tambah distributor
                </a>

                <!-- Tambah produk (Dark navy) -->
                <a href="{{ route('admin.produk') }}?openForm=1" 
                   class="bg-[#0B132B] hover:bg-slate-900 text-white font-semibold py-3 px-4 rounded-xl text-xs text-center shadow-xs transition flex items-center justify-center">
                    Tambah produk
                </a>

                <!-- Atur tahun fiskal (White bordered) -->
                <a href="{{ route('admin.tahun-fiskal') }}" 
                   class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-800 font-semibold py-3 px-4 rounded-xl text-xs text-center shadow-2xs transition flex items-center justify-center">
                    Atur tahun fiskal
                </a>
            </div>
        </div>

    </div>

    <!-- Aktivitas terbaru Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-bold text-slate-900 tracking-tight">
                Aktivitas terbaru
            </h2>
            <a href="{{ route('admin.log-aktivitas') }}" 
               class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2 rounded-xl transition">
                Lihat semua log
            </a>
        </div>

        <div class="divide-y divide-slate-100">
            <template x-for="log in logs.slice(0, 5)" :key="log.id">
                <div class="py-3.5 flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-3 min-w-0 pr-4">
                        <!-- Badge Aksi -->
                        <span class="px-2.5 py-1 rounded-md font-bold text-[11px] text-white flex-shrink-0"
                              :class="{
                                  'bg-[#0B132B]': log.aksi === 'Ubah' || log.aksi === 'Login' || log.aksi === 'Ekspor',
                                  'bg-emerald-600': log.aksi === 'Tambah',
                                  'bg-red-600': log.aksi === 'Hapus'
                              }"
                              x-text="log.aksi">
                        </span>

                        <!-- Detail Pengguna & Keterangan -->
                        <div class="truncate text-slate-700">
                            <span class="font-bold text-slate-900" x-text="log.pengguna"></span>
                            <span class="text-slate-400 mx-1">&middot;</span>
                            <span x-text="log.keterangan"></span>
                        </div>
                    </div>

                    <!-- Waktu -->
                    <div class="text-slate-400 font-normal whitespace-nowrap text-[11px]" x-text="log.waktu"></div>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection

