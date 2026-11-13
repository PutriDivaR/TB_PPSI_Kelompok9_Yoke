<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Admin') - PT Yoke Food Industries Indonesia</title>

    <!-- Tailwind CSS CDN dengan konfigurasi tema -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        admin: {
                            navy: '#0B132B',
                            slate: '#1E293B',
                            bg: '#EFF5F9',
                            red: '#DC2626',
                            green: '#047857'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <!-- Admin Portal Mock Store -->
    <script src="{{ asset('js/admin-portal.js') }}"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#EFF5F9] text-slate-800 antialiased min-h-screen flex" 
      x-data="{ 
          showLogoutModal: false,
          toasts: [],
          addToast(message, type = 'success') {
              const id = Date.now();
              this.toasts.push({ id, message, type });
              setTimeout(() => {
                  this.toasts = this.toasts.filter(t => t.id !== id);
              }, 3500);
          }
      }"
      @show-toast.window="addToast($event.detail.message, $event.detail.type)">

    <!-- Sidebar Portal Admin -->
    @include('layouts.partials.admin-sidebar')

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto min-h-screen p-8 lg:p-10">
        
        <!-- Quick Control Utility (Reset Demo Data & Environment Tag) -->
        <div class="mb-4 flex items-center justify-between text-xs text-slate-400">
            <div class="flex items-center space-x-2">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-medium text-slate-500">Mode Desain Frontend Terpadu &middot; Simpanan Otomatis (Local Store)</span>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="if(confirm('Reset semua data kembali ke data contoh dokumen?')) adminStore.resetAll()" 
                        type="button" 
                        class="hover:text-red-600 transition flex items-center space-x-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-white shadow-2xs">
                    <i class="fa-solid fa-rotate-left text-[10px]"></i>
                    <span>Reset Data Dummy Dokumen</span>
                </button>
            </div>
        </div>

        <!-- Page Yield Content -->
        <div class="flex-1">
            @yield('content')
        </div>

        <!-- Footer Notice Sesuai Dokumen -->
        <footer class="mt-12 pt-6 border-t border-slate-200/80 text-xs text-slate-400">
            Data pada tampilan ini adalah contoh untuk keperluan desain.
        </footer>
    </main>

    <!-- Logout Confirmation Modal (Simulasi Frontend) -->
    <div x-cloak x-show="showLogoutModal" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100"
             @click.away="showLogoutModal = false">
            <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl mb-4 mx-auto">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900 text-center">Keluar dari Portal Admin?</h3>
            <p class="text-xs text-slate-500 text-center mt-2 leading-relaxed">
                Anda akan mengakhiri sesi simulasi administrator Rina Wulandari.
            </p>
            <div class="mt-6 flex space-x-3">
                <button @click="showLogoutModal = false" 
                        type="button" 
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition">
                    Batal
                </button>
                <button @click="showLogoutModal = false; showToast('Berhasil keluar dari sesi simulasi admin.', 'info')" 
                        type="button" 
                        class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-xs shadow-md transition">
                    Ya, Keluar
                </button>
            </div>
        </div>
    </div>

    <!-- Floating Toast Notification Container -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col space-y-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="pointer-events-auto flex items-center space-x-3 px-4 py-3 rounded-xl shadow-xl text-xs font-semibold border transition-all duration-300 animate-slide-up"
                 :class="{
                     'bg-slate-900 text-white border-slate-800': toast.type === 'info',
                     'bg-emerald-600 text-white border-emerald-700': toast.type === 'success',
                     'bg-red-600 text-white border-red-700': toast.type === 'error'
                 }">
                <i :class="{
                    'fa-solid fa-circle-check text-sm text-emerald-200': toast.type === 'success',
                    'fa-solid fa-circle-xmark text-sm text-red-200': toast.type === 'error',
                    'fa-solid fa-circle-info text-sm text-blue-200': toast.type === 'info'
                }"></i>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    @yield('scripts')
</body>
</html>

