@extends('layouts.admin')

@section('title', 'Pengguna & Hak Akses')

@section('content')
<div x-data="{
    showForm: new URLSearchParams(window.location.search).get('openForm') === '1',
    searchQuery: '',
    selectedRoleFilter: 'Semua',
    selectedRole: 'Distributor'
}" class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">MASTER DATA</div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Pengguna & Hak Akses</h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Atur pendaftaran akun, pembaruan profil, penetapan peran, dan otorisasi hak akses.
            </p>
        </div>

        <button x-show="!showForm" @click="showForm = true" 
                type="button"
                class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-plus text-[11px]"></i>
            <span>Tambah Pengguna</span>
        </button>
    </div>

    {{-- Form Section: Pengguna Baru (Frame Merah Sesuai Mockup) --}}
    <div x-cloak x-show="showForm" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-2xl p-6 border-2 border-red-500/90 shadow-sm">
        
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-900">Pengguna baru</h2>
            <button type="button" @click="showForm = false" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.pengguna.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        NAMA LENGKAP <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="nama"
                           value="{{ old('nama') }}"
                           placeholder="Nama pengguna"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        EMAIL <span class="text-red-500">*</span>
                    </label>
                    <input type="email" 
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="nama@perusahaan.id"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>

            <!-- Peran Selector Buttons -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                    PERAN <span class="text-red-500">*</span>
                </label>
                <input type="hidden" name="peran" :value="selectedRole">
                <div class="flex flex-wrap gap-2">
                    @foreach(['Admin', 'Distributor', 'Logistik Pusat', 'SAM'] as $r)
                        <button type="button" 
                                @click="selectedRole = '{{ $r }}'"
                                class="px-4 py-2 rounded-xl text-xs font-semibold transition"
                                :class="selectedRole === '{{ $r }}' ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-200/80 hover:bg-slate-300 text-slate-700'">
                            {{ $r }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Kata Sandi Opsional -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        KATA SANDI <span class="text-slate-400 font-normal">(Opsional, default: password123)</span>
                    </label>
                    <input type="password" 
                           name="kata_sandi"
                           placeholder="Minimal 4 karakter"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <!-- Distributor Terkait (Jika Peran Distributor) -->
                <div x-show="selectedRole === 'Distributor'">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        PERUSAHAAN DISTRIBUTOR TERKAIT
                    </label>
                    <select name="id_distributor"
                            class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option value="">-- Pilih Distributor (Opsional) --</option>
                        @foreach($distributors as $d)
                            <option value="{{ $d->id_distributor }}">{{ $d->nama }} ({{ $d->wilayah->nama_wilayah ?? 'Area' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Actions Buttons -->
            <div class="pt-2 flex items-center space-x-2">
                <button type="submit" 
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-xl text-xs shadow-xs transition">
                    Simpan
                </button>
                <button type="button" 
                        @click="showForm = false"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-6 py-2 rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>

    {{-- Table & Filters Container Card --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">

        {{-- Search & Filter Tab Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="w-full md:w-96">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">CARI PENGGUNA</label>
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="Nama atau email"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-3.5 pr-8 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 self-end">
                @foreach(['Semua', 'Admin', 'Distributor', 'Logistik Pusat', 'SAM'] as $tab)
                    <button type="button" @click="selectedRoleFilter = '{{ $tab }}'"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="selectedRoleFilter === '{{ $tab }}' ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'">
                        {{ $tab }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Users Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-3">PENGGUNA</th>
                        <th class="pb-3 px-3">PERAN</th>
                        <th class="pb-3 px-3">STATUS</th>
                        <th class="pb-3 px-3">EMAIL</th>
                        <th class="pb-3 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        @php
                            $roleName = $user->peran->nama_peran ?? 'Unknown';
                            $initials = collect(explode(' ', $user->nama))->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
                            $roleClass = match($roleName) {
                                'Admin' => 'bg-red-600',
                                'Distributor' => 'bg-emerald-600',
                                'Logistik Pusat' => 'bg-[#0B132B]',
                                'SAM' => 'bg-slate-600',
                                default => 'bg-slate-500',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors"
                            x-show="(selectedRoleFilter === 'Semua' || '{{ $roleName }}' === selectedRoleFilter) &&
                                    (!searchQuery.trim() || '{{ strtolower($user->nama) }}'.includes(searchQuery.toLowerCase().trim()) || '{{ strtolower($user->email) }}'.includes(searchQuery.toLowerCase().trim()))">
                            <td class="py-3 px-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#0B132B] text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900">{{ $user->nama }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block {{ $roleClass }}">
                                    {{ $roleName }}
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block {{ $user->status_aktif ? 'bg-emerald-600' : 'bg-slate-700' }}">
                                    {{ $user->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-500">{{ $user->email }}</td>
                            <td class="py-3 px-3 text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    {{-- Toggle Status (Power Button) --}}
                                    <form action="{{ route('admin.pengguna.toggle', $user) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                title="{{ $user->status_aktif ? 'Nonaktifkan akun' : 'Aktifkan akun' }}"
                                                class="w-7 h-7 rounded-lg {{ $user->status_aktif ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-500 hover:bg-slate-600' }} text-white flex items-center justify-center transition">
                                            <i class="fa-solid fa-power-off text-[10px]"></i>
                                        </button>
                                    </form>

                                    {{-- Delete Button --}}
                                    <form action="{{ route('admin.pengguna.delete', $user) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $user->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                title="Hapus akun"
                                                class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition">
                                            <i class="fa-solid fa-trash text-[10px]"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                Belum ada data pengguna terdaftar di basis data. Klik tombol "Tambah Pengguna" di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Matriks Hak Akses Per Peran Card --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-4">
        <div>
            <h2 class="text-base font-bold text-slate-900 tracking-tight">Matriks hak akses per peran</h2>
            <p class="text-xs text-slate-500 mt-0.5">Referensi otorisasi modul untuk tiap peran pengguna.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-4 w-1/3">MODUL</th>
                        <th class="pb-3 px-4 text-center">ADMIN</th>
                        <th class="pb-3 px-4 text-center">DISTRIBUTOR</th>
                        <th class="pb-3 px-4 text-center">LOGISTIK PUSAT</th>
                        <th class="pb-3 px-4 text-center">SAM</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($permissions as $perm)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-slate-800">{{ $perm['modul'] }}</td>
                            @foreach(['admin', 'distributor', 'logistik', 'sam'] as $roleKey)
                                <td class="py-3.5 px-4 text-center">
                                    @if($perm[$roleKey])
                                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-emerald-600 text-white text-[11px] shadow-2xs">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                    @else
                                        <span class="text-slate-400 font-bold text-sm select-none">&mdash;</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
