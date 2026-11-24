@extends('layouts.admin')

@section('title', 'Ringkasan Portal Admin')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div>
        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">
            RINGKASAN
        </div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
            Selamat datang{{ Auth::check() ? ', ' . Auth::user()->nama : '' }}
        </h1>
        <p class="text-sm text-slate-500 font-normal mt-1">
            Pantau kondisi master data dan aktivitas pengguna sistem.
        </p>
    </div>

    {{-- 4 Stats Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {{-- PENGGUNA AKTIF --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs hover:shadow-sm transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">PENGGUNA AKTIF</div>
            <div class="mt-2 text-4xl font-black text-slate-900">{{ $users->where('status_aktif', true)->count() }}</div>
            <div class="mt-1 text-xs text-slate-500">dari {{ $users->count() }} akun terdaftar</div>
        </div>

        {{-- DISTRIBUTOR --}}
        @php
            $sumbarCount = $distributors->filter(fn($d) => $d->wilayah && str_contains(strtolower($d->wilayah->nama_wilayah ?? ''), 'sumbar'))->count();
            $riauCount = $distributors->filter(fn($d) => $d->wilayah && str_contains(strtolower($d->wilayah->nama_wilayah ?? ''), 'riau'))->count();
        @endphp
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs hover:shadow-sm transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">DISTRIBUTOR</div>
            <div class="mt-2 text-4xl font-black text-slate-900">{{ $distributors->count() }}</div>
            <div class="mt-1 text-xs text-slate-500">Sumbar {{ $sumbarCount }} · Riau {{ $riauCount }}</div>
        </div>

        {{-- PRODUK (SKU) --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs hover:shadow-sm transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">PRODUK (SKU)</div>
            <div class="mt-2 text-4xl font-black text-slate-900">{{ $products->count() }}</div>
            <div class="mt-1 text-xs text-slate-500">katalog master aktif</div>
        </div>

        {{-- TAHUN FISKAL --}}
        @php $activePeriod = $fiscalPeriods->firstWhere('status', 'Aktif'); @endphp
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs hover:shadow-sm transition">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">TAHUN FISKAL</div>
            <div class="mt-2 text-3xl font-black text-slate-900 tracking-tight">
                @if($activePeriod)
                    {{ $activePeriod->tanggal_mulai->format('Y') }}/{{ $activePeriod->tanggal_selesai->format('Y') }}
                @else
                    —
                @endif
            </div>
            <div class="mt-1 text-xs text-slate-500">
                @if($activePeriod)
                    {{ $activePeriod->status }}
                @else
                    Belum ada periode aktif
                @endif
            </div>
        </div>
    </div>

    {{-- Middle Section: Pengguna per peran & Aksi cepat --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- Pengguna per peran Card --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs">
            <h2 class="text-base font-bold text-slate-900 tracking-tight mb-5">Pengguna per peran</h2>

            @php
                $totalUsers = max($users->count(), 1);
                $roleCounts = $users->groupBy(fn($u) => $u->peran->nama_peran ?? 'Unknown')->map->count();
                $roleColors = [
                    'Admin' => 'bg-red-600',
                    'Distributor' => 'bg-emerald-600',
                    'Logistik Pusat' => 'bg-[#0B132B]',
                    'SAM' => 'bg-[#0B132B]',
                ];
            @endphp

            <div class="space-y-4 text-xs font-medium">
                @foreach(['Admin', 'Distributor', 'Logistik Pusat', 'SAM'] as $role)
                    @php $count = $roleCounts->get($role, 0); @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="font-bold text-slate-900">{{ $role }}</span>
                            <span class="text-slate-500">{{ $count }} akun</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="{{ $roleColors[$role] ?? 'bg-slate-600' }} h-2 rounded-full transition-all duration-500"
                                 style="width: {{ round(($count / $totalUsers) * 100, 1) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Aksi cepat Card --}}
        <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs flex flex-col justify-between">
            <h2 class="text-base font-bold text-slate-900 tracking-tight mb-5">Aksi cepat</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 my-auto">
                <a href="{{ route('admin.pengguna') }}?openForm=1" 
                   class="bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-xl text-xs text-center shadow-xs transition flex items-center justify-center">
                    Tambah pengguna
                </a>
                <a href="{{ route('admin.distributor') }}?openForm=1" 
                   class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-3 px-4 rounded-xl text-xs text-center shadow-xs transition flex items-center justify-center">
                    Tambah distributor
                </a>
                <a href="{{ route('admin.produk') }}?openForm=1" 
                   class="bg-[#0B132B] hover:bg-slate-900 text-white font-semibold py-3 px-4 rounded-xl text-xs text-center shadow-xs transition flex items-center justify-center">
                    Tambah produk
                </a>
                <!-- Atur tahun fiskal (White bordered) -->
                <a href="{{ route('admin.tahun-fiskal') }}?openForm=1" 
                   class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-800 font-semibold py-3 px-4 rounded-xl text-xs text-center shadow-2xs transition flex items-center justify-center">
                    Atur tahun fiskal
                </a>
            </div>
        </div>

    </div>

    {{-- Aktivitas terbaru Card --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-base font-bold text-slate-900 tracking-tight">Aktivitas terbaru</h2>
            <a href="{{ route('admin.log-aktivitas') }}" 
               class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2 rounded-xl transition">
                Lihat semua log
            </a>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($logs->take(5) as $log)
                <div class="py-3.5 flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-3 min-w-0 pr-4">
                        @php
                            $badgeClass = match($log->aksi) {
                                'Tambah' => 'bg-emerald-600',
                                'Hapus' => 'bg-red-600',
                                default => 'bg-[#0B132B]',
                            };
                        @endphp
                        <span class="px-2.5 py-1 rounded-md font-bold text-[11px] text-white flex-shrink-0 {{ $badgeClass }}">
                            {{ $log->aksi }}
                        </span>
                        <div class="truncate text-slate-700">
                            <span class="font-bold text-slate-900">{{ $log->pengguna->nama ?? 'Sistem' }}</span>
                            <span class="text-slate-400 mx-1">&middot;</span>
                            <span>{{ $log->modul }}</span>
                        </div>
                    </div>
                    <div class="text-slate-400 font-normal whitespace-nowrap text-[11px]">
                        {{ $log->waktu ? $log->waktu->format('d M Y · H:i') : '-' }}
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    Belum ada aktivitas tercatat.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
