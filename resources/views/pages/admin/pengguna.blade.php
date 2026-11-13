@extends('layouts.admin')

@section('title', 'Pengguna & Hak Akses')

@section('content')
<div x-data="{
    users: [],
    permissions: [],
    searchQuery: '',
    selectedRoleFilter: 'Semua',
    
    // State Form
    showForm: false,
    isEditing: false,
    editId: null,
    formData: {
        nama: '',
        email: '',
        peran: 'Distributor',
    },

    init() {
        this.users = adminStore.load('users');
        this.permissions = adminStore.load('permissions');

        // Check if query param opened the form
        const params = new URLSearchParams(window.location.search);
        if (params.get('openForm')) {
            this.showForm = true;
        }
    },

    get filteredUsers() {
        return this.users.filter(user => {
            const matchesRole = this.selectedRoleFilter === 'Semua' || user.peran === this.selectedRoleFilter;
            const q = this.searchQuery.toLowerCase().trim();
            const matchesQuery = !q || user.nama.toLowerCase().includes(q) || user.email.toLowerCase().includes(q);
            return matchesRole && matchesQuery;
        });
    },

    openCreateForm() {
        this.isEditing = false;
        this.editId = null;
        this.formData = { nama: '', email: '', peran: 'Distributor' };
        this.showForm = true;
        this.$nextTick(() => {
            document.getElementById('inputNama')?.focus();
        });
    },

    openEditForm(user) {
        this.isEditing = true;
        this.editId = user.id;
        this.formData = {
            nama: user.nama,
            email: user.email,
            peran: user.peran
        };
        this.showForm = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    cancelForm() {
        this.showForm = false;
        this.isEditing = false;
        this.editId = null;
        this.formData = { nama: '', email: '', peran: 'Distributor' };
    },

    saveUser() {
        if (!this.formData.nama.trim() || !this.formData.email.trim()) {
            showToast('Harap lengkapi nama dan email pengguna.', 'error');
            return;
        }

        if (this.isEditing) {
            const index = this.users.findIndex(u => u.id === this.editId);
            if (index !== -1) {
                const oldPeran = this.users[index].peran;
                this.users[index].nama = this.formData.nama;
                this.users[index].email = this.formData.email;
                this.users[index].peran = this.formData.peran;
                this.users[index].inisial = adminStore.generateInitials(this.formData.nama);
                
                adminStore.save('users', this.users);
                adminStore.logActivity('Ubah', 'Kelola Pengguna', `Memperbarui profil ${this.formData.nama} (${this.formData.peran})`);
                showToast(`Akun ${this.formData.nama} berhasil diperbarui.`, 'success');
            }
        } else {
            const newUser = {
                id: Date.now(),
                nama: this.formData.nama,
                email: this.formData.email,
                peran: this.formData.peran,
                status: 'Aktif',
                loginTerakhir: 'Baru dibuat',
                inisial: adminStore.generateInitials(this.formData.nama)
            };
            this.users.unshift(newUser);
            adminStore.save('users', this.users);
            adminStore.logActivity('Tambah', 'Kelola Pengguna', `Menambahkan pengguna baru ${this.formData.nama} (${this.formData.peran})`);
            showToast(`Pengguna baru ${this.formData.nama} berhasil ditambahkan.`, 'success');
        }

        this.cancelForm();
    },

    toggleStatus(user) {
        user.status = user.status === 'Aktif' ? 'Nonaktif' : 'Aktif';
        adminStore.save('users', this.users);
        adminStore.logActivity('Ubah', 'Kelola Pengguna', `Mengubah status akun ${user.nama} menjadi ${user.status}`);
        showToast(`Status ${user.nama} diubah menjadi ${user.status}.`, 'info');
    },

    deleteUser(user) {
        if (user.peran === 'Admin') {
            showToast('Akun Administrator utama tidak dapat dihapus demi keamanan.', 'error');
            return;
        }

        if (confirm(`Apakah Anda yakin ingin menghapus akun ${user.nama}?`)) {
            this.users = this.users.filter(u => u.id !== user.id);
            adminStore.save('users', this.users);
            adminStore.logActivity('Hapus', 'Kelola Pengguna', `Menghapus akun ${user.nama}`);
            showToast(`Akun ${user.nama} berhasil dihapus.`, 'success');
        }
    },

    togglePermission(perm, roleKey) {
        if (roleKey === 'admin') {
            showToast('Hak akses Admin dikunci untuk menjaga keamanan sistem.', 'error');
            return;
        }
        perm[roleKey] = !perm[roleKey];
        adminStore.save('permissions', this.permissions);
        const statusText = perm[roleKey] ? 'diberikan' : 'dicabut';
        adminStore.logActivity('Ubah', 'Kelola Pengguna', `Otorisasi modul ${perm.modul} ${statusText} untuk peran ${roleKey.toUpperCase()}`);
        showToast(`Akses ${perm.modul} untuk ${roleKey.toUpperCase()} berhasil diperbarui.`, 'success');
    }
}" x-init="init()" class="space-y-6">

    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">
                MASTER DATA
            </div>
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Pengguna & Hak Akses
            </h1>
            <p class="text-sm text-slate-500 font-normal mt-1">
                Atur pendaftaran akun, pembaruan profil, penetapan peran, dan otorisasi hak akses.
            </p>
        </div>

        <button x-show="!showForm" @click="openCreateForm()" 
                type="button"
                class="bg-red-600 hover:bg-red-700 text-white font-semibold px-4 py-2.5 rounded-xl text-xs shadow-xs transition flex items-center space-x-2">
            <i class="fa-solid fa-plus text-[11px]"></i>
            <span>Tambah Pengguna</span>
        </button>
    </div>

    <!-- Form Section (Pengguna Baru / Ubah Pengguna) - Frame Merah Sesuai Mockup -->
    <div x-cloak x-show="showForm" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="bg-white rounded-2xl p-6 border-2 border-red-500/90 shadow-sm">
        
        <h2 class="text-sm font-bold text-slate-900 mb-4" x-text="isEditing ? 'Ubah profil pengguna' : 'Pengguna baru'">
            Pengguna baru
        </h2>

        <form @submit.prevent="saveUser()" class="space-y-4">
            <!-- Two Fields Side by Side -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        NAMA LENGKAP
                    </label>
                    <input type="text" 
                           id="inputNama"
                           x-model="formData.nama" 
                           placeholder="Nama pengguna"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">
                        EMAIL
                    </label>
                    <input type="email" 
                           x-model="formData.email" 
                           placeholder="nama@perusahaan.id"
                           required
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                </div>
            </div>

            <!-- Peran Selector Buttons -->
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                    PERAN
                </label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="r in ['Admin', 'Distributor', 'Logistik Pusat', 'SAM']" :key="r">
                        <button type="button" 
                                @click="formData.peran = r"
                                class="px-4 py-2 rounded-xl text-xs font-semibold transition"
                                :class="formData.peran === r ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-200/80 hover:bg-slate-300 text-slate-700'"
                                x-text="r">
                        </button>
                    </template>
                </div>
            </div>

            <!-- Actions Buttons -->
            <div class="pt-2 flex items-center space-x-2">
                <button type="submit" 
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2 rounded-xl text-xs shadow-xs transition">
                    Simpan
                </button>
                <button type="button" 
                        @click="cancelForm()"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-6 py-2 rounded-xl text-xs transition">
                    Batal
                </button>
            </div>
        </form>
    </div>

    <!-- Table & Filters Container Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-5">
        
        <!-- Search & Filter Tab Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Search Input -->
            <div class="w-full md:w-96">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                    CARI PENGGUNA
                </label>
                <div class="relative">
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Nama atau email"
                           class="w-full bg-[#EBF3FB] border border-blue-100 rounded-xl pl-3.5 pr-8 py-2 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-slate-400 text-xs"></i>
                </div>
            </div>

            <!-- Role Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5 self-end">
                <template x-for="tab in ['Semua', 'Admin', 'Distributor', 'Logistik Pusat', 'SAM']" :key="tab">
                    <button type="button" 
                            @click="selectedRoleFilter = tab"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition"
                            :class="selectedRoleFilter === tab ? 'bg-[#0B132B] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                            x-text="tab">
                    </button>
                </template>
            </div>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 px-3">PENGGUNA</th>
                        <th class="pb-3 px-3">PERAN</th>
                        <th class="pb-3 px-3">STATUS</th>
                        <th class="pb-3 px-3">LOGIN TERAKHIR</th>
                        <th class="pb-3 px-3 text-right">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="user in filteredUsers" :key="user.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Pengguna -->
                            <td class="py-3 px-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 rounded-lg bg-[#0B132B] text-white flex items-center justify-center font-bold text-[11px] flex-shrink-0"
                                         x-text="user.inisial">
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900" x-text="user.nama"></div>
                                        <div class="text-[11px] text-slate-500" x-text="user.email"></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Peran Badge -->
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block"
                                      :class="{
                                          'bg-red-600': user.peran === 'Admin',
                                          'bg-emerald-600': user.peran === 'Distributor',
                                          'bg-[#0B132B]': user.peran === 'Logistik Pusat',
                                          'bg-slate-600': user.peran === 'SAM'
                                      }"
                                      x-text="user.peran">
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3 px-3">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold text-white inline-block"
                                      :class="user.status === 'Aktif' ? 'bg-emerald-600' : 'bg-slate-700'"
                                      x-text="user.status">
                                </span>
                            </td>

                            <!-- Login Terakhir -->
                            <td class="py-3 px-3 text-slate-500" x-text="user.loginTerakhir"></td>

                            <!-- Aksi (3 Square Icon Buttons) -->
                            <td class="py-3 px-3 text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <!-- Edit -->
                                    <button @click="openEditForm(user)" 
                                            title="Ubah profil pengguna"
                                            type="button" 
                                            class="w-7 h-7 rounded-lg bg-slate-500 hover:bg-slate-600 text-white flex items-center justify-center transition">
                                        <i class="fa-solid fa-pencil text-[10px]"></i>
                                    </button>

                                    <!-- Toggle Status (Power) -->
                                    <button @click="toggleStatus(user)" 
                                            :title="user.status === 'Aktif' ? 'Nonaktifkan akun' : 'Aktifkan akun'"
                                            type="button" 
                                            class="w-7 h-7 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center transition">
                                        <i class="fa-solid fa-power-off text-[10px]"></i>
                                    </button>

                                    <!-- Delete -->
                                    <button @click="deleteUser(user)" 
                                            title="Hapus akun"
                                            type="button" 
                                            class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition">
                                        <i class="fa-solid fa-trash text-[10px]"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredUsers.length === 0">
                        <td colspan="5" class="py-8 text-center text-slate-400">
                            Tidak ada pengguna yang cocok dengan kriteria pencarian.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Matriks Hak Akses Per Peran Card -->
    <div class="bg-white rounded-2xl p-6 border border-slate-200/90 shadow-2xs space-y-4">
        <div>
            <h2 class="text-base font-bold text-slate-900 tracking-tight">
                Matriks hak akses per peran
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Klik sel untuk memberi atau mencabut akses modul. Hak akses Admin dikunci.
            </p>
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
                    <template x-for="perm in permissions" :key="perm.modul">
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-4 font-semibold text-slate-800" x-text="perm.modul"></td>

                            <!-- Admin (Locked) -->
                            <td class="py-3.5 px-4 text-center cursor-not-allowed" @click="togglePermission(perm, 'admin')">
                                <div class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-emerald-600 text-white text-[11px] shadow-2xs">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            </td>

                            <!-- Distributor -->
                            <td class="py-3.5 px-4 text-center cursor-pointer hover:bg-slate-100/60 rounded-lg transition" 
                                @click="togglePermission(perm, 'distributor')">
                                <template x-if="perm.distributor">
                                    <div class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-emerald-600 text-white text-[11px] shadow-2xs">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                </template>
                                <template x-if="!perm.distributor">
                                    <span class="text-slate-400 font-bold text-sm select-none">&mdash;</span>
                                </template>
                            </td>

                            <!-- Logistik Pusat -->
                            <td class="py-3.5 px-4 text-center cursor-pointer hover:bg-slate-100/60 rounded-lg transition" 
                                @click="togglePermission(perm, 'logistik')">
                                <template x-if="perm.logistik">
                                    <div class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-emerald-600 text-white text-[11px] shadow-2xs">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                </template>
                                <template x-if="!perm.logistik">
                                    <span class="text-slate-400 font-bold text-sm select-none">&mdash;</span>
                                </template>
                            </td>

                            <!-- SAM -->
                            <td class="py-3.5 px-4 text-center cursor-pointer hover:bg-slate-100/60 rounded-lg transition" 
                                @click="togglePermission(perm, 'sam')">
                                <template x-if="perm.sam">
                                    <div class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-emerald-600 text-white text-[11px] shadow-2xs">
                                        <i class="fa-solid fa-check"></i>
                                    </div>
                                </template>
                                <template x-if="!perm.sam">
                                    <span class="text-slate-400 font-bold text-sm select-none">&mdash;</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

