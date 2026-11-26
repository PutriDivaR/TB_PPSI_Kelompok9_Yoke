<?php

namespace App\Http\Controllers;

use App\Models\Distributor;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Produk;
use App\Models\TahunFiskal;
use App\Models\Wilayah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminPortalController extends Controller
{
    /**
     * Matriks hak akses per peran (statis, sesuai desain sistem).
     */
    private static function getPermissionsMatrix(): array
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

    private function logAction(string $modul, string $aksi): void
    {
        LogAktivitas::create([
            'id_pengguna' => Auth::id() ?? Pengguna::first()->id_pengguna ?? 1,
            'modul' => $modul,
            'aksi' => $aksi,
            'waktu' => now(),
        ]);
    }

    public function ringkasan()
    {
        $users = Pengguna::with('peran')->get();
        $distributors = Distributor::with('wilayah')->get();
        $products = Produk::all();
        $fiscalPeriods = TahunFiskal::orderBy('tanggal_mulai', 'desc')->get();
        $logs = LogAktivitas::with('pengguna.peran')
            ->orderBy('waktu', 'desc')
            ->limit(10)
            ->get();

        return view('pages.admin.ringkasan', compact(
            'users',
            'distributors',
            'products',
            'fiscalPeriods',
            'logs'
        ));
    }

    // ==================== 1. PENGGUNA & HAK AKSES ====================

    public function pengguna()
    {
        $users = Pengguna::with(['peran', 'distributor'])->get();
        $roles = Peran::all();
        $distributors = Distributor::all();
        $permissions = self::getPermissionsMatrix();

        return view('pages.admin.pengguna', compact('users', 'roles', 'distributors', 'permissions'));
    }

    public function storePengguna(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:150',
            'email' => 'required|email|max:254|unique:pengguna,email',
            'peran' => 'required|string',
            'kata_sandi' => 'nullable|string|min:4',
            'id_distributor' => 'nullable|exists:distributor,id_distributor',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'peran.required' => 'Peran pengguna wajib dipilih.',
        ]);

        $peran = Peran::firstOrCreate(
            ['nama_peran' => $request->peran],
            ['deskripsi' => 'Peran ' . $request->peran]
        );

        $pengguna = Pengguna::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'kata_sandi' => Hash::make($request->kata_sandi ?: 'password123'),
            'id_peran' => $peran->id_peran,
            'status_aktif' => true,
            'id_distributor' => $request->id_distributor ?: null,
        ]);

        $this->logAction('Kelola Pengguna', 'Tambah');

        return redirect()->route('admin.pengguna')
            ->with('success', "Pengguna baru '{$pengguna->nama}' ({$peran->nama_peran}) berhasil ditambahkan.");
    }

    public function togglePengguna(Pengguna $pengguna)
    {
        $newStatus = ! $pengguna->status_aktif;
        $pengguna->update(['status_aktif' => $newStatus]);

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        $this->logAction('Kelola Pengguna', 'Ubah');

        return redirect()->back()
            ->with('success', "Status akun '{$pengguna->nama}' berhasil {$statusLabel}.");
    }

    public function deletePengguna(Pengguna $pengguna)
    {
        if (Auth::check() && Auth::id() === $pengguna->id_pengguna) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $nama = $pengguna->nama;
        $pengguna->delete();

        $this->logAction('Kelola Pengguna', 'Hapus');

        return redirect()->back()->with('success', "Akun '{$nama}' berhasil dihapus.");
    }

    // ==================== 2. MASTER DISTRIBUTOR ====================

    public function distributor()
    {
        $distributors = Distributor::with('wilayah')->get();
        $wilayahList = Wilayah::all();

        return view('pages.admin.distributor', compact('distributors', 'wilayahList'));
    }

    public function storeDistributor(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:150',
            'kontak' => 'required|string|max:60',
            'telepon' => 'nullable|string|max:60',
            'wilayah' => 'required|string',
        ], [
            'nama.required' => 'Nama perusahaan distributor wajib diisi.',
            'kontak.required' => 'Nama kontak resmi wajib diisi.',
            'wilayah.required' => 'Cakupan wilayah wajib dipilih.',
        ]);

        $namaWilayah = match (strtolower(trim($request->wilayah))) {
            'sumbar', 'sumatera barat' => 'Sumatera Barat',
            'riau' => 'Riau',
            default => $request->wilayah,
        };

        $wilayah = Wilayah::firstOrCreate(['nama_wilayah' => $namaWilayah]);

        $distributor = Distributor::create([
            'nama' => $request->nama,
            'id_wilayah' => $wilayah->id_wilayah,
            'kontak' => $request->kontak . ($request->telepon ? ' (' . $request->telepon . ')' : ''),
            'status_aktif' => true,
        ]);

        $this->logAction('Master Distributor', 'Tambah');

        return redirect()->route('admin.distributor')
            ->with('success', "Distributor baru '{$distributor->nama}' berhasil didaftarkan.");
    }

    public function deleteDistributor(Distributor $distributor)
    {
        $nama = $distributor->nama;
        $distributor->delete();

        $this->logAction('Master Distributor', 'Hapus');

        return redirect()->back()->with('success', "Data distributor '{$nama}' berhasil dihapus.");
    }

    // ==================== 3. MASTER PRODUK (SKU) ====================

    public function produk()
    {
        $products = Produk::all();

        return view('pages.admin.produk', compact('products'));
    }

    public function storeProduk(Request $request)
    {
        $request->validate([
            'kode_sku' => 'required|string|max:60|unique:produk,kode_sku',
            'nama_produk' => 'required|string|max:150',
            'harga' => 'required|numeric|min:0',
            'karton_per_kontainer' => 'required|integer|min:1',
            'kategori' => 'nullable|string|max:40',
        ], [
            'kode_sku.required' => 'Kode SKU wajib diisi.',
            'kode_sku.unique' => 'Kode SKU sudah terdaftar.',
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'harga.required' => 'Harga per karton wajib diisi.',
            'karton_per_kontainer.required' => 'Isi karton per kontainer wajib diisi.',
        ]);

        $produk = Produk::create([
            'kode_sku' => strtoupper(trim($request->kode_sku)),
            'nama_produk' => $request->nama_produk,
            'harga' => $request->harga,
            'karton_per_kontainer' => $request->karton_per_kontainer,
            'kategori' => $request->kategori ?: 'Minuman',
        ]);

        $this->logAction('Master Produk (SKU)', 'Tambah');

        return redirect()->route('admin.produk')
            ->with('success', "Produk SKU '{$produk->kode_sku}' berhasil ditambahkan.");
    }

    public function deleteProduk(Produk $produk)
    {
        if (Produk::count() <= 1) {
            return redirect()->back()->with('error', 'Minimal harus ada 1 master produk SKU di sistem.');
        }

        $kode = $produk->kode_sku;
        $produk->delete();

        $this->logAction('Master Produk (SKU)', 'Hapus');

        return redirect()->back()->with('success', "Produk SKU '{$kode}' berhasil dihapus.");
    }

    // ==================== 4. TAHUN FISKAL ====================

    public function tahunFiskal()
    {
        $fiscalPeriods = TahunFiskal::orderBy('tanggal_mulai', 'desc')->get();

        return view('pages.admin.tahun-fiskal', compact('fiscalPeriods'));
    }

    public function storeTahunFiskal(Request $request)
    {
        $request->validate([
            'tahun_mulai' => 'required|integer|min:2020|max:2050',
        ], [
            'tahun_mulai.required' => 'Tahun mulai wajib diisi (contoh: 2028).',
        ]);

        $tahunMulai = (int) $request->tahun_mulai;
        $tglMulai = Carbon::create($tahunMulai, 10, 1)->toDateString();
        $tglSelesai = Carbon::create($tahunMulai + 1, 9, 30)->toDateString();

        $exists = TahunFiskal::where('tanggal_mulai', $tglMulai)->exists();
        if ($exists) {
            return redirect()->back()->with('error', "Periode tahun fiskal {$tahunMulai}/" . ($tahunMulai + 1) . " sudah ada.");
        }

        $period = TahunFiskal::create([
            'tanggal_mulai' => $tglMulai,
            'tanggal_selesai' => $tglSelesai,
            'status' => 'Draf',
        ]);

        $this->logAction('Tahun Fiskal', 'Tambah');

        return redirect()->route('admin.tahun-fiskal')
            ->with('success', "Draf periode tahun fiskal {$tahunMulai}/" . ($tahunMulai + 1) . " berhasil dibuat.");
    }

    public function activateTahunFiskal(TahunFiskal $tahunFiskal)
    {
        TahunFiskal::where('status', 'Aktif')->update(['status' => 'Selesai']);
        $tahunFiskal->update(['status' => 'Aktif']);

        $label = $tahunFiskal->tanggal_mulai->format('Y') . '/' . $tahunFiskal->tanggal_selesai->format('Y');
        $this->logAction('Tahun Fiskal', 'Ubah');

        return redirect()->back()
            ->with('success', "Periode tahun fiskal {$label} sekarang resmi aktif sebagai acuan operasional.");
    }

    // ==================== 5. LOG AKTIVITAS ====================

    public function logAktivitas()
    {
        $logs = LogAktivitas::with('pengguna.peran')
            ->orderBy('waktu', 'desc')
            ->limit(200)
            ->get();

        return view('pages.admin.log-aktivitas', compact('logs'));
    }
}
