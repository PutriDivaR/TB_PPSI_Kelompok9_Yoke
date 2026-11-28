<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Admin') - PT Yoke Food Industries Indonesia</title>

    <!-- Tailwind CSS (Vite + CDN Fallback) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            red: '#DC2626',
                            dark: '#1E293B'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100/80 font-sans antialiased text-slate-800" 
      x-data="{ 
          sidebarOpen: window.innerWidth >= 1024,
          showLogoutModal: false,
          showProfileModal: {{ $errors->hasAny(['nama', 'kata_sandi', 'kata_sandi_confirmation', 'foto_profil']) ? 'true' : 'false' }},
          toasts: [],
          addToast(message, type = 'success') {
              const id = Date.now();
              this.toasts.push({ id, message, type });
              setTimeout(() => {
                  this.toasts = this.toasts.filter(t => t.id !== id);
              }, 3500);
          }
      }"
      @show-toast.window="addToast($event.detail.message, $event.detail.type)"
      @open-logout-modal.window="showLogoutModal = true"
      @open-profile-modal.window="showProfileModal = true">

    <div class="min-h-screen flex flex-col">
        <!-- Header Atas (Persis seperti modul lain) -->
        @include('layouts.partials.header')

        <div class="flex flex-1 pt-16">
            <!-- Sidebar Navigation (Persis seperti modul lain) -->
            @include('layouts.partials.admin-sidebar')

            <!-- Main Content Area & Footer -->
            <div :class="sidebarOpen ? 'ml-64' : 'ml-0'" class="min-w-0 flex-1 flex flex-col min-h-[calc(100vh-4rem)] transition-all duration-300">
                <main class="flex-1 p-4 lg:p-8 space-y-4">
                    {{-- Alert Flash Message --}}
                    @if(session('success'))
                        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold px-4 py-3 rounded-xl flex items-center justify-between shadow-2xs">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold px-4 py-3 rounded-xl flex items-center justify-between shadow-2xs">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-circle-exclamation text-red-600 text-sm"></i>
                                <span>{{ session('error') }}</span>
                            </div>
                            <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    @endif

                    @if($errors->any() && !$errors->hasAny(['nama', 'kata_sandi', 'kata_sandi_confirmation', 'foto_profil']))
                        <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold px-4 py-3 rounded-xl shadow-2xs space-y-1">
                            <div class="font-bold flex items-center space-x-1.5 text-red-700 mb-1">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Terjadi kesalahan validasi:</span>
                            </div>
                            @foreach($errors->all() as $err)
                                <div class="ml-5 list-disc">• {{ $err }}</div>
                            @endforeach
                        </div>
                    @endif

                    @yield('content')
                </main>
                @include('layouts.partials.footer')
            </div>
        </div>
    </div>

    <!-- Floating Toast Notification Container -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col space-y-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div class="pointer-events-auto flex items-center space-x-3 px-4 py-3 rounded-xl shadow-xl text-xs font-semibold border transition-all duration-300"
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

    <!-- Profile Modal & Logout Modal -->
    @include('layouts.partials.profile-modal')
    @include('layouts.partials.logout-modal')

    @yield('scripts')
    @stack('scripts')
</body>
</html>
