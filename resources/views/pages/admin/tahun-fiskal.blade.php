@extends('layouts.admin')

@section('title', 'Periode Tahun Fiskal')

@section('content')
<div x-data="{
    showForm: new URLSearchParams(window.location.search).get('openForm') === '1',
    inputTahunMulai: 2028,
    get calculatedRentang() {
        const y = parseInt(this.inputTahunMulai) || 2028;
        return `1 Oktober ${y} – 30 September ${y + 1} (${y}/${y + 1})`;
    },
    showActivateModal: false,
    activateTarget: {
        id: null,
        label: '',
        rentang: '',
        actionUrl: ''
    },
    confirmActivate(id, label, rentang, actionUrl) {
        this.activateTarget = { id, label, rentang, actionUrl };
        this.showActivateModal = true;
    }
}" class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">MASTER DATA</div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Periode Tahun Fiskal</h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Rentang tahun fiskal 1 Oktober &ndash; 30 September menjadi acuan perhitungan kuota dan agregasi laporan.
            </p>
        </div>

        <button x-show="!showForm" 
                @click="showForm = true" 
                type="button"
                class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-plus text-[11px]"></i>
            <span>Buat periode</span>
        </button>
    </div>

    {{-- Top Card: Periode Berjalan --}}
    @php $activePeriod = $fiscalPeriods->firstWhere('status', 'Aktif'); @endphp
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">PERIODE BERJALAN</div>
                <div class="text-3xl font-black text-slate-900 tracking-tight mt-1">
                    @if($activePeriod)
                        {{ $activePeriod->tanggal_mulai->format('Y') }}/{{ $activePeriod->tanggal_selesai->format('Y') }}
                    @else
                        Belum Ada Periode Aktif
                    @endif
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                    @if($activePeriod)
                        {{ $activePeriod->tanggal_mulai->translatedFormat('d F Y') }} – {{ $activePeriod->tanggal_selesai->translatedFormat('d F Y') }}
                    @else
                        Silakan buat periode baru di bawah dan klik "Aktifkan periode".
                    @endif
                </div>
            </div>

            @if($activePeriod)
                @php
                    $daysPassed = max(0, $activePeriod->tanggal_mulai->diffInDays(now()));
                    $totalDays = max(1, $activePeriod->tanggal_mulai->diffInDays($activePeriod->tanggal_selesai));
                    $percentage = round(($daysPassed / $totalDays) * 100, 1);
                @endphp
                <div class="sm:text-right">
                    <div class="text-3xl font-black text-slate-900">{{ min($percentage, 100) }}%</div>
                    <div class="text-xs text-slate-500 mt-0.5">hari ke-{{ $daysPassed }} dari {{ $totalDays }}</div>
                </div>
            @endif
        </div>

        @if($activePeriod)
            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div class="bg-red-600 h-2 rounded-full transition-all duration-500" style="width: {{ min($percentage, 100) }}%;"></div>
            </div>
        @endif
    </div>

    {{-- Form Periode Baru (Border Merah Sesuai Mockup) --}}
    <div x-cloak x-show="showForm" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-2xl p-6 border-2 border-red-500 shadow-sm">
        
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-slate-900">Periode baru</h2>
            <button type="button" @click="showForm = false" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('admin.tahun-fiskal.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        TAHUN MULAI <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           name="tahun_mulai"
                           x-model.number="inputTahunMulai" 
                           min="2020"
                           max="2050"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        Rentang otomatis
                    </label>
                    <div class="bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-800"
                         x-text="calculatedRentang">
                    </div>
                </div>
            </div>

            <div class="pt-2 flex items-center space-x-2">
                <button type="submit" 
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-xl text-xs shadow-xs transition">
                    Simpan sebagai draf
                </button>
                <button type="button" 
                        @click="showForm = false"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-6 py-2 rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>

    {{-- Grid Kartu Periode Tahun Fiskal --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @forelse($fiscalPeriods as $p)
            @php
                $tahunLabel = $p->tanggal_mulai->format('Y') . '/' . $p->tanggal_selesai->format('Y');
                $rentangLabel = $p->tanggal_mulai->translatedFormat('d F Y') . ' – ' . $p->tanggal_selesai->translatedFormat('d F Y');
            @endphp
            <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs flex flex-col justify-between hover:border-slate-300 transition">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-xl font-black text-slate-900 tracking-tight">
                            {{ $tahunLabel }}
                        </div>
                        @php
                            $statusClass = match($p->status) {
                                'Aktif' => 'bg-emerald-600',
                                'Draf' => 'bg-[#0B132B]',
                                default => 'bg-slate-600',
                            };
                        @endphp
                        <span class="px-2.5 py-0.5 rounded text-[11px] font-bold text-white uppercase tracking-wider {{ $statusClass }}">
                            {{ $p->status }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 leading-relaxed">
                        {{ $rentangLabel }}
                    </div>
                </div>

                <div class="mt-6">
                    @if($p->status === 'Aktif')
                        <div class="text-emerald-700 text-xs font-bold py-2 flex items-center space-x-1.5">
                            <i class="fa-solid fa-circle-check text-xs"></i>
                            <span>Sedang berjalan</span>
                        </div>
                    @elseif($p->status === 'Draf')
                        <button type="button" 
                                @click="confirmActivate('{{ $p->id_tahun_fiskal }}', '{{ $tahunLabel }}', '{{ $rentangLabel }}', '{{ route('admin.tahun-fiskal.activate', $p) }}')"
                                class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-2.5 rounded-xl text-xs text-center transition shadow-2xs cursor-pointer flex items-center justify-center space-x-1.5">
                            <i class="fa-solid fa-calendar-check text-[11px]"></i>
                            <span>Aktifkan periode</span>
                        </button>
                    @elseif($p->status === 'Selesai')
                        <div class="text-slate-400 text-xs py-2 font-medium">Tutup buku & diarsipkan</div>
                    @else
                        <div class="text-slate-400 text-xs py-2 font-medium">{{ $p->status }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-8 border border-slate-200/90 shadow-2xs text-center text-slate-400 text-xs">
                Belum ada data periode tahun fiskal di basis data. Klik tombol "Buat periode" di atas.
            </div>
        @endforelse
    </div>

    {{-- Modal Notifikasi Konfirmasi Aktivasi Periode Tahun Fiskal --}}
    <div x-cloak 
         x-show="showActivateModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="showActivateModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4"
         style="display: none;">

        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 transform transition-all text-center"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="showActivateModal = false">

            <!-- Emerald Icon Circle -->
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mb-4 mx-auto shadow-xs">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <h3 class="text-base font-bold text-slate-900">
                Aktifkan Periode <span x-text="activateTarget.label" class="text-emerald-700 font-black"></span>?
            </h3>

            <div class="mt-3 bg-amber-50 border border-amber-200/80 rounded-xl p-3 text-left space-y-1.5">
                <div class="flex items-start space-x-2 text-amber-800 text-xs font-semibold">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 flex-shrink-0"></i>
                    <span>Periode berjalan saat ini akan otomatis diselesaikan dan ditutup buku.</span>
                </div>
                <div class="text-[11px] text-amber-700 pl-5 leading-relaxed">
                    Rentang: <span x-text="activateTarget.rentang" class="font-medium text-slate-700"></span>
                </div>
            </div>

            <p class="text-xs text-slate-500 mt-3 leading-relaxed">
                Periode baru ini akan langsung menjadi acuan resmi perhitungan kuota, forecast, dan agregasi laporan.
            </p>

            <div class="mt-6 flex space-x-3">
                <button @click="showActivateModal = false" 
                        type="button" 
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-semibold text-xs hover:bg-slate-50 transition cursor-pointer">
                    Batal
                </button>

                <form :action="activateTarget.actionUrl" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" 
                            class="w-full px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-md hover:shadow-lg transition cursor-pointer flex items-center justify-center space-x-1.5">
                        <i class="fa-solid fa-check text-[11px]"></i>
                        <span>Ya, Aktifkan</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
