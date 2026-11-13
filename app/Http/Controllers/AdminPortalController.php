<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminPortalController extends Controller
{
    /**
     * Data Master Pengguna Awal
     */
    public static function getInitialUsers(): array
    {
        return [
            ['id' => 1, 'nama' => 'Rina Wulandari', 'email' => 'rina.w@contoh.id', 'peran' => 'Admin', 'status' => 'Aktif', 'login_terakhir' => '23 Okt 2026, 09:12', 'inisial' => 'RW'],
            ['id' => 2, 'nama' => 'Fajar Hidayat', 'email' => 'fajar.h@contoh.id', 'peran' => 'Distributor', 'status' => 'Aktif', 'login_terakhir' => '22 Okt 2026, 16:40', 'inisial' => 'FH'],
            ['id' => 3, 'nama' => 'Maya Sartika', 'email' => 'maya.s@contoh.id', 'peran' => 'Logistik Pusat', 'status' => 'Aktif', 'login_terakhir' => '23 Okt 2026, 08:05', 'inisial' => 'MS'],
            ['id' => 4, 'nama' => 'Dedi Kurniawan', 'email' => 'dedi.k@contoh.id', 'peran' => 'SAM', 'status' => 'Aktif', 'login_terakhir' => '21 Okt 2026, 14:22', 'inisial' => 'DK'],
            ['id' => 5, 'nama' => 'Yoga Prasetyo', 'email' => 'yoga.p@contoh.id', 'peran' => 'Distributor', 'status' => 'Nonaktif', 'login_terakhir' => '02 Okt 2026, 10:30', 'inisial' => 'YP'],
            ['id' => 6, 'nama' => 'Lina Marlina', 'email' => 'lina.m@contoh.id', 'peran' => 'SAM', 'status' => 'Aktif', 'login_terakhir' => '22 Okt 2026, 11:18', 'inisial' => 'LM'],
            ['id' => 7, 'nama' => 'Budi Santoso', 'email' => 'budi.s@contoh.id', 'peran' => 'Logistik Pusat', 'status' => 'Aktif', 'login_terakhir' => '20 Okt 2026, 15:47', 'inisial' => 'BS'],
            ['id' => 8, 'nama' => 'Ahmad Zulfikar', 'email' => 'ahmad.z@contoh.id', 'peran' => 'Distributor', 'status' => 'Aktif', 'login_terakhir' => '19 Okt 2026, 09:02', 'inisial' => 'AZ'],
        ];
    }

    /**
     * Data Matriks Hak Akses Awal
     */
    public static function getInitialPermissions(): array
    {
        return [
            ['modul' => 'Kelola Pengguna', 'admin' => true, 'distributor' => false, 'logistik' => false, 'sam' => false],
            ['modul' => 'Master Distributor', 'admin' => true, 'distributor' => false, 'logistik' => false, 'sam' => true],
            ['modul' => 'Master Produk (SKU)', 'admin' => true, 'distributor' => false, 'logistik' => true, 'sam' => false],
            ['modul' => 'Tahun Fiskal', 'admin' => true, 'distributor' => false, 'logistik' => false, 'sam' => false],
            ['modul' => 'Kuota & Pengiriman', 'admin' => true, 'distributor' => true, 'logistik' => true, 'sam' => true],
            ['modul' => 'Laporan', 'admin' => true, 'distributor' => true, 'logistik' => true, 'sam' => true],
            ['modul' => 'Log Aktivitas', 'admin' => true, 'distributor' => false, 'logistik' => false, 'sam' => false],
        ];
    }

    /**
     * Data Master Distributor Awal
     */
    public static function getInitialDistributors(): array
    {
        return [
            ['id' => 1, 'kode' => 'DST-001', 'nama' => 'PT Minang Niaga Perkasa', 'wilayah' => 'Sumbar', 'kontak' => 'Hendra Putra', 'telepon' => '0751-000-101'],
            ['id' => 2, 'kode' => 'DST-002', 'nama' => 'CV Ranah Bundo Sejahtera', 'wilayah' => 'Sumbar', 'kontak' => 'Rosa Anggraini', 'telepon' => '0752-000-102'],
            ['id' => 3, 'kode' => 'DST-003', 'nama' => 'PT Andalas Mitra Distribusi', 'wilayah' => 'Sumbar', 'kontak' => 'Irfan Maulana', 'telepon' => '0751-000-103'],
            ['id' => 4, 'kode' => 'DST-004', 'nama' => 'PT Riau Makmur Lestari', 'wilayah' => 'Riau', 'kontak' => 'Tania Rahmawati', 'telepon' => '0761-000-104'],
            ['id' => 5, 'kode' => 'DST-005', 'nama' => 'CV Bumi Lancang Kuning', 'wilayah' => 'Riau', 'kontak' => 'Rudi Hartono', 'telepon' => '0761-000-105'],
            ['id' => 6, 'kode' => 'DST-006', 'nama' => 'PT Pekanbaru Jaya Abadi', 'wilayah' => 'Riau', 'kontak' => 'Sari Wulansari', 'telepon' => '0761-000-106'],
        ];
    }

    /**
     * Data Master Produk (SKU) Awal
     */
    public static function getInitialProducts(): array
    {
        return [
            ['id' => 1, 'kode' => 'SKU-001', 'nama' => 'Produk Contoh A', 'harga' => 185000, 'isi' => 2000, 'nilai' => 370000000],
            ['id' => 2, 'kode' => 'SKU-002', 'nama' => 'Produk Contoh B', 'harga' => 210000, 'isi' => 2000, 'nilai' => 420000000],
            ['id' => 3, 'kode' => 'SKU-003', 'nama' => 'Produk Contoh C', 'harga' => 95000,  'isi' => 2000, 'nilai' => 190000000],
        ];
    }

    /**
     * Data Master Periode Tahun Fiskal Awal
     */
    public static function getInitialFiscalPeriods(): array
    {
        return [
            ['id' => 1, 'tahun' => '2027/2028', 'rentang' => '1 Oktober 2027 – 30 September 2028', 'status' => 'Draf'],
            ['id' => 2, 'tahun' => '2026/2027', 'rentang' => '1 Oktober 2026 – 30 September 2027', 'status' => 'Aktif'],
            ['id' => 3, 'tahun' => '2025/2026', 'rentang' => '1 Oktober 2025 – 30 September 2026', 'status' => 'Selesai'],
            ['id' => 4, 'tahun' => '2024/2025', 'rentang' => '1 Oktober 2024 – 30 September 2025', 'status' => 'Selesai'],
        ];
    }

    /**
     * Data Log Aktivitas Awal
     */
    public static function getInitialLogs(): array
    {
        return [
            ['id' => 1, 'waktu' => '23 Okt 2026 · 09:41', 'pengguna' => 'Rina Wulandari', 'peran' => 'Admin', 'aksi' => 'Ubah', 'modul' => 'Kelola Pengguna', 'keterangan' => 'Mengubah peran Dedi Kurniawan menjadi SAM', 'ip' => '10.12.4.21'],
            ['id' => 2, 'waktu' => '23 Okt 2026 · 09:28', 'pengguna' => 'Rina Wulandari', 'peran' => 'Admin', 'aksi' => 'Tambah', 'modul' => 'Master Distributor', 'keterangan' => 'Menambahkan CV Bumi Lancang Kuning (Riau)', 'ip' => '10.12.4.21'],
            ['id' => 3, 'waktu' => '23 Okt 2026 · 09:12', 'pengguna' => 'Rina Wulandari', 'peran' => 'Admin', 'aksi' => 'Login', 'modul' => 'Autentikasi', 'keterangan' => 'Masuk ke sistem', 'ip' => '10.12.4.21'],
            ['id' => 4, 'waktu' => '23 Okt 2026 · 08:05', 'pengguna' => 'Maya Sartika', 'peran' => 'Logistik Pusat', 'aksi' => 'Login', 'modul' => 'Autentikasi', 'keterangan' => 'Masuk ke sistem', 'ip' => '10.12.7.54'],
            ['id' => 5, 'waktu' => '22 Okt 2026 · 16:40', 'pengguna' => 'Fajar Hidayat', 'peran' => 'Distributor', 'aksi' => 'Login', 'modul' => 'Autentikasi', 'keterangan' => 'Masuk ke sistem', 'ip' => '36.88.20.145'],
            ['id' => 6, 'waktu' => '22 Okt 2026 · 15:10', 'pengguna' => 'Rina Wulandari', 'peran' => 'Admin', 'aksi' => 'Ubah', 'modul' => 'Master Produk (SKU)', 'keterangan' => 'Memperbarui harga SKU-002', 'ip' => '10.12.4.21'],
            ['id' => 7, 'waktu' => '22 Okt 2026 · 11:32', 'pengguna' => 'Rina Wulandari', 'peran' => 'Admin', 'aksi' => 'Ekspor', 'modul' => 'Log Aktivitas', 'keterangan' => 'Mengekspor log 1–21 Okt 2026', 'ip' => '10.12.4.21'],
            ['id' => 8, 'waktu' => '21 Okt 2026 · 14:22', 'pengguna' => 'Dedi Kurniawan', 'peran' => 'SAM', 'aksi' => 'Login', 'modul' => 'Autentikasi', 'keterangan' => 'Masuk ke sistem', 'ip' => '10.12.9.12'],
            ['id' => 9, 'waktu' => '20 Okt 2026 · 10:03', 'pengguna' => 'Rina Wulandari', 'peran' => 'Admin', 'aksi' => 'Hapus', 'modul' => 'Kelola Pengguna', 'keterangan' => 'Menghapus akun uji coba', 'ip' => '10.12.4.21'],
            ['id' => 10, 'waktu' => '01 Okt 2026 · 00:05', 'pengguna' => 'Rina Wulandari', 'peran' => 'Admin', 'aksi' => 'Ubah', 'modul' => 'Tahun Fiskal', 'keterangan' => 'Mengaktifkan periode 2026/2027', 'ip' => '10.12.4.21'],
        ];
    }

    public function ringkasan()
    {
        return view('pages.admin.ringkasan', [
            'initialUsers' => self::getInitialUsers(),
            'initialDistributors' => self::getInitialDistributors(),
            'initialProducts' => self::getInitialProducts(),
            'initialFiscalPeriods' => self::getInitialFiscalPeriods(),
            'initialLogs' => self::getInitialLogs(),
        ]);
    }

    public function pengguna()
    {
        return view('pages.admin.pengguna', [
            'initialUsers' => self::getInitialUsers(),
            'initialPermissions' => self::getInitialPermissions(),
        ]);
    }

    public function distributor()
    {
        return view('pages.admin.distributor', [
            'initialDistributors' => self::getInitialDistributors(),
        ]);
    }

    public function produk()
    {
        return view('pages.admin.produk', [
            'initialProducts' => self::getInitialProducts(),
        ]);
    }

    public function tahunFiskal()
    {
        return view('pages.admin.tahun-fiskal', [
            'initialFiscalPeriods' => self::getInitialFiscalPeriods(),
        ]);
    }

    public function logAktivitas()
    {
        return view('pages.admin.log-aktivitas', [
            'initialLogs' => self::getInitialLogs(),
        ]);
    }
}

