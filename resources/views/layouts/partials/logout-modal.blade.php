<!-- Logout Confirmation Modal / Notifikasi Konfirmasi Keluar -->
<div x-cloak 
     x-show="showLogoutModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="showLogoutModal = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
     style="display: none;">

    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 transform transition-all"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.away="showLogoutModal = false">

        <!-- Red Icon Circle -->
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl mb-4 mx-auto shadow-xs">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </div>

        <h3 class="text-base font-bold text-slate-900 text-center">Konfirmasi Keluar</h3>
        
        <p class="text-xs text-slate-500 text-center mt-2 leading-relaxed">
            @auth
                Apakah Anda yakin ingin keluar dari sistem WAYPOINT? Sesi kerja <strong>{{ Auth::user()->nama }}</strong> akan diakhiri.
            @else
                Apakah Anda yakin ingin keluar dari sistem WAYPOINT? Sesi kerja Anda saat ini akan diakhiri.
            @endauth
        </p>

        <div class="mt-6 flex space-x-3">
            <button @click="showLogoutModal = false" 
                    type="button" 
                    class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition cursor-pointer">
                Batal
            </button>

            <form action="{{ route('logout') }}" method="POST" class="flex-1">
                @csrf
                <button type="submit" 
                        class="w-full px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-xs shadow-md hover:shadow-lg transition cursor-pointer flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-right-from-bracket text-[11px]"></i>
                    <span>Ya, Keluar</span>
                </button>
            </form>
        </div>
    </div>
</div>
