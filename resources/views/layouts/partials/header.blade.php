@php
    $currentRole = request()->route('role', request('role', session('mock_role', 'distributor')));
    if (request()->route('role') || request()->has('role')) {
        session(['mock_role' => $currentRole]);
    }

    $rolesInfo = [
        'distributor' => ['name' => 'Putri Diva (Distributor)', 'desc' => 'Cabang Padang (DST-PDG-014)', 'color' => 'bg-emerald-500'],
        'logistik'    => ['name' => 'Riski (Logistik)', 'desc' => 'Logistik Pusat Jakarta', 'color' => 'bg-blue-500'],
        'sam'         => ['name' => 'Farhan Fitrahadi (SAM)', 'desc' => 'Sales Area Sumbar & Riau', 'color' => 'bg-amber-500'],
        'admin'       => ['name' => 'Suci Nurhaliza (Admin)', 'desc' => 'System Administrator', 'color' => 'bg-purple-500'],
    ];
    $activeUser = $rolesInfo[$currentRole] ?? $rolesInfo['distributor'];
@endphp

<header :class="sidebarOpen ? 'left-64' : 'left-0'" 
        class="bg-white border-b border-slate-200 h-16 fixed top-0 right-0 z-30 flex items-center justify-between px-6 transition-all duration-300">
    
    <button type="button" @click="sidebarOpen = !sidebarOpen" class="mr-3 rounded-lg border border-slate-200 p-2 text-slate-600" aria-label="Buka atau tutup menu"><i class="fa-solid fa-bars"></i></button>
    <!-- Left: Breadcrumbs -->
    <div class="hidden min-w-0 flex-1 items-center space-x-3 text-xs font-medium text-slate-500 lg:flex">
        <span class="text-slate-400">WAYPOINT</span>
        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
        <span class="text-slate-700 font-bold uppercase tracking-wider">{{ $currentRole }}</span>
        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
        <span class="text-red-600 font-bold">@yield('module_title', 'Modul E-PO & Forecasting')</span>
    </div>

    <!-- Right: Simulator Role Switcher Dropdown & User Profile -->
    <div class="flex items-center space-x-4">

        <!-- DROPDOWN GANTI AKTOR / ROLE -->
        <div class="relative" x-data="{ openRole: false }">
            <button @click="openRole = !openRole" 
                    class="flex items-center space-x-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 transition">
                <span class="w-2.5 h-2.5 rounded-full {{ $activeUser['color'] }}"></span>
                <span class="uppercase tracking-wider">Simulasi Aktor: {{ $currentRole }}</span>
                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
            </button>

            <div x-show="openRole" @click.away="openRole = false" 
                 class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-2 text-xs z-50" style="display: none;">
                <div class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 mb-1">
                    Pilih Aktor Pengguna:
                </div>
                
                <a href="{{ route('simulation.distributor') }}" class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 {{ $currentRole === 'distributor' ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-600' }}">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Distributor (Diva)</span>
                    </div>
                    @if($currentRole==='distributor')<i class="fa-solid fa-check text-emerald-600"></i>@endif
                </a>

                <a href="{{ route('simulation.logistik') }}" class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 {{ $currentRole === 'logistik' ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-600' }}">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span>Logistik Pusat (Fauzi)</span>
                    </div>
                    @if($currentRole==='logistik')<i class="fa-solid fa-check text-blue-600"></i>@endif
                </a>

                <a href="{{ route('simulation.sam') }}" class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 {{ $currentRole === 'sam' ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-600' }}">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>SAM (Taufik)</span>
                    </div>
                    @if($currentRole==='sam')<i class="fa-solid fa-check text-amber-600"></i>@endif
                </a>

                <a href="{{ route('simulation.admin') }}" class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 {{ $currentRole === 'admin' ? 'bg-slate-100 font-bold text-slate-900' : 'text-slate-600' }}">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        <span>Admin (Suci)</span>
                    </div>
                    @if($currentRole==='admin')<i class="fa-solid fa-check text-purple-600"></i>@endif
                </a>
            </div>
        </div>

        <div class="h-6 w-[1px] bg-slate-200"></div>

        <!-- User Profile Info -->
        <div class="flex items-center space-x-3">
            <div class="text-right hidden sm:block">
                <div class="text-xs font-bold text-slate-800">{{ $activeUser['name'] }}</div>
                <div class="text-[10px] text-slate-400">{{ $activeUser['desc'] }}</div>
            </div>
            <img class="w-8 h-8 rounded-full border border-slate-300" 
                 src="https://ui-avatars.com/api/?name={{ urlencode($activeUser['name']) }}&background=1E293B&color=fff" alt="Avatar">
        </div>

    </div>
</header>