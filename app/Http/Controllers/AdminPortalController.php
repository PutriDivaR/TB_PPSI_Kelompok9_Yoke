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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
        $userId = Auth::id();
        abort_unless($userId, 403, 'Sesi administrator tidak ditemukan.');

        LogAktivitas::create([
            'id_pengguna' => $userId,
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
        $distributors = Distributor::with('penggunaRecord')->get();
        $permissions = self::getPermissionsMatrix();

        return view('pages.admin.pengguna', compact('users', 'roles', 'distributors', 'permissions'));
    }

    public function storePengguna(Request $request)
    {
        $nilai = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254', 'unique:pengguna,email'],
            'peran' => ['required', Rule::in(['Admin', 'Distributor', 'Logistik Pusat', 'SAM'])],
            'kata_sandi' => ['nullable', 'string', 'min:4'],
            'id_distributor' => [
                'exclude_unless:peran,Distributor',
                'required',
                'integer',
                'exists:distributor,id_distributor',
                Rule::unique('pengguna', 'id_distributor'),
            ],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email sudah terdaftar.',
            'peran.required' => 'Peran pengguna wajib dipilih.',
            'peran.in' => 'Pilih peran yang tersedia.',
            'id_distributor.required' => 'Pilih perusahaan distributor untuk akun dengan peran Distributor.',
            'id_distributor.exists' => 'Perusahaan distributor yang dipilih tidak ditemukan.',
            'id_distributor.unique' => 'Perusahaan distributor ini sudah memiliki akun. Setiap perusahaan hanya dapat memiliki satu akun distributor.',
        ]);

        $pengguna = DB::transaction(function () use ($nilai) {
            $peran = Peran::firstOrCreate(
                ['nama_peran' => $nilai['peran']],
                ['deskripsi' => 'Peran '.$nilai['peran']]
            );

            $pengguna = Pengguna::create([
                'nama' => $nilai['nama'],
                'email' => $nilai['email'],
                'kata_sandi' => Hash::make($nilai['kata_sandi'] ?? 'password123'),
                'id_peran' => $peran->id_peran,
                'status_aktif' => true,
                'id_distributor' => $nilai['id_distributor'] ?? null,
            ]);

            $this->logAction('Kelola Pengguna', 'Tambah');

            return $pengguna->load('peran');
        });

        return redirect()->route('admin.pengguna')
            ->with('success', "Pengguna baru '{$pengguna->nama}' ({$pengguna->peran->nama_peran}) berhasil ditambahkan.");
    }

    public function editPengguna(Pengguna $pengguna)
    {
        if ($this->isDetachedDistributorAccount($pengguna)) {
            return redirect()->route('admin.pengguna')
                ->with('error', 'Akun distributor lama yang sudah dilepas dari perusahaan merupakan arsip dan tidak dapat diedit.');
        }

        $pengguna->loadMissing(['peran', 'distributor']);
        $isDistributor = $this->isDistributorAccount($pengguna);

        return view('pages.admin.edit-pengguna', compact('pengguna', 'isDistributor'));
    }

    public function updatePengguna(Request $request, Pengguna $pengguna)
    {
        $mode = $request->input('mode', 'edit');
        if (
            $this->isDetachedDistributorAccount($pengguna)
            || ($mode === 'ganti_pemegang' && ! $this->isDistributorAccount($pengguna))
        ) {
            return redirect()->route('admin.pengguna')
                ->with('error', 'Akun ini tidak dapat diperbarui dengan pilihan tersebut.');
        }

        $emailRule = Rule::unique('pengguna', 'email');
        if ($mode === 'edit') {
            $emailRule->ignore($pengguna->id_pengguna, 'id_pengguna');
        }

        $nilai = $request->validate([
            'mode' => ['required', Rule::in(['edit', 'ganti_pemegang'])],
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254', $emailRule],
            'kata_sandi' => [
                'exclude_unless:mode,ganti_pemegang',
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email sudah digunakan akun lain.',
            'kata_sandi.required' => 'Kata sandi pemegang baru wajib diisi.',
            'kata_sandi.min' => 'Kata sandi pemegang baru minimal 8 karakter.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $hasil = DB::transaction(function () use ($nilai, $pengguna) {
            $akunLama = Pengguna::query()
                ->with(['peran', 'distributor'])
                ->lockForUpdate()
                ->findOrFail($pengguna->id_pengguna);

            if (
                $this->isDetachedDistributorAccount($akunLama)
                || ($nilai['mode'] === 'ganti_pemegang' && ! $this->isDistributorAccount($akunLama))
            ) {
                return null;
            }

            if ($nilai['mode'] === 'ganti_pemegang') {
                $distributorId = $akunLama->id_distributor;
                $namaLama = $akunLama->nama;
                $namaDistributor = $akunLama->distributor->nama;

                $akunLama->update([
                    'status_aktif' => false,
                    'id_distributor' => null,
                ]);

                $akunBaru = Pengguna::create([
                    'nama' => $nilai['nama'],
                    'email' => $nilai['email'],
                    'kata_sandi' => Hash::make($nilai['kata_sandi']),
                    'id_peran' => $akunLama->id_peran,
                    'status_aktif' => true,
                    'id_distributor' => $distributorId,
                ]);

                $this->logAction('Kelola Pengguna', 'Ganti Akun');

                return [
                    'mode' => 'ganti_pemegang',
                    'namaLama' => $namaLama,
                    'namaBaru' => $akunBaru->nama,
                    'namaDistributor' => $namaDistributor,
                ];
            }

            $akunLama->update([
                'nama' => $nilai['nama'],
                'email' => $nilai['email'],
            ]);
            $this->logAction('Kelola Pengguna', 'Ubah');

            return ['mode' => 'edit', 'nama' => $akunLama->nama];
        });

        if ($hasil === null) {
            return redirect()->route('admin.pengguna')
                ->with('error', 'Akun sudah berubah. Muat ulang halaman sebelum mencoba lagi.');
        }

        if ($hasil['mode'] === 'ganti_pemegang') {
            return redirect()->route('admin.pengguna')->with(
                'success',
                "Akun '{$hasil['namaLama']}' diarsipkan. Akun '{$hasil['namaBaru']}' kini terhubung ke {$hasil['namaDistributor']}; riwayat lama tetap atas nama pemegang sebelumnya."
            );
        }

        return redirect()->route('admin.pengguna')
            ->with('success', "Profil akun '{$hasil['nama']}' berhasil diperbarui.");
    }

    public function togglePengguna(Pengguna $pengguna)
    {
        $pengguna->loadMissing('peran');
        $newStatus = ! $pengguna->status_aktif;
        if ($newStatus && $this->isDetachedDistributorAccount($pengguna)) {
            return redirect()->back()
                ->with('error', 'Akun distributor yang sudah diarsipkan tidak dapat diaktifkan kembali. Buat akun baru untuk pemegang berikutnya.');
        }

        if (! $newStatus && (int) Auth::id() === (int) $pengguna->id_pengguna) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun administrator yang sedang digunakan.');
        }

        DB::transaction(function () use ($pengguna, $newStatus) {
            $pengguna->update(['status_aktif' => $newStatus]);
            $this->logAction('Kelola Pengguna', 'Ubah');
        });

        $statusLabel = $newStatus ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Status akun '{$pengguna->nama}' berhasil {$statusLabel}.");
    }

    public function deletePengguna(Pengguna $pengguna)
    {
        if ((int) Auth::id() === (int) $pengguna->id_pengguna) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($this->isDetachedDistributorAccount($pengguna)) {
            return redirect()->back()
                ->with('error', 'Akun distributor arsip dipertahankan agar riwayat pemegang sebelumnya tetap utuh.');
        }

        if (
            $pengguna->logAktivitasRecords()->exists()
            || $pengguna->forecastRecordsAsPenyetuju()->exists()
            || $pengguna->purchaseOrderRecordsAsPenyetuju()->exists()
            || $pengguna->beritaAcaraKerusakanRecordsAsPengesah()->exists()
        ) {
            return redirect()->back()->with('error', "Akun '{$pengguna->nama}' sudah tercatat dalam riwayat aktivitas atau transaksi. Nonaktifkan akun agar riwayat tetap utuh.");
        }

        $nama = $pengguna->nama;
        DB::transaction(function () use ($pengguna) {
            $pengguna->delete();
            $this->logAction('Kelola Pengguna', 'Hapus');
        });

        return redirect()->back()->with('success', "Akun '{$nama}' berhasil dihapus.");
    }

    private function isDistributorAccount(Pengguna $pengguna): bool
    {
        $pengguna->loadMissing('peran');

        return $pengguna->id_distributor !== null
            && $pengguna->peran?->nama_peran === 'Distributor';
    }

    private function isDetachedDistributorAccount(Pengguna $pengguna): bool
    {
        $pengguna->loadMissing('peran');

        return $pengguna->id_distributor === null
            && $pengguna->peran?->nama_peran === 'Distributor';
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
        $nilai = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kontak' => ['required', 'string', 'max:60'],
            'telepon' => ['nullable', 'string', 'max:60'],
            'wilayah' => ['required', 'string', Rule::in(['Sumbar', 'Riau'])],
        ], [
            'nama.required' => 'Nama perusahaan distributor wajib diisi.',
            'kontak.required' => 'Nama kontak resmi wajib diisi.',
            'wilayah.required' => 'Cakupan wilayah wajib dipilih.',
            'wilayah.in' => 'Pilih cakupan wilayah yang tersedia.',
        ]);

        $namaWilayah = match (strtolower(trim($nilai['wilayah']))) {
            'sumbar', 'sumatera barat' => 'Sumatera Barat',
            'riau' => 'Riau',
            default => $nilai['wilayah'],
        };

        $distributor = DB::transaction(function () use ($nilai, $namaWilayah) {
            $wilayah = Wilayah::firstOrCreate(['nama_wilayah' => $namaWilayah]);

            $distributor = Distributor::create([
                'nama' => $nilai['nama'],
                'id_wilayah' => $wilayah->id_wilayah,
                'kontak' => $nilai['kontak'].(! empty($nilai['telepon'] ?? null) ? ' ('.$nilai['telepon'].')' : ''),
                'status_aktif' => true,
            ]);

            $this->logAction('Master Distributor', 'Tambah');

            return $distributor;
        });

        return redirect()->route('admin.distributor')
            ->with('success', "Distributor baru '{$distributor->nama}' berhasil didaftarkan.");
    }

    public function deleteDistributor(Distributor $distributor)
    {
        if (
            $distributor->penggunaRecord()->exists()
            || $distributor->forecastRecords()->exists()
            || $distributor->sellOutRecords()->exists()
            || $distributor->stokGudangRecords()->exists()
        ) {
            return redirect()->back()->with('error', "Distributor '{$distributor->nama}' masih digunakan oleh akun atau data operasional. Nonaktifkan, bukan hapus, agar riwayat tetap utuh.");
        }

        $nama = $distributor->nama;
        DB::transaction(function () use ($distributor) {
            $distributor->delete();
            $this->logAction('Master Distributor', 'Hapus');
        });

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
        $nilai = $request->validate([
            'kode_sku' => ['required', 'string', 'max:60', 'unique:produk,kode_sku'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'harga' => ['required', 'numeric', 'min:0'],
            'karton_per_kontainer' => ['required', 'integer', 'min:1'],
            'kategori' => ['nullable', 'string', 'max:40'],
        ], [
            'kode_sku.required' => 'Kode SKU wajib diisi.',
            'kode_sku.unique' => 'Kode SKU sudah terdaftar.',
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'harga.required' => 'Harga per karton wajib diisi.',
            'karton_per_kontainer.required' => 'Isi karton per kontainer wajib diisi.',
        ]);

        $produk = DB::transaction(function () use ($nilai) {
            $produk = Produk::create([
                'kode_sku' => strtoupper(trim($nilai['kode_sku'])),
                'nama_produk' => $nilai['nama_produk'],
                'harga' => $nilai['harga'],
                'karton_per_kontainer' => $nilai['karton_per_kontainer'],
                'kategori' => $nilai['kategori'] ?? 'Minuman',
            ]);

            $this->logAction('Master Produk (SKU)', 'Tambah');

            return $produk;
        });

        return redirect()->route('admin.produk')
            ->with('success', "Produk SKU '{$produk->kode_sku}' berhasil ditambahkan.");
    }

    public function deleteProduk(Produk $produk)
    {
        if (
            $produk->detailForecastRecords()->exists()
            || $produk->detailPoRecords()->exists()
            || $produk->sellOutRecords()->exists()
            || $produk->stokGudangRecords()->exists()
        ) {
            return redirect()->back()->with('error', "SKU '{$produk->kode_sku}' sudah digunakan dalam data operasional. Data historis dipertahankan, jadi SKU tidak dapat dihapus.");
        }

        if (Produk::count() <= 1) {
            return redirect()->back()->with('error', 'Minimal harus ada 1 master produk SKU di sistem.');
        }

        $kode = $produk->kode_sku;
        DB::transaction(function () use ($produk) {
            $produk->delete();
            $this->logAction('Master Produk (SKU)', 'Hapus');
        });

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
        $nilai = $request->validate([
            'tahun_mulai' => ['required', 'integer', 'min:2020', 'max:2050'],
        ], [
            'tahun_mulai.required' => 'Tahun mulai wajib diisi (contoh: 2028).',
        ]);

        $tahunMulai = (int) $nilai['tahun_mulai'];
        $tglMulai = Carbon::create($tahunMulai, 10, 1)->toDateString();
        $tglSelesai = Carbon::create($tahunMulai + 1, 9, 30)->toDateString();

        $exists = TahunFiskal::where('tanggal_mulai', $tglMulai)->exists();
        if ($exists) {
            return redirect()->back()->withInput()->with('error', "Periode tahun fiskal {$tahunMulai}/".($tahunMulai + 1).' sudah ada.');
        }

        DB::transaction(function () use ($tglMulai, $tglSelesai) {
            TahunFiskal::create([
                'tanggal_mulai' => $tglMulai,
                'tanggal_selesai' => $tglSelesai,
                'status' => 'Draf',
            ]);

            $this->logAction('Tahun Fiskal', 'Tambah');
        });

        return redirect()->route('admin.tahun-fiskal')
            ->with('success', "Draf periode tahun fiskal {$tahunMulai}/".($tahunMulai + 1).' berhasil dibuat.');
    }

    public function activateTahunFiskal(TahunFiskal $tahunFiskal)
    {
        $label = $tahunFiskal->tanggal_mulai->format('Y').'/'.$tahunFiskal->tanggal_selesai->format('Y');
        DB::transaction(function () use ($tahunFiskal) {
            TahunFiskal::where('status', 'Aktif')->update(['status' => 'Selesai']);
            $tahunFiskal->update(['status' => 'Aktif']);
            $this->logAction('Tahun Fiskal', 'Ubah');
        });

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
