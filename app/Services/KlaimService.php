<?php

namespace App\Services;

use App\Models\BeritaAcaraKerusakan;
use App\Models\DetailKlaim;
use App\Models\DetailPenerimaan;
use App\Models\FotoBukti;
use App\Models\Klaim;
use App\Models\LogAktivitas;
use App\Models\PenerimaanBarang;
use App\Models\Pengguna;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Aturan alur klaim retur, dipakai bersama oleh halaman web dan API v1.
 *
 * Alur: Distributor ajukan -> SAM verifikasi fisik & sahkan BAK -> Logistik selesaikan.
 * SAM dapat mengembalikan ke distributor; Logistik dapat mengembalikan ke SAM.
 */
class KlaimService
{
    public const STATUS_MENUNGGU_SAM = 'Menunggu Verifikasi SAM';

    public const STATUS_DI_LOGISTIK = 'Diteruskan ke Logistik';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUS_DIKEMBALIKAN = 'Dikembalikan';

    public const VERIFIKASI_MENUNGGU = 'Menunggu Verifikasi';

    public const VERIFIKASI_SESUAI = 'Disetujui';

    public const VERIFIKASI_TIDAK_SESUAI = 'Ditolak';

    public const BAK_DISAHKAN = 'Disahkan';

    public const BAK_DIBATALKAN = 'Dibatalkan';

    /** Distributor menyortir barang sekitar satu minggu setelah barang tiba di gudang. */
    public const HARI_PENYORTIRAN = 7;

    /**
     * Jenis kerusakan beserta penanggung dan alasannya.
     *
     * Daftar jenis ini masih sementara dan menunggu konfirmasi dari perusahaan.
     * shortcut: Penyok/Bocor/Basah dianggap kerusakan Barang Dalam Perjalanan karena jenisnya saja
     * tidak membedakan kerusakan penyimpanan gudang; perlu penyebab kerusakan jika aturan ini dipastikan.
     *
     * @var array<string, array{penanggung: string, alasan: string}>
     */
    public const JENIS_KERUSAKAN = [
        'Penyok' => ['penanggung' => 'Perusahaan', 'alasan' => 'Kerusakan fisik yang ditemukan saat penyortiran kedatangan dianggap terjadi selama Barang Dalam Perjalanan, dan dipastikan SAM saat pemeriksaan fisik.'],
        'Bocor' => ['penanggung' => 'Perusahaan', 'alasan' => 'Kerusakan fisik yang ditemukan saat penyortiran kedatangan dianggap terjadi selama Barang Dalam Perjalanan, dan dipastikan SAM saat pemeriksaan fisik.'],
        'Basah' => ['penanggung' => 'Perusahaan', 'alasan' => 'Kerusakan fisik yang ditemukan saat penyortiran kedatangan dianggap terjadi selama Barang Dalam Perjalanan, dan dipastikan SAM saat pemeriksaan fisik.'],
        'Kurang Jumlah' => ['penanggung' => 'Perusahaan', 'alasan' => 'Barang yang kurang terhadap invoice menjadi tanggungan perusahaan.'],
        'Kedaluwarsa' => ['penanggung' => 'Distributor', 'alasan' => 'Retur kedaluwarsa menjadi tanggungan distributor.'],
    ];

    /**
     * Bentuk penyelesaian klaim oleh Logistik Pusat.
     *
     * Daftar ini masih sementara dan menunggu konfirmasi dari perusahaan.
     *
     * @var list<string>
     */
    public const BENTUK_PENYELESAIAN = ['Ganti Rugi', 'Penyesuaian Catatan Kewajiban'];

    /** Jumlah karton yang paling banyak bisa diklaim dari satu baris penerimaan: barang kurang + barang tidak layak jual. */
    public static function maksimumKlaim(DetailPenerimaan $baris): int
    {
        return max(0, (int) $baris->detailPengiriman->jumlah_dikirim - (int) $baris->jumlah_baik);
    }

    /**
     * Ajukan klaim baru, atau ajukan ulang klaim yang dikembalikan untuk kedatangan yang sama.
     *
     * @param  list<array{id_detail_penerimaan: int, jenis_kerusakan: string, jumlah: int, foto?: list<UploadedFile>}>  $baris
     */
    public function ajukan(int $idPenerimaan, int $idDistributor, array $baris, ?Pengguna $pelaku): Klaim
    {
        $berkasLama = [];
        $klaim = DB::transaction(function () use ($idPenerimaan, $idDistributor, $baris, $pelaku, &$berkasLama) {
            $penerimaan = PenerimaanBarang::query()->whereKey($idPenerimaan)->lockForUpdate()
                ->with(['pengiriman.po.forecast', 'detailPenerimaanRecordsAsPenerimaan.detailPengiriman.detailPo.produk'])
                ->firstOrFail();
            abort_unless((int) $penerimaan->pengiriman->po->forecast->id_distributor === $idDistributor, 403, 'Penerimaan bukan milik perusahaan Anda.');

            $klaim = Klaim::query()->where('id_penerimaan', $idPenerimaan)->lockForUpdate()->with('detailKlaimRecords.fotoBuktiRecords')->first();
            if ($klaim && $klaim->status !== self::STATUS_DIKEMBALIKAN) {
                throw ValidationException::withMessages(['id_penerimaan' => 'Kedatangan ini sudah memiliki klaim yang sedang diproses.']);
            }
            $this->pastikanJumlahDalamBatas($penerimaan, $baris);
            $diajukanUlang = $klaim !== null;

            if ($klaim) {
                foreach ($klaim->detailKlaimRecords as $detail) {
                    $berkasLama = [...$berkasLama, ...$detail->fotoBuktiRecords->pluck('path_berkas')->all()];
                    $detail->fotoBuktiRecords()->delete();
                    $detail->delete();
                }
                // Catatan dihapus agar catatan yang tersisa di antrean SAM selalu berasal dari Logistik.
                $klaim->update(['status' => self::STATUS_MENUNGGU_SAM, 'tanggal_pengajuan' => now(), 'catatan_pengembalian' => null]);
            } else {
                $klaim = Klaim::query()->create([
                    'id_penerimaan' => $idPenerimaan,
                    'tanggal_pengajuan' => now(),
                    'status' => self::STATUS_MENUNGGU_SAM,
                ]);
            }

            foreach ($baris as $item) {
                $detail = DetailKlaim::query()->create([
                    'id_klaim' => $klaim->id_klaim,
                    'id_detail_penerimaan' => $item['id_detail_penerimaan'],
                    'jenis_kerusakan' => $item['jenis_kerusakan'],
                    'jumlah' => $item['jumlah'],
                    'penanggung_jawab' => self::JENIS_KERUSAKAN[$item['jenis_kerusakan']]['penanggung'],
                    'status_verifikasi' => self::VERIFIKASI_MENUNGGU,
                ]);
                $this->simpanFoto($detail, $item['foto'] ?? [], 'Distributor');
            }
            $this->catat($pelaku, ($diajukanUlang ? 'Ajukan ulang klaim #' : 'Ajukan klaim #').$klaim->id_klaim);

            return $klaim;
        });
        Storage::disk('local')->delete($berkasLama);

        return $klaim;
    }

    public function jadwalkanKunjungan(Klaim $klaim, string $tanggal, Pengguna $sam): void
    {
        DB::transaction(function () use ($klaim, $tanggal, $sam) {
            $this->kunciDenganStatus($klaim, self::STATUS_MENUNGGU_SAM)->update(['jadwal_kunjungan' => $tanggal]);
            $this->catat($sam, 'Jadwalkan kunjungan klaim #'.$klaim->id_klaim);
        });
    }

    /**
     * Simpan hasil pemeriksaan fisik SAM untuk setiap baris klaim.
     *
     * @param  array<int, array{status_verifikasi: string, jumlah_terverifikasi?: int|null, catatan?: string|null}>  $hasil  Kunci: id_detail_klaim.
     * @param  array<int, list<UploadedFile>>  $fotoLokasi  Kunci: id_detail_klaim.
     */
    public function simpanVerifikasi(Klaim $klaim, array $hasil, array $fotoLokasi, Pengguna $sam): void
    {
        DB::transaction(function () use ($klaim, $hasil, $fotoLokasi, $sam) {
            $terkunci = $this->kunciDenganStatus($klaim, self::STATUS_MENUNGGU_SAM);
            $details = $terkunci->detailKlaimRecords()->get()->keyBy('id_detail_klaim');
            if (collect(array_keys($hasil))->map(fn ($id) => (int) $id)->sort()->values()->all() !== $details->keys()->sort()->values()->all()) {
                throw ValidationException::withMessages(['hasil' => 'Setiap baris klaim harus diperiksa.']);
            }

            foreach ($hasil as $id => $item) {
                $detail = $details[(int) $id];
                $sesuai = $item['status_verifikasi'] === self::VERIFIKASI_SESUAI;
                $jumlah = $sesuai ? $detail->jumlah : (isset($item['jumlah_terverifikasi']) ? (int) $item['jumlah_terverifikasi'] : null);
                if ($jumlah === null || $jumlah < 0 || $jumlah > $detail->jumlah) {
                    throw ValidationException::withMessages(["hasil.$id.jumlah_terverifikasi" => "Jumlah terverifikasi harus antara 0 dan {$detail->jumlah} karton."]);
                }
                $detail->update([
                    'status_verifikasi' => $sesuai ? self::VERIFIKASI_SESUAI : self::VERIFIKASI_TIDAK_SESUAI,
                    'jumlah_terverifikasi' => $jumlah,
                    'catatan_verifikasi' => filled($item['catatan'] ?? null) ? $item['catatan'] : null,
                ]);
                $this->simpanFoto($detail, $fotoLokasi[(int) $id] ?? [], 'SAM');
            }
            $this->catat($sam, 'Verifikasi fisik klaim #'.$klaim->id_klaim);
        });
    }

    /** Sahkan Berita Acara Kerusakan dan teruskan klaim ke Logistik Pusat. */
    public function sahkan(Klaim $klaim, string $tandaTanganPng, Pengguna $sam): BeritaAcaraKerusakan
    {
        $berkasLama = null;
        $bak = DB::transaction(function () use ($klaim, $tandaTanganPng, $sam, &$berkasLama) {
            $terkunci = $this->kunciDenganStatus($klaim, self::STATUS_MENUNGGU_SAM);
            if ($terkunci->detailKlaimRecords()->where('status_verifikasi', self::VERIFIKASI_MENUNGGU)->exists()) {
                throw ValidationException::withMessages(['tanda_tangan' => 'Selesaikan verifikasi fisik semua baris sebelum mengesahkan berita acara.']);
            }
            $path = 'klaim/tanda-tangan/'.Str::ulid().'.png';
            abort_unless(Storage::disk('local')->put($path, $tandaTanganPng), 500, 'Berkas tanda tangan gagal disimpan.');

            $nilai = ['id_pengesah' => $sam->id_pengguna, 'tanggal_pengesahan' => now(), 'tanda_tangan_digital' => $path, 'status' => self::BAK_DISAHKAN];
            $bak = BeritaAcaraKerusakan::query()->where('id_klaim', $terkunci->id_klaim)->lockForUpdate()->first();
            if ($bak) {
                // Klaim yang dikembalikan Logistik disahkan ulang pada dokumen yang sama; nomornya tetap.
                $berkasLama = $bak->tanda_tangan_digital;
                $bak->update($nilai);
            } else {
                $bak = BeritaAcaraKerusakan::query()->create($nilai + ['id_klaim' => $terkunci->id_klaim, 'nomor_bak' => 'TMP-'.Str::ulid()]);
                $bak->update(['nomor_bak' => self::kodeBak($bak)]);
            }
            $terkunci->update(['status' => self::STATUS_DI_LOGISTIK, 'catatan_pengembalian' => null]);
            $this->catat($sam, 'Sahkan '.$bak->nomor_bak);

            return $bak;
        });
        if ($berkasLama) {
            Storage::disk('local')->delete($berkasLama);
        }

        return $bak;
    }

    public function kembalikanKeDistributor(Klaim $klaim, string $catatan, Pengguna $sam): void
    {
        DB::transaction(function () use ($klaim, $catatan, $sam) {
            $this->kunciDenganStatus($klaim, self::STATUS_MENUNGGU_SAM)
                ->update(['status' => self::STATUS_DIKEMBALIKAN, 'catatan_pengembalian' => $catatan]);
            $this->catat($sam, 'Kembalikan klaim #'.$klaim->id_klaim.' ke distributor');
        });
    }

    public function selesaikan(Klaim $klaim, string $bentuk, ?string $catatan, Pengguna $logistik): void
    {
        DB::transaction(function () use ($klaim, $bentuk, $catatan, $logistik) {
            $this->kunciDenganStatus($klaim, self::STATUS_DI_LOGISTIK)->update([
                'status' => self::STATUS_SELESAI,
                'tanggal_penyelesaian' => now(),
                'bentuk_penyelesaian' => $bentuk,
                'catatan_penyelesaian' => filled($catatan) ? $catatan : null,
            ]);
            $this->catat($logistik, 'Selesaikan klaim #'.$klaim->id_klaim);
        });
    }

    public function kembalikanKeSam(Klaim $klaim, string $catatan, Pengguna $logistik): void
    {
        DB::transaction(function () use ($klaim, $catatan, $logistik) {
            $terkunci = $this->kunciDenganStatus($klaim, self::STATUS_DI_LOGISTIK);
            $terkunci->update(['status' => self::STATUS_MENUNGGU_SAM, 'catatan_pengembalian' => $catatan]);
            // Hasil pemeriksaan lama tetap tersimpan sebagai isian awal, tetapi SAM wajib memeriksa ulang.
            $terkunci->detailKlaimRecords()->update(['status_verifikasi' => self::VERIFIKASI_MENUNGGU]);
            BeritaAcaraKerusakan::query()->where('id_klaim', $terkunci->id_klaim)->update(['status' => self::BAK_DIBATALKAN]);
            $this->catat($logistik, 'Kembalikan klaim #'.$klaim->id_klaim.' ke SAM');
        });
    }

    public static function kodeKlaim(Klaim $klaim): string
    {
        return 'KLM-'.str_pad((string) $klaim->id_klaim, 5, '0', STR_PAD_LEFT);
    }

    public static function kodeBak(BeritaAcaraKerusakan $bak): string
    {
        return 'BAK-'.str_pad((string) $bak->id_bak, 5, '0', STR_PAD_LEFT);
    }

    /** @param  list<array{id_detail_penerimaan: int, jenis_kerusakan: string, jumlah: int}>  $baris */
    private function pastikanJumlahDalamBatas(PenerimaanBarang $penerimaan, array $baris): void
    {
        $barisPenerimaan = $penerimaan->detailPenerimaanRecordsAsPenerimaan->keyBy('id_detail_penerimaan');
        $total = [];
        foreach ($baris as $i => $item) {
            if (! $barisPenerimaan->has($item['id_detail_penerimaan'])) {
                throw ValidationException::withMessages(["baris.$i.id_detail_penerimaan" => 'Produk harus berasal dari kedatangan yang dipilih.']);
            }
            $total[$item['id_detail_penerimaan']] = ($total[$item['id_detail_penerimaan']] ?? 0) + (int) $item['jumlah'];
        }
        foreach ($total as $id => $jumlah) {
            $maksimum = self::maksimumKlaim($barisPenerimaan[$id]);
            if ($jumlah > $maksimum) {
                $produk = $barisPenerimaan[$id]->detailPengiriman->detailPo->produk->nama_produk;
                throw ValidationException::withMessages(['baris' => "Klaim {$produk} ({$jumlah} karton) melebihi barang kurang atau rusak pada penerimaan ({$maksimum} karton)."]);
            }
        }
    }

    private function kunciDenganStatus(Klaim $klaim, string $status): Klaim
    {
        $terkunci = Klaim::query()->whereKey($klaim->id_klaim)->lockForUpdate()->firstOrFail();
        if ($terkunci->status !== $status) {
            throw ValidationException::withMessages(['status' => "Klaim ini berstatus {$terkunci->status}, bukan {$status}."]);
        }

        return $terkunci;
    }

    /** @param  list<UploadedFile>  $berkas */
    private function simpanFoto(DetailKlaim $detail, array $berkas, string $sumber): void
    {
        foreach ($berkas as $foto) {
            $path = $foto->store('klaim/bukti', 'local');
            abort_unless($path, 500, 'Berkas foto bukti gagal disimpan.');
            FotoBukti::query()->create([
                'id_detail_klaim' => $detail->id_detail_klaim,
                'path_berkas' => $path,
                'tanggal_unggah' => now(),
                'sumber' => $sumber,
            ]);
        }
    }

    private function catat(?Pengguna $pelaku, string $aksi): void
    {
        if (! $pelaku) {
            return;
        }
        LogAktivitas::query()->create([
            'id_pengguna' => $pelaku->id_pengguna,
            'modul' => 'Klaim',
            'aksi' => Str::limit($aksi, 60, ''),
            'waktu' => now(),
        ]);
    }
}
