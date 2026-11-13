@extends('layouts.admin')

@section('title', 'Log Aktivitas (Audit Trail)')

@section('content')
<div x-data="{
    logs: [],
    searchQuery: '',
    selectedActionFilter: 'Semua',

    init() {
        this.logs = adminStore.load('logs');
    },

    get filteredLogs() {
        return this.logs.filter(log => {
            const matchesAction = this.selectedActionFilter === 'Semua' || log.aksi === this.selectedActionFilter;
            const q = this.searchQuery.toLowerCase().trim();
            const matchesQuery = !q || 
                log.pengguna.toLowerCase().includes(q) || 
                log.peran.toLowerCase().includes(q) || 
                log.modul.toLowerCase().includes(q) || 
                log.keterangan.toLowerCase().includes(q) ||
                log.ip.toLowerCase().includes(q);
            return matchesAction && matchesQuery;
        });
    },

    exportLogFile() {
        if (this.filteredLogs.length === 0) {
            showToast('Tidak ada data log yang dapat diekspor.', 'error');
            return;
        }

        // Generate CSV Data
        const headers = ['ID', 'Waktu', 'Pengguna', 'Peran', 'Aksi', 'Modul', 'Keterangan', 'Alamat IP'];
        const csvRows = [headers.join(',')];

        this.filteredLogs.forEach(l => {
            const row = [
                l.id,
                `&quot;${l.waktu}&quot;`,
                `&quot;${l.pengguna}&quot;`,
                `&quot;${l.peran}&quot;`,
                `&quot;${l.aksi}&quot;`,
                `&quot;${l.modul}&quot;`,
                `&quot;${l.keterangan}&quot;`,
                `&quot;${l.ip}&quot;`
            ];
            csvRows.push(row.join(','));
        });

        const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvRows.join('\n'));
        const downloadLink = document.createElement('a');
        downloadLink.setAttribute('href', csvContent);
        downloadLink.setAttribute('download', `log_aktivitas_${Date.now()}.csv`);
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        // Catat aksi ekspor ke dalam audit log
        adminStore.logActivity('Ekspor', 'Log Aktivitas', `Mengekspor log aktivitas sistem (${this.filteredLogs.length} baris)`);
        this.logs = adminStore.load('logs');
        showToast('Berkas CSV log aktivitas berhasil diunduh.', 'success');
    }
}" x-init="init()" class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">
                PENGAWASAN
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Log Aktivitas
            </h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Rekam jejak aktivitas pengguna (audit trail) untuk pengawasan sistem.
            </p>
        </div>

        <!-- Tombol Ekspor Log -->
        <button @click="exportLogFile()" 
                type="button"
                class="bg-white hover:bg-slate-50 border border-slate-300 text-slate-800 font-semibold px-4 py-2.5 rounded-xl text-xs shadow-2xs transition flex items-center space-x-2">
            <i class="fa-solid fa-arrow-up-from-bracket text-[11px]"></i>
            <span>Ekspor log</span>
        </button>
    </div>

    <!-- Container Table & Filter Card (Sesuai Mockup Layar 9) -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">
        
        <!-- Search Bar & Filter Action Tabs -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- CARI LOG -->
            <div class="w-full md:w-96">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                    CARI LOG
                </label>
                <div class="relative">
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Pengguna, modul, atau keterangan"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-3.5 pr-8 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-800">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-slate-400 text-xs"></i>
                </div>
            </div>

            <!-- Action Tabs (Semua, Login, Tambah, Ubah, Hapus, Ekspor) -->
            <div class="flex flex-wrap items-center gap-1.5 self-end">
                <template x-for="tab in ['Semua', 'Login', 'Tambah', 'Ubah', 'Hapus', 'Ekspor']" :key="tab">
                    <button type="button" 
                            @click="selectedActionFilter = tab"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="selectedActionFilter === tab ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                            x-text="tab">
                    </button>
                </template>
            </div>
        </div>

        <!-- Audit Log Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-3 w-40">WAKTU</th>
                        <th class="pb-3 px-3 w-48">PENGGUNA</th>
                        <th class="pb-3 px-3 w-24">AKSI</th>
                        <th class="pb-3 px-3 w-40">MODUL</th>
                        <th class="pb-3 px-3">KETERANGAN</th>
                        <th class="pb-3 px-3 text-right w-32">ALAMAT IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="log in filteredLogs" :key="log.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Waktu -->
                            <td class="py-3.5 px-3 text-slate-500 whitespace-nowrap" x-text="log.waktu"></td>

                            <!-- Pengguna & Peran -->
                            <td class="py-3.5 px-3">
                                <div class="font-bold text-slate-900" x-text="log.pengguna"></div>
                                <div class="text-[11px] text-slate-500" x-text="log.peran"></div>
                            </td>

                            <!-- Aksi Badge -->
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block"
                                      :class="{
                                          'bg-[#0B132B]': log.aksi === 'Ubah' || log.aksi === 'Login' || log.aksi === 'Ekspor',
                                          'bg-emerald-600': log.aksi === 'Tambah',
                                          'bg-red-600': log.aksi === 'Hapus'
                                      }"
                                      x-text="log.aksi">
                                </span>
                            </td>

                            <!-- Modul -->
                            <td class="py-3.5 px-3 font-semibold text-slate-800" x-text="log.modul"></td>

                            <!-- Keterangan -->
                            <td class="py-3.5 px-3 text-slate-700 leading-relaxed" x-text="log.keterangan"></td>

                            <!-- Alamat IP -->
                            <td class="py-3.5 px-3 text-right font-mono text-[11px] text-slate-500" x-text="log.ip"></td>
                        </tr>
                    </template>

                    <tr x-show="filteredLogs.length === 0">
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            Tidak ada rekaman log yang cocok dengan pencarian.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

