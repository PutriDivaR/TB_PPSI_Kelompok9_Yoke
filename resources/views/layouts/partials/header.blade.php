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

    <!-- Right: User Profile (Click to Edit) & Logout -->
    <div class="flex items-center space-x-4">

        <!-- User Profile Info & Edit Profile Trigger -->
        <div class="flex items-center space-x-3">
            @auth
                <button type="button" 
                        @click="showProfileModal = true"
                        class="flex items-center space-x-3 text-left group cursor-pointer focus:outline-none"
                        title="Klik untuk mengedit profil Anda">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-bold text-slate-800 group-hover:text-red-600 transition flex items-center justify-end space-x-1.5">
                            <span>{{ Auth::user()->nama }}</span>
                            <i class="fa-solid fa-pen-to-square text-[10px] text-slate-400 group-hover:text-red-500 transition"></i>
                        </div>
                        <div class="text-[10px] text-slate-400">{{ Auth::user()->peran->nama_peran ?? 'Pengguna' }}</div>
                    </div>
                    <div class="relative">
                        <img class="w-8 h-8 rounded-full border border-slate-300 object-cover shadow-2xs group-hover:border-red-500 transition" 
                             src="{{ Auth::user()->foto_profil_url }}" 
                             alt="Avatar">
                        <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-white rounded-full flex items-center justify-center border border-slate-200 text-slate-500 text-[8px] group-hover:bg-red-600 group-hover:text-white transition">
                            <i class="fa-solid fa-pen"></i>
                        </div>
                    </div>
                </button>

                <button type="button"
                        @click="showLogoutModal = true"
                        class="ml-1 w-8 h-8 rounded-lg bg-slate-100 hover:bg-red-50 border border-slate-200 flex items-center justify-center text-slate-500 hover:text-red-600 transition cursor-pointer"
                        title="Keluar">
                    <i class="fa-solid fa-right-from-bracket text-xs"></i>
                </button>
            @else
                <div class="flex items-center space-x-3">
                    <div class="text-right hidden sm:block">
                        <div class="text-xs font-bold text-slate-800">{{ $activeUser['name'] }}</div>
                        <div class="text-[10px] text-slate-400">{{ $activeUser['desc'] }}</div>
                    </div>
                    <img class="w-8 h-8 rounded-full border border-slate-300" 
                         src="https://ui-avatars.com/api/?name={{ urlencode($activeUser['name']) }}&background=1E293B&color=fff" alt="Avatar">
                    <a href="{{ route('login') }}" 
                       class="ml-2 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-semibold shadow-xs transition">
                        Masuk
                    </a>
                </div>
            @endauth
        </div>

    </div>
</header>