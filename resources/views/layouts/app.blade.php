<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WAYPOINT') - PT Yoke Food Industries Indonesia</title>
    
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
                    }
                }
            }
        }
    </script>
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Admin Portal Mock Store -->
    <script src="{{ asset('js/admin-portal.js') }}"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-100/80 font-sans antialiased text-slate-800" 
      x-data="{ 
          sidebarOpen: window.innerWidth >= 1024,
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

    <div class="min-h-screen flex flex-col">
        <!-- Header Atas -->
        @include('layouts.partials.header')

        <div class="flex flex-1 pt-16">
            <!-- Sidebar Navigation -->
            @include('layouts.partials.sidebar')

            <!-- Main Content Area & Footer -->
            <div :class="sidebarOpen ? 'ml-64' : 'ml-0'" class="min-w-0 flex-1 flex flex-col min-h-[calc(100vh-4rem)] transition-all duration-300">
                <main class="flex-1 p-4 lg:p-8">
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

    @yield('scripts')
</body>
</html>