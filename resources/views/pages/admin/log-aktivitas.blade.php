@extends('layouts.admin')

@section('title', 'Log Aktivitas (Audit Trail)')

@section('content')
<div class="space-y-6"
     x-data="{
         searchQuery: '',
         selectedActionFilter: 'Semua',
     }">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">PENGAWASAN</div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Log Aktivitas</h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Rekam jejak aktivitas pengguna (audit trail) untuk pengawasan sistem.
            </p>
        </div>
    </div>

    {{-- Container Table & Filter Card --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">

        {{-- Search Bar & Filter Action Tabs --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="w-full md:w-96">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">CARI LOG</label>
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="Pengguna, modul, atau keterangan"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-3.5 pr-8 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-1.5 self-end">
                @foreach(['Semua', 'Login', 'Logout', 'Tambah', 'Ubah', 'Hapus', 'Ekspor'] as $tab)
                    <button type="button" @click="selectedActionFilter = '{{ $tab }}'"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="selectedActionFilter === '{{ $tab }}' ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'">
                        {{ $tab }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Audit Log Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-3 w-40">WAKTU</th>
                        <th class="pb-3 px-3 w-48">PENGGUNA</th>
                        <th class="pb-3 px-3 w-24">AKSI</th>
                        <th class="pb-3 px-3 w-40">MODUL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        @php
                            $logUser = $log->pengguna->nama ?? 'Sistem';
                            $logRole = $log->pengguna->peran->nama_peran ?? '-';
                            $badgeClass = match($log->aksi) {
                                'Tambah' => 'bg-emerald-600',
                                'Hapus' => 'bg-red-600',
                                default => 'bg-[#0B132B]',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors"
                            x-show="(selectedActionFilter === 'Semua' || '{{ $log->aksi }}' === selectedActionFilter) &&
                                    (!searchQuery.trim() || '{{ strtolower($logUser) }}'.includes(searchQuery.toLowerCase().trim()) || '{{ strtolower($log->modul) }}'.includes(searchQuery.toLowerCase().trim()))">
                            <td class="py-3.5 px-3 text-slate-500 whitespace-nowrap">
                                {{ $log->waktu ? $log->waktu->format('d M Y · H:i') : '-' }}
                            </td>
                            <td class="py-3.5 px-3">
                                <div class="font-bold text-slate-900">{{ $logUser }}</div>
                                <div class="text-[11px] text-slate-500">{{ $logRole }}</div>
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block {{ $badgeClass }}">
                                    {{ $log->aksi }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 font-semibold text-slate-800">{{ $log->modul }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">
                                Belum ada rekaman log aktivitas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
