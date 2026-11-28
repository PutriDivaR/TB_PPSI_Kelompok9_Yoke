<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - WAYPOINT</title>

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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-[#1E293B] min-h-screen flex items-center justify-center p-4" x-data="{ showPassword: false }">

    <div class="w-full max-w-md">

        {{-- Logo & Branding --}}
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-2xl shadow-xl mx-auto mb-4">
                yfi
            </div>
            <h1 class="text-white font-extrabold text-2xl tracking-wider">WAYPOINT</h1>
            <p class="text-slate-400 text-xs mt-1 font-medium">PT Yoke Food Industries Indonesia</p>
        </div>

        {{-- Card Login --}}
        <div class="bg-white rounded-2xl shadow-2xl p-8">

            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Masuk ke Sistem</h2>
                <p class="text-xs text-slate-500 mt-1">Gunakan akun terdaftar untuk mengakses WAYPOINT.</p>
            </div>

            {{-- Flash: Logout Berhasil --}}
            @if(session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold px-4 py-3 rounded-xl flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Error Validasi --}}
            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold px-4 py-3 rounded-xl space-y-1">
                    @foreach($errors->all() as $error)
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-circle-exclamation text-red-500 text-[11px]"></i>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        ALAMAT EMAIL
                    </label>
                    <div class="relative">
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="nama@perusahaan.id"
                               required
                               autofocus
                               class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition {{ ($errors->has('email') || $errors->has('password')) ? 'ring-2 ring-red-400' : '' }}">
                        <i class="fa-solid fa-envelope absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                    </div>
                </div>

                {{-- Kata Sandi --}}
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        KATA SANDI
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'"
                               name="password"
                               placeholder="Masukkan kata sandi"
                               required
                               class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-10 pr-10 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition {{ ($errors->has('email') || $errors->has('password')) ? 'ring-2 ring-red-400' : '' }}">
                        <i class="fa-solid fa-lock absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                        <button type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-3.5 text-slate-400 hover:text-slate-600 transition"
                                tabindex="-1">
                            <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm"></i>
                        </button>
                    </div>
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center">
                    <input type="checkbox"
                           name="remember"
                           id="remember"
                           class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500">
                    <label for="remember" class="ml-2 text-xs text-slate-600 font-medium">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                {{-- Tombol Login --}}
                <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl text-sm shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-right-to-bracket text-sm"></i>
                    <span>Masuk</span>
                </button>
            </form>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6">
            <p class="text-slate-500 text-[10px] font-medium">
                &copy; {{ date('Y') }} PT Yoke Food Industries Indonesia — PPSI Kelompok 9 (SI-UNAND)
            </p>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</body>
</html>
