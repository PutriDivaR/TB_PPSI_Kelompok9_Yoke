@php
    $currentRoute = request()->route()->getName();
@endphp

<aside class="w-64 bg-[#0B132B] text-slate-300 flex-shrink-0 flex flex-col justify-between min-h-screen border-r border-slate-800/80 select-none">
    <div>
        <!-- Logo & Header Portal Admin -->
        <div class="p-6 pb-5 flex items-center space-x-3.5">
            <div class="w-10 h-10 bg-red-600 rounded-xl flex items-center justify-center text-white text-lg shadow-md shadow-red-900/30">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <h1 class="text-white font-bold text-sm leading-tight tracking-wide">Portal Admin</h1>
                <p class="text-[11px] text-slate-400 font-medium">Administrator Sistem</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="px-4 py-2 space-y-6 text-xs">
            <!-- Ringkasan -->
            <div>
                <a href="{{ route('admin.ringkasan') }}" 
                   class="flex items-center space-x-3 px-4 py-3 rounded-2xl font-semibold transition-all duration-150 {{ $currentRoute === 'admin.ringkasan' ? 'bg-red-600 text-white shadow-md shadow-red-950/40' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                    <i class="fa-solid fa-table-cells-large text-sm w-4 text-center"></i>
                    <span class="text-[13px]">Ringkasan</span>
                </a>
            </div>

            <!-- MASTER DATA -->
            <div>
                <div class="text-[10px] font-extrabold uppercase text-slate-400 tracking-widest px-4 mb-2">
                    MASTER DATA
                </div>
                <div class="space-y-1">
                    <!-- Pengguna & Hak Akses -->
                    <a href="{{ route('admin.pengguna') }}" 
                       class="flex items-center space-x-3 px-4 py-2.5 rounded-2xl font-medium transition-all duration-150 {{ $currentRoute === 'admin.pengguna' ? 'bg-red-600 text-white shadow-md shadow-red-950/40' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <i class="fa-solid fa-users text-sm w-4 text-center"></i>
                        <span class="text-[12.5px]">Pengguna & Hak Akses</span>
                    </a>

                    <!-- Data Distributor -->
                    <a href="{{ route('admin.distributor') }}" 
                       class="flex items-center space-x-3 px-4 py-2.5 rounded-2xl font-medium transition-all duration-150 {{ $currentRoute === 'admin.distributor' ? 'bg-red-600 text-white shadow-md shadow-red-950/40' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <i class="fa-solid fa-store text-sm w-4 text-center"></i>
                        <span class="text-[12.5px]">Data Distributor</span>
                    </a>

                    <!-- Data Produk (SKU) -->
                    <a href="{{ route('admin.produk') }}" 
                       class="flex items-center space-x-3 px-4 py-2.5 rounded-2xl font-medium transition-all duration-150 {{ $currentRoute === 'admin.produk' ? 'bg-red-600 text-white shadow-md shadow-red-950/40' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <i class="fa-solid fa-cube text-sm w-4 text-center"></i>
                        <span class="text-[12.5px]">Data Produk (SKU)</span>
                    </a>

                    <!-- Periode Tahun Fiskal -->
                    <a href="{{ route('admin.tahun-fiskal') }}" 
                       class="flex items-center space-x-3 px-4 py-2.5 rounded-2xl font-medium transition-all duration-150 {{ $currentRoute === 'admin.tahun-fiskal' ? 'bg-red-600 text-white shadow-md shadow-red-950/40' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <i class="fa-solid fa-calendar-days text-sm w-4 text-center"></i>
                        <span class="text-[12.5px]">Periode Tahun Fiskal</span>
                    </a>
                </div>
            </div>

            <!-- PENGAWASAN -->
            <div>
                <div class="text-[10px] font-extrabold uppercase text-slate-400 tracking-widest px-4 mb-2">
                    PENGAWASAN
                </div>
                <div class="space-y-1">
                    <!-- Log Aktivitas -->
                    <a href="{{ route('admin.log-aktivitas') }}" 
                       class="flex items-center space-x-3 px-4 py-2.5 rounded-2xl font-medium transition-all duration-150 {{ $currentRoute === 'admin.log-aktivitas' ? 'bg-red-600 text-white shadow-md shadow-red-950/40' : 'text-slate-300 hover:text-white hover:bg-slate-800/60' }}">
                        <i class="fa-solid fa-file-lines text-sm w-4 text-center"></i>
                        <span class="text-[12.5px]">Log Aktivitas</span>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <!-- User Profile & Footer Action -->
    <div class="p-4 border-t border-slate-800/80">
        <!-- Switcher Simulator Link (Subtle) -->
        <div class="mb-3 px-2 flex items-center justify-between text-[11px] text-slate-400">
            <span>Aktor Sistem</span>
            <a href="{{ route('simulation.distributor') }}" class="text-blue-400 hover:text-blue-300 underline font-medium">
                Pindah ke E-PO &rarr;
            </a>
        </div>

        <div class="bg-[#121B35] rounded-2xl p-3 border border-slate-700/50">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 bg-red-600 text-white rounded-xl flex items-center justify-center font-bold text-xs tracking-wider shadow-sm">
                    RW
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-white truncate">Rina Wulandari</p>
                    <p class="text-[11px] text-slate-400 truncate">Admin</p>
                </div>
            </div>
            
            <button @click="showLogoutModal = true" 
                    type="button"
                    class="mt-3 w-full bg-[#0B132B] hover:bg-slate-800 border border-slate-700 text-slate-200 text-xs font-semibold py-2 rounded-xl transition text-center flex items-center justify-center space-x-2">
                <i class="fa-solid fa-arrow-right-from-bracket text-[11px]"></i>
                <span>Keluar</span>
            </button>
        </div>
    </div>
</aside>

