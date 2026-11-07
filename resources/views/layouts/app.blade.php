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
<body class="bg-slate-100/80 font-sans antialiased text-slate-800" x-data="{ sidebarOpen: true }">

    <div class="min-h-screen flex flex-col">
        <!-- Header Atas (Sekarang Sudah Dipasang) -->
        @include('layouts.partials.header')

        <div class="flex flex-1 pt-16">
            <!-- Sidebar Navigation -->
            @include('layouts.partials.sidebar')

            <!-- Main Content Area -->
            <main :class="sidebarOpen ? 'ml-64' : 'ml-0'" class="flex-1 p-8 transition-all duration-300">
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>