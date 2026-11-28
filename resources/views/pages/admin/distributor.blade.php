@extends('layouts.admin')

@section('title', 'Data Distributor')

@section('content')
<div x-data="{
    showForm: new URLSearchParams(window.location.search).get('openForm') === '1' || @json($errors->has('nama') || $errors->has('kontak') || $errors->has('telepon') || $errors->has('wilayah')),
    selectedWilayahFilter: 'Semua',
    selectedWilayah: @js(old('wilayah', 'Sumbar'))
}" class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">MASTER DATA</div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Data Distributor</h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Kelola profil distributor, cakupan wilayah operasional (Sumbar dan Riau), dan kontak resmi perusahaan.
            </p>
        </div>

        <button x-show="!showForm" 
                @click="showForm = true" 
                type="button"
                class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-plus text-[11px]"></i>
            <span>Tambah distributor</span>
        </button>
    </div>

    {{-- Form Distributor Baru (Sesuai Mockup Layar 4 - Frame Hijau) --}}
    <div x-cloak x-show="showForm" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-2xl p-6 border-2 border-emerald-500/60 shadow-sm">
        
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-900">Distributor baru</h2>
            <button type="button" @click="showForm = false" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.distributor.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- 3 Fields Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        NAMA PERUSAHAAN <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="nama"
                           value="{{ old('nama') }}"
                           placeholder="PT / CV ..."
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        NAMA KONTAK RESMI <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="kontak"
                           value="{{ old('kontak') }}"
                           placeholder="Nama penanggung jawab"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        TELEPON
                    </label>
                    <input type="text" 
                           name="telepon"
                           value="{{ old('telepon') }}"
                           placeholder="0xxx-xxx-xxx"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-600">
                </div>
            </div>

            <!-- Cakupan Wilayah Selector -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                    CAKUPAN WILAYAH <span class="text-red-500">*</span>
                </label>
                <input type="hidden" name="wilayah" :value="selectedWilayah">
                <div class="flex items-center space-x-2">
                    <button type="button" 
                            @click="selectedWilayah = 'Sumbar'"
                            class="px-4 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="selectedWilayah === 'Sumbar' ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-200/80 hover:bg-slate-300 text-slate-700'">
                        Sumbar
                    </button>
                    <button type="button" 
                            @click="selectedWilayah = 'Riau'"
                            class="px-4 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="selectedWilayah === 'Riau' ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-200/80 hover:bg-slate-300 text-slate-700'">
                        Riau
                    </button>
                </div>
            </div>

            <!-- Buttons -->
            <div class="pt-2 flex items-center space-x-2">
                <button type="submit" 
                        class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold px-6 py-2 rounded-xl text-xs shadow-xs transition">
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

    {{-- Table Card Container --}}
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">

        {{-- Filter Tabs Wilayah --}}
        <div class="flex items-center space-x-2">
            @foreach(['Semua', 'Sumbar', 'Riau'] as $tab)
                <button type="button" @click="selectedWilayahFilter = '{{ $tab }}'"
                        class="px-4 py-1.5 rounded-xl text-xs font-semibold transition"
                        :class="selectedWilayahFilter === '{{ $tab }}' ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'">
                    {{ $tab }}
                </button>
            @endforeach
        </div>

        {{-- Table Data Distributor --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-4 w-28">KODE</th>
                        <th class="pb-3 px-4">DISTRIBUTOR</th>
                        <th class="pb-3 px-4">WILAYAH</th>
                        <th class="pb-3 px-4">KONTAK RESMI</th>
                        <th class="pb-3 px-4">STATUS</th>
                        <th class="pb-3 px-4 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($distributors as $dist)
                        @php $wilayahName = $dist->wilayah->nama_wilayah ?? '-'; @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors"
                            x-show="selectedWilayahFilter === 'Semua' || '{{ $wilayahName }}'.includes(selectedWilayahFilter)">
                            <td class="py-3.5 px-4 font-semibold text-slate-700">DST-{{ str_pad($dist->id_distributor, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">{{ $dist->nama }}</td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block {{ str_contains(strtolower($wilayahName), 'sumbar') ? 'bg-emerald-600' : 'bg-red-600' }}">
                                    {{ $wilayahName }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $dist->kontak ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block {{ $dist->status_aktif ? 'bg-emerald-600' : 'bg-slate-700' }}">
                                    {{ $dist->status_aktif ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <form action="{{ route('admin.distributor.delete', $dist) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus data distributor {{ $dist->nama }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            title="Hapus distributor"
                                            class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 text-white inline-flex items-center justify-center transition">
                                        <i class="fa-solid fa-trash text-[10px]"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                Belum ada data distributor terdaftar di basis data. Klik tombol "Tambah distributor" di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
