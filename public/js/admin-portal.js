/**
 * Portal Admin - Mock Data & Frontend State Store
 * Menangani simulasi state, validasi, kalkulasi, dan persistensi lokal (localStorage)
 * untuk seluruh fitur Portal Admin PT Yoke Food Industries Indonesia
 */

window.adminStore = {
    // Initial Seed Data (Sesuai Referensi Dokumen & Desain)
    initialData: {
        users: [
            { id: 1, nama: 'Rina Wulandari', email: 'rina.w@contoh.id', peran: 'Admin', status: 'Aktif', loginTerakhir: '23 Okt 2026, 09:12', inisial: 'RW' },
            { id: 2, nama: 'Fajar Hidayat', email: 'fajar.h@contoh.id', peran: 'Distributor', status: 'Aktif', loginTerakhir: '22 Okt 2026, 16:40', inisial: 'FH' },
            { id: 3, nama: 'Maya Sartika', email: 'maya.s@contoh.id', peran: 'Logistik Pusat', status: 'Aktif', loginTerakhir: '23 Okt 2026, 08:05', inisial: 'MS' },
            { id: 4, nama: 'Dedi Kurniawan', email: 'dedi.k@contoh.id', peran: 'SAM', status: 'Aktif', loginTerakhir: '21 Okt 2026, 14:22', inisial: 'DK' },
            { id: 5, nama: 'Yoga Prasetyo', email: 'yoga.p@contoh.id', peran: 'Distributor', status: 'Nonaktif', loginTerakhir: '02 Okt 2026, 10:30', inisial: 'YP' },
            { id: 6, nama: 'Lina Marlina', email: 'lina.m@contoh.id', peran: 'SAM', status: 'Aktif', loginTerakhir: '22 Okt 2026, 11:18', inisial: 'LM' },
            { id: 7, nama: 'Budi Santoso', email: 'budi.s@contoh.id', peran: 'Logistik Pusat', status: 'Aktif', loginTerakhir: '20 Okt 2026, 15:47', inisial: 'BS' },
            { id: 8, nama: 'Ahmad Zulfikar', email: 'ahmad.z@contoh.id', peran: 'Distributor', status: 'Aktif', loginTerakhir: '19 Okt 2026, 09:02', inisial: 'AZ' }
        ],
        permissions: [
            { modul: 'Kelola Pengguna', admin: true, distributor: false, logistik: false, sam: false },
            { modul: 'Master Distributor', admin: true, distributor: false, logistik: false, sam: true },
            { modul: 'Master Produk (SKU)', admin: true, distributor: false, logistik: true, sam: false },
            { modul: 'Tahun Fiskal', admin: true, distributor: false, logistik: false, sam: false },
            { modul: 'Kuota & Pengiriman', admin: true, distributor: true, logistik: true, sam: true },
            { modul: 'Laporan', admin: true, distributor: true, logistik: true, sam: true },
            { modul: 'Log Aktivitas', admin: true, distributor: false, logistik: false, sam: false }
        ],
        distributors: [
            { id: 1, kode: 'DST-001', nama: 'PT Minang Niaga Perkasa', wilayah: 'Sumbar', kontak: 'Hendra Putra', telepon: '0751-000-101' },
            { id: 2, kode: 'DST-002', nama: 'CV Ranah Bundo Sejahtera', wilayah: 'Sumbar', kontak: 'Rosa Anggraini', telepon: '0752-000-102' },
            { id: 3, kode: 'DST-003', nama: 'PT Andalas Mitra Distribusi', wilayah: 'Sumbar', kontak: 'Irfan Maulana', telepon: '0751-000-103' },
            { id: 4, kode: 'DST-004', nama: 'PT Riau Makmur Lestari', wilayah: 'Riau', kontak: 'Tania Rahmawati', telepon: '0761-000-104' },
            { id: 5, kode: 'DST-005', nama: 'CV Bumi Lancang Kuning', wilayah: 'Riau', kontak: 'Rudi Hartono', telepon: '0761-000-105' },
            { id: 6, kode: 'DST-006', nama: 'PT Pekanbaru Jaya Abadi', wilayah: 'Riau', kontak: 'Sari Wulansari', telepon: '0761-000-106' }
        ],
        products: [
            { id: 1, kode: 'SKU-001', nama: 'Produk Contoh A', harga: 185000, isi: 2000, nilai: 370000000 },
            { id: 2, kode: 'SKU-002', nama: 'Produk Contoh B', harga: 210000, isi: 2000, nilai: 420000000 },
            { id: 3, kode: 'SKU-003', nama: 'Produk Contoh C', harga: 95000, isi: 2000, nilai: 190000000 }
        ],
        fiscalPeriods: [
            { id: 1, tahun: '2027/2028', rentang: '1 Oktober 2027 – 30 September 2028', status: 'Draf' },
            { id: 2, tahun: '2026/2027', rentang: '1 Oktober 2026 – 30 September 2027', status: 'Aktif', aktifSejak: '01 Okt 2026', persentase: 6, hariKe: 23, totalHari: 365, bulanAktif: 'Okt' },
            { id: 3, tahun: '2025/2026', rentang: '1 Oktober 2025 – 30 September 2026', status: 'Selesai' },
            { id: 4, tahun: '2024/2025', rentang: '1 Oktober 2024 – 30 September 2025', status: 'Selesai' }
        ],
        logs: [
            { id: 1, waktu: '23 Okt 2026 · 09:41', pengguna: 'Rina Wulandari', peran: 'Admin', aksi: 'Ubah', modul: 'Kelola Pengguna', keterangan: 'Mengubah peran Dedi Kurniawan menjadi SAM', ip: '10.12.4.21' },
            { id: 2, waktu: '23 Okt 2026 · 09:28', pengguna: 'Rina Wulandari', peran: 'Admin', aksi: 'Tambah', modul: 'Master Distributor', keterangan: 'Menambahkan CV Bumi Lancang Kuning (Riau)', ip: '10.12.4.21' },
            { id: 3, waktu: '23 Okt 2026 · 09:12', pengguna: 'Rina Wulandari', peran: 'Admin', aksi: 'Login', modul: 'Autentikasi', keterangan: 'Masuk ke sistem', ip: '10.12.4.21' },
            { id: 4, waktu: '23 Okt 2026 · 08:05', pengguna: 'Maya Sartika', peran: 'Logistik Pusat', aksi: 'Login', modul: 'Autentikasi', keterangan: 'Masuk ke sistem', ip: '10.12.7.54' },
            { id: 5, waktu: '22 Okt 2026 · 16:40', pengguna: 'Fajar Hidayat', peran: 'Distributor', aksi: 'Login', modul: 'Autentikasi', keterangan: 'Masuk ke sistem', ip: '36.88.20.145' },
            { id: 6, waktu: '22 Okt 2026 · 15:10', pengguna: 'Rina Wulandari', peran: 'Admin', aksi: 'Ubah', modul: 'Master Produk (SKU)', keterangan: 'Memperbarui harga SKU-002', ip: '10.12.4.21' },
            { id: 7, waktu: '22 Okt 2026 · 11:32', pengguna: 'Rina Wulandari', peran: 'Admin', aksi: 'Ekspor', modul: 'Log Aktivitas', keterangan: 'Mengekspor log 1–21 Okt 2026', ip: '10.12.4.21' },
            { id: 8, waktu: '21 Okt 2026 · 14:22', pengguna: 'Dedi Kurniawan', peran: 'SAM', aksi: 'Login', modul: 'Autentikasi', keterangan: 'Masuk ke sistem', ip: '10.12.9.12' },
            { id: 9, waktu: '20 Okt 2026 · 10:03', pengguna: 'Rina Wulandari', peran: 'Admin', aksi: 'Hapus', modul: 'Kelola Pengguna', keterangan: 'Menghapus akun uji coba', ip: '10.12.4.21' },
            { id: 10, waktu: '01 Okt 2026 · 00:05', pengguna: 'Rina Wulandari', peran: 'Admin', aksi: 'Ubah', modul: 'Tahun Fiskal', keterangan: 'Mengaktifkan periode 2026/2027', ip: '10.12.4.21' }
        ]
    },

    // Memuat data dari localStorage atau inisialisasi awal
    load(key) {
        const stored = localStorage.getItem('yoke_admin_' + key);
        if (stored) {
            try {
                return JSON.parse(stored);
            } catch (e) {
                console.error('Error parsing stored data for', key, e);
            }
        }
        return JSON.parse(JSON.stringify(this.initialData[key]));
    },

    // Menyimpan data ke localStorage
    save(key, data) {
        localStorage.setItem('yoke_admin_' + key, JSON.stringify(data));
    },

    // Format Rupiah
    formatRupiah(number) {
        return 'Rp ' + Number(number || 0).toLocaleString('id-ID');
    },

    // Format Angka Ribuan
    formatNumber(number) {
        return Number(number || 0).toLocaleString('id-ID');
    },

    // Waktu format saat ini (misal: 23 Okt 2026 · 10:15)
    getCurrentFormattedTime() {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const now = new Date();
        const d = String(now.getDate()).padStart(2, '0');
        const m = months[now.getMonth()];
        const y = now.getFullYear();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        return `${d} ${m} ${y} · ${hh}:${mm}`;
    },

    // Generate inisial dari nama
    generateInitials(name) {
        if (!name) return '??';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    },

    // Catat log otomatis
    logActivity(aksi, modul, keterangan) {
        const logs = this.load('logs');
        const newLog = {
            id: Date.now(),
            waktu: this.getCurrentFormattedTime(),
            pengguna: 'Rina Wulandari',
            peran: 'Admin',
            aksi: aksi,
            modul: modul,
            keterangan: keterangan,
            ip: '10.12.4.21'
        };
        logs.unshift(newLog);
        this.save('logs', logs);
        window.dispatchEvent(new CustomEvent('admin-log-added', { detail: newLog }));
    },

    // Reset seluruh data ke kondisi awal dokumen
    resetAll() {
        localStorage.removeItem('yoke_admin_users');
        localStorage.removeItem('yoke_admin_permissions');
        localStorage.removeItem('yoke_admin_distributors');
        localStorage.removeItem('yoke_admin_products');
        localStorage.removeItem('yoke_admin_fiscalPeriods');
        localStorage.removeItem('yoke_admin_logs');
        window.location.reload();
    }
};

// Global Toast Dispatcher
window.showToast = function(message, type = 'success') {
    window.dispatchEvent(new CustomEvent('show-toast', {
        detail: { message, type }
    }));
};

