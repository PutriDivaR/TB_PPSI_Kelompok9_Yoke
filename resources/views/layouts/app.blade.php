<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'WAYPOINT') - PT Yoke Food Industries Indonesia</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100/80 font-sans antialiased text-slate-800" 
      x-data="{ sidebarOpen: window.innerWidth >= 1024, showLogoutModal: false, showProfileModal: {{ $errors->hasAny(['nama', 'kata_sandi', 'kata_sandi_confirmation', 'foto_profil']) ? 'true' : 'false' }} }"
      @open-logout-modal.window="showLogoutModal = true"
      @open-profile-modal.window="showProfileModal = true">

    <div class="min-h-screen flex flex-col">
        <!-- Header Atas (Sekarang Sudah Dipasang) -->
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

    <!-- Profile Modal & Logout Modal -->
    @include('layouts.partials.profile-modal')
    @include('layouts.partials.logout-modal')

    @stack('scripts')
</body>
</html>