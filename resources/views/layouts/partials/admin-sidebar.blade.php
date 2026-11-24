<aside x-show="sidebarOpen" 
       class="w-64 bg-[#1E293B] text-slate-300 fixed left-0 top-0 bottom-0 z-40 overflow-y-auto flex flex-col justify-between select-none">
    
    <div>
        <!-- Logo Header WAYPOINT (PERSIS sidebar.blade.php) -->
        <div class="p-5 border-b border-slate-700/60 flex flex-col items-center text-center">
            <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-xl shadow-lg mb-2">
                yfi
            </div>
            <span class="text-white font-bold tracking-wider text-sm">WAYPOINT</span>
            <span class="text-[10px] text-slate-400 font-medium">PT Yoke Food Industries Indonesia</span>
        </div>

        <!-- Navigation Menu -->
        <nav class="p-4 space-y-5 text-xs">
            
            <!-- Ringkasan -->
            <div>
                <a href="{{ route('admin.ringkasan') }}" 
                   class="flex items-center space-x-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.ringkasan') ? 'bg-slate-800 text-white font-semibold border-l-4 border-red-500' : 'hover:bg-slate-800 text-slate-300' }}">
                    <i class="fa-solid fa-table-cells-large w-4 {{ request()->routeIs('admin.ringkasan') ? 'text-red-500' : 'text-slate-400' }}"></i>
                    <span>Ringkasan</span>
                </a>
            </div>

            <!-- MASTER DATA -->
            <div>
                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-2 px-2">MASTER DATA</div>
                <div class="space-y-1">
                    <!-- Pengguna & Hak Akses -->
                    <a href="{{ route('admin.pengguna') }}" 
                       class="flex items-center space-x-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.pengguna') ? 'bg-slate-800 text-white font-semibold border-l-4 border-red-500' : 'hover:bg-slate-800 text-slate-300' }}">
                        <i class="fa-solid fa-users w-4 {{ request()->routeIs('admin.pengguna') ? 'text-red-500' : 'text-slate-400' }}"></i>
                        <span>Pengguna & Hak Akses</span>
                    </a>

                    <!-- Data Distributor -->
                    <a href="{{ route('admin.distributor') }}" 
                       class="flex items-center space-x-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.distributor') ? 'bg-slate-800 text-white font-semibold border-l-4 border-red-500' : 'hover:bg-slate-800 text-slate-300' }}">
                        <i class="fa-solid fa-store w-4 {{ request()->routeIs('admin.distributor') ? 'text-red-500' : 'text-slate-400' }}"></i>
                        <span>Data Distributor</span>
                    </a>

                    <!-- Data Produk (SKU) -->
                    <a href="{{ route('admin.produk') }}" 
                       class="flex items-center space-x-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.produk') ? 'bg-slate-800 text-white font-semibold border-l-4 border-red-500' : 'hover:bg-slate-800 text-slate-300' }}">
                        <i class="fa-solid fa-cube w-4 {{ request()->routeIs('admin.produk') ? 'text-red-500' : 'text-slate-400' }}"></i>
                        <span>Data Produk (SKU)</span>
                    </a>

                    <!-- Periode Tahun Fiskal -->
                    <a href="{{ route('admin.tahun-fiskal') }}" 
                       class="flex items-center space-x-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.tahun-fiskal') ? 'bg-slate-800 text-white font-semibold border-l-4 border-red-500' : 'hover:bg-slate-800 text-slate-300' }}">
                        <i class="fa-solid fa-calendar-days w-4 {{ request()->routeIs('admin.tahun-fiskal') ? 'text-red-500' : 'text-slate-400' }}"></i>
                        <span>Periode Tahun Fiskal</span>
                    </a>
                </div>
            </div>

            <!-- PENGAWASAN -->
            <div>
                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-2 px-2">PENGAWASAN</div>
                <div class="space-y-1">
                    <!-- Log Aktivitas -->
                    <a href="{{ route('admin.log-aktivitas') }}" 
                       class="flex items-center space-x-3 px-3 py-2 rounded-lg transition {{ request()->routeIs('admin.log-aktivitas') ? 'bg-slate-800 text-white font-semibold border-l-4 border-red-500' : 'hover:bg-slate-800 text-slate-300' }}">
                        <i class="fa-solid fa-file-lines w-4 {{ request()->routeIs('admin.log-aktivitas') ? 'text-red-500' : 'text-slate-400' }}"></i>
                        <span>Log Aktivitas</span>
                    </a>
                </div>
            </div>

            <!-- LAPORAN -->
            <div>
                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-2 px-2">Laporan</div>
                <div class="space-y-1">
                    <a href="{{ route('pelaporan.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 {{ request()->routeIs('pelaporan.*') ? 'bg-slate-800 text-white border-l-4 border-red-500' : 'text-slate-300' }}">
                        <i class="fa-solid fa-chart-column w-4 text-slate-400"></i>
                        <span>Pelaporan & Stok (MySQL)</span>
                    </a>
                </div>
            </div>

        </nav>
    </div>

    <!-- Bagian Bawah Sidebar -->
    <div class="p-4 border-t border-slate-700/60 text-xs text-slate-400">
        @auth
            <div class="text-white font-bold text-xs">{{ Auth::user()->nama }}</div>
            <div class="text-[10px] text-slate-400 mb-2">{{ Auth::user()->peran->nama_peran ?? 'Admin' }}</div>
            <button type="button"
                    @click="showLogoutModal = true"
                    class="flex items-center space-x-2 text-red-400 hover:text-red-300 transition mt-1 cursor-pointer w-full text-left">
                <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
                <span class="text-[11px] font-semibold">Keluar</span>
            </button>
        @else
            <div class="text-white font-bold text-xs">WAYPOINT</div>
            <a href="{{ route('login') }}" class="text-[11px] text-red-400 hover:text-red-300 font-semibold flex items-center space-x-1.5 mt-1">
                <i class="fa-solid fa-right-to-bracket text-[10px]"></i>
                <span>Masuk ke Akun</span>
            </a>
        @endauth
    </div>
</aside>
