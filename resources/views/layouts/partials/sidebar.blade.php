@php
    $currentRole = request()->route('role', request('role', session('mock_role', 'distributor')));
    $roleHomeRoutes = [
        'distributor' => 'simulation.distributor',
        'logistik' => 'simulation.logistik',
        'sam' => 'simulation.sam',
        'admin' => 'simulation.admin',
    ];
@endphp

<aside x-show="sidebarOpen" 
       class="w-64 bg-[#1E293B] text-slate-300 fixed left-0 top-0 bottom-0 z-40 overflow-y-auto flex flex-col justify-between">
    
    <div>
        <!-- Logo Header WAYPOINT -->
        <div class="p-5 border-b border-slate-700/60 flex flex-col items-center text-center">
            <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-xl shadow-lg mb-2">
                yfi
            </div>
            <span class="text-white font-bold tracking-wider text-sm">WAYPOINT</span>
            <span class="text-[10px] text-slate-400 font-medium">PT Yoke Food Industries Indonesia</span>
        </div>

        <!-- Navigation items rendered per Role -->
        <nav class="p-4 space-y-5 text-xs">
            
            <!-- Dashboard (Semua Aktor) -->
            <div>
                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-2 px-2">Utama</div>
                <a href="{{ route($roleHomeRoutes[$currentRole] ?? 'simulation.distributor') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                    <i class="fa-solid fa-chart-line w-4 text-slate-400"></i>
                    <span>Dashboard {{ ucfirst($currentRole) }}</span>
                </a>
            </div>

            <!-- MENU DISTRIBUTOR -->
            @if($currentRole === 'distributor')
            <div>
                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-2 px-2">Operasional Distributor</div>
                <div class="space-y-1">
                    <a href="{{ route('simulation.distributor') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-slate-800 text-white font-semibold border-l-4 border-red-500">
                        <i class="fa-solid fa-file-invoice-dollar w-4 text-red-500"></i>
                        <span>E-PO (Purchase Order)</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">
                        <i class="fa-solid fa-truck-fast w-4 text-slate-400"></i>
                        <span>Monitoring Pengiriman</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">
                        <i class="fa-solid fa-box-open w-4 text-slate-400"></i>
                        <span>Klaim & Retur Barang</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- MENU LOGISTIK PUSAT -->
            @if($currentRole === 'logistik')
            <div>
                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-2 px-2">Operasional Logistik</div>
                <div class="space-y-1">
                    <a href="{{ route('simulation.logistik') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-slate-800 text-white font-semibold border-l-4 border-red-500">
                        <i class="fa-solid fa-check-double w-4 text-red-500"></i>
                        <span>Antrean Forecast & E-PO</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">
                        <i class="fa-solid fa-location-dot w-4 text-slate-400"></i>
                        <span>Update Milestone & ETA</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">
                        <i class="fa-solid fa-file-signature w-4 text-slate-400"></i>
                        <span>Proses Klaim & BAK</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- MENU SAM -->
            @if($currentRole === 'sam')
            <div>
                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-2 px-2">Sales Area Manager</div>
                <div class="space-y-1">
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">
                        <i class="fa-solid fa-pen-nib w-4 text-slate-400"></i>
                        <span>Verifikasi & E-Sign BAK</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">
                        <i class="fa-solid fa-chart-pie w-4 text-slate-400"></i>
                        <span>Laporan Penjualan Area</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- MENU ADMIN -->
            @if($currentRole === 'admin')
            <div>
                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider mb-2 px-2">Master Data & Admin</div>
                <div class="space-y-1">
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">
                        <i class="fa-solid fa-users w-4 text-slate-400"></i>
                        <span>Kelola Pengguna</span>
                    </a>
                    <a href="#" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">
                        <i class="fa-solid fa-database w-4 text-slate-400"></i>
                        <span>Master Data SKU & Fiskal</span>
                    </a>
                </div>
            </div>
            @endif

        </nav>
    </div>

    <div class="p-4 border-t border-slate-700/60 text-xs text-slate-400">
        <div class="text-white font-bold text-xs capitalize">{{ $currentRole }} Mode</div>
        <div class="text-[10px] text-slate-400">Simulasi Tanpa Login</div>
    </div>
</aside>