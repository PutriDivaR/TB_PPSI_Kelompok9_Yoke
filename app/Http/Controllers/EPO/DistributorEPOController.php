<?php

namespace App\Http\Controllers\EPO;

use App\Http\Controllers\Controller;
use App\Models\DetailForecast;
use App\Models\DetailPo;
use App\Models\Distributor;
use App\Models\Forecast;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use App\Models\SellOut;
use App\Models\StokGudang;
use App\Models\TahunFiskal;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * E-PO sisi Distributor.
 *
 * SATUAN: seluruh angka forecast, kuota, dan pesanan di modul ini adalah KARTON.
 * Kolom detail_forecast.jumlah_kontainer, detail_forecast.jumlah_disetujui, dan
 * detail_po.jumlah_kontainer menyimpan jumlah KARTON (nama kolom lama dari ERD).
 * Estimasi kontainer hanya dihitung untuk tampilan: total karton / KARTON_PER_KONTAINER.
 */
class DistributorEPOController extends Controller implements HasMiddleware
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_DIAJUKAN = 'Diajukan';

    public const STATUS_DIPROSES = 'Diproses';

    public const STATUS_DISETUJUI = 'Disetujui';

    /** Isi 1 kontainer (karton). Dipakai untuk "Estimasi Kontainer". */
    private const KARTON_PER_KONTAINER = 2000;

    /** Minimum total pesanan 1 E-PO (karton) = 1 kontainer. Ubah di sini bila aturan berubah. */
    private const MINIMUM_ORDER_KARTON = 2000;

    /** Kapasitas produksi pabrik per bulan (karton). */
    private const KAPASITAS_BULANAN_KARTON = 30000;

    private const LEAD_TIME_MIN_MONTHS = 2;

    private const LEAD_TIME_MAX_MONTHS = 3;

    private const ROLLING_PLANNING_MAX_MONTHS = 7;

    private const CUTOFF_DAY = 15;

    private const BULAN_RIWAYAT_PENJUALAN = 3;

    private const STOK_PENGAMAN = 0.10;

    private const LEAD_TIME_MIN_DAYS = 60;

    private const LEAD_TIME_MAX_DAYS = 75;

    private const AWALAN_PO = 'PO-YFI';

    private const PABRIK = [
        'nama' => 'PT Yoke Food Industries Indonesia',
        'alamat' => 'Kawasan Industri Jababeka Blok F-12, Cikarang, Bekasi, Jawa Barat 17530',
        'plant' => 'Plant Cikarang #02',
        'termin' => '30 Hari Kalender - Bank Transfer',
    ];

    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul E-PO belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    // ------------------------------------------------------------------
    // LAYAR 1 - Hitung Forecast (beranda distributor)
    // ------------------------------------------------------------------
    public function hitungForecast(Request $request): View
    {
        $konteks = $this->konteksDistributor($request);
        if (! $konteks) {
            return view('pages.e-po.distributor.hitung-forecast', $this->helperTampilan());
        }
        $request->validate(['periode' => ['nullable', 'date_format:Y-m']]);
        $distributor = $konteks['distributor'];
        $periode = $this->periodeTarget($request->query('periode'));
        $tenggat = $this->tenggatPengajuan($periode);
        $bisaMengajukanPeriode = $periode->greaterThanOrEqualTo($this->periodeMinimumForecast())
            && $periode->lessThanOrEqualTo($this->periodeMaximumForecast());

        $forecastTarget = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->whereDate('periode', $periode->toDateString())->with('purchaseOrderRecords')->first();
        $riwayat = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->withSum('detailForecastRecords as total_usulan', 'jumlah_kontainer')
            ->withSum('detailForecastRecords as total_disetujui', 'jumlah_disetujui')
            ->with('purchaseOrderRecords')->orderByDesc('periode')->limit(8)->get();
        $riwayatEpo = PurchaseOrder::query()
            ->whereHas('forecast', fn ($query) => $query->where('id_distributor', $distributor->id_distributor))
            ->with(['forecast', 'detailPoRecordsAsPo.produk'])
            ->orderByRaw("CASE status WHEN 'Diajukan' THEN 0 WHEN 'Diproses' THEN 1 WHEN 'Disetujui' THEN 2 ELSE 3 END")
            ->orderByDesc('tanggal_pengajuan')
            ->limit(20)
            ->get()
            ->each(function (PurchaseOrder $po): void {
                $po->setAttribute('total_karton', (int) $po->detailPoRecordsAsPo->sum('jumlah_kontainer'));
                $po->setAttribute('total_nilai', (float) $po->detailPoRecordsAsPo->sum(
                    fn (DetailPo $detail) => $detail->jumlah_kontainer * (float) $detail->produk->harga
                ));
            });

        $terakhirBerPo = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->where('status', self::STATUS_DISETUJUI)
            ->whereHas('purchaseOrderRecords', fn ($q) => $q->where('status', self::STATUS_DISETUJUI))
            ->with(['detailForecastRecords', 'purchaseOrderRecords.detailPoRecordsAsPo'])
            ->orderByDesc('periode')->first();
        $realisasi = null;
        if ($terakhirBerPo) {
            $kuota = (int) $terakhirBerPo->detailForecastRecords->sum('jumlah_disetujui');
            $dipesan = (int) $terakhirBerPo->purchaseOrderRecords->where('status', self::STATUS_DISETUJUI)
                ->sum(fn (PurchaseOrder $po) => $po->detailPoRecordsAsPo->sum('jumlah_kontainer'));
            $realisasi = ['periode' => $terakhirBerPo->periode, 'kuota' => $kuota, 'dipesan' => $dipesan,
                'persen' => $kuota > 0 ? (int) round($dipesan / $kuota * 100) : 0];
        }

        $periodePilihan = collect(range(-6, self::ROLLING_PLANNING_MAX_MONTHS))
            ->map(fn (int $offset) => CarbonImmutable::now()->startOfMonth()->addMonthsNoOverflow($offset))
            ->filter(fn (CarbonImmutable $kandidat) => $this->tahunFiskalUntuk($kandidat))
            ->values();

        return view('pages.e-po.distributor.hitung-forecast', $konteks + $this->helperTampilan() + [
            'periode' => $periode,
            'tenggat' => $tenggat,
            'hariSisa' => (int) now()->startOfDay()->diffInDays($tenggat->startOfDay(), false),
            'forecastTarget' => $forecastTarget,
            'riwayat' => $riwayat,
            'riwayatEpo' => $riwayatEpo,
            'tahunFiskal' => $this->tahunFiskalUntuk($periode),
            'siapInputEpo' => $this->forecastSiapPo($distributor, $periode),
            'realisasi' => $realisasi,
            'periodePilihan' => $periodePilihan,
            'kartonPerKontainer' => self::KARTON_PER_KONTAINER,
            'bisaMengajukanPeriode' => $bisaMengajukanPeriode,
            'periodeMinimumForecast' => $this->periodeMinimumForecast(),
            'periodeMaximumForecast' => $this->periodeMaximumForecast(),
        ]);
    }

    // ------------------------------------------------------------------
    // LAYAR 2 - Ajukan Forecast per periode bulan
    // ------------------------------------------------------------------
    public function ajukanForecast(Request $request): View
    {
        $konteks = $this->konteksDistributor($request);
        if (! $konteks) {
            return view('pages.e-po.distributor.hitung-forecast', $this->helperTampilan());
        }
        $request->validate(['periode' => ['nullable', 'date_format:Y-m']]);
        $distributor = $konteks['distributor'];
        $periode = $this->periodeTarget($request->query('periode'));

        $forecast = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->whereDate('periode', $periode->toDateString())->with(['detailForecastRecords', 'purchaseOrderRecords'])->first();
        $bisaMengajukanPeriode = $periode->greaterThanOrEqualTo($this->periodeMinimumForecast())
            && $periode->lessThanOrEqualTo($this->periodeMaximumForecast());
        $editable = $bisaMengajukanPeriode && (! $forecast || $forecast->status === self::STATUS_DRAFT);
        $tersimpan = $forecast?->detailForecastRecords->keyBy('id_produk') ?? collect();
        $usulanLama = is_array(old('usulan')) ? old('usulan') : [];
        $rekomendasi = $this->rekomendasi($distributor->id_distributor, $periode);

        // Semua SKU aktif ditampilkan; yang tidak dibutuhkan cukup dibiarkan 0.
        $baris = Produk::query()->orderBy('kode_sku')->get()->map(function (Produk $produk) use ($rekomendasi, $tersimpan, $usulanLama) {
            $rek = $rekomendasi[$produk->id_produk];

            return [
                'id' => $produk->id_produk, 'kode' => $produk->kode_sku, 'nama' => $produk->nama_produk,
                'kategori' => $produk->kategori, 'harga' => (float) $produk->harga,
                'rata_rata' => $rek['rata_rata'], 'stok' => $rek['stok_layak_jual'], 'rekomendasi' => $rek['karton'],
                'usulan' => (int) ($usulanLama[$produk->id_produk] ?? $tersimpan[$produk->id_produk]->jumlah_kontainer ?? 0),
            ];
        })->values();

        return view('pages.e-po.distributor.ajukan-forecast', $konteks + $this->helperTampilan() + [
            'periode' => $periode, 'tenggat' => $this->tenggatPengajuan($periode),
            'forecast' => $forecast, 'editable' => $editable, 'baris' => $baris,
            'bisaDibatalkan' => $forecast && in_array($forecast->status, [self::STATUS_DRAFT, self::STATUS_DIAJUKAN], true) && $forecast->purchaseOrderRecords->isEmpty(),
            'kapasitas' => self::KAPASITAS_BULANAN_KARTON, 'jumlahBulan' => self::BULAN_RIWAYAT_PENJUALAN,
            'kartonPerKontainer' => self::KARTON_PER_KONTAINER,
            'bisaMengajukanPeriode' => $bisaMengajukanPeriode,
        ]);
    }

    public function submitForecast(Request $request): RedirectResponse
    {
        $nilai = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'aksi' => ['required', 'in:draft,kirim'],
            'usulan' => ['nullable', 'array'],
            'usulan.*' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'konfirmasi' => ['nullable', 'required_if:aksi,kirim', 'accepted'],
        ], [
            'konfirmasi.accepted' => 'Centang konfirmasi kebenaran data cabang sebelum mengajukan forecast.',
            'konfirmasi.required_if' => 'Centang konfirmasi kebenaran data cabang sebelum mengajukan forecast.',
            'usulan.*.integer' => 'Jumlah usulan harus berupa bilangan bulat (karton).',
        ]);
        $distributor = $this->distributorAktif($request);
        abort_unless($distributor?->penggunaRecord, 422, 'Distributor belum memiliki akun pengguna.');

        $periode = $this->periodeTarget($nilai['periode']);
        if ($periode->lessThan($this->periodeMinimumForecast()) || $periode->greaterThan($this->periodeMaximumForecast())) {
            throw ValidationException::withMessages(['periode' => 'Forecast hanya boleh ditujukan '.$this->tanggal($this->periodeMinimumForecast(), 'F Y').' sampai '.$this->tanggal($this->periodeMaximumForecast(), 'F Y').' dalam horizon perencanaan 5 bulan ke depan.']);
        }
        $tahunFiskal = $this->tahunFiskalUntuk($periode);
        if (! $tahunFiskal) {
            throw ValidationException::withMessages(['periode' => 'Periode ini belum berada dalam tahun fiskal yang terdaftar.']);
        }
        $usulan = collect($nilai['usulan'] ?? [])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0);
        if (Produk::query()->whereIn('id_produk', $usulan->keys())->count() !== $usulan->count()) {
            throw ValidationException::withMessages(['usulan' => 'Terdapat produk yang tidak dikenal.']);
        }
        $kirim = $nilai['aksi'] === 'kirim';
        if ($kirim && $usulan->isEmpty()) {
            throw ValidationException::withMessages(['usulan' => 'Isi minimal satu SKU dengan jumlah lebih dari 0 karton.']);
        }

        DB::transaction(function () use ($distributor, $periode, $tahunFiskal, $usulan, $kirim) {
            // 1 distributor = 1 forecast per periode bulan (dijaga juga oleh UNIQUE di database).
            $forecast = Forecast::query()->where('id_distributor', $distributor->id_distributor)
                ->whereDate('periode', $periode->toDateString())->lockForUpdate()->first();
            if ($forecast && $forecast->status !== self::STATUS_DRAFT) {
                throw ValidationException::withMessages(['periode' => 'Forecast periode ini sudah diajukan dan tidak dapat diubah. Batalkan pengajuan terlebih dahulu bila ingin mengganti.']);
            }
            $forecast ??= new Forecast(['id_distributor' => $distributor->id_distributor, 'periode' => $periode->toDateString()]);
            $forecast->fill(['id_tahun_fiskal' => $tahunFiskal->id_tahun_fiskal, 'tanggal_pengajuan' => now(),
                'status' => $kirim ? self::STATUS_DIAJUKAN : self::STATUS_DRAFT])->save();

            DetailForecast::query()->where('id_forecast', $forecast->id_forecast)->delete();
            foreach ($usulan as $idProduk => $karton) {
                DetailForecast::query()->create(['id_forecast' => $forecast->id_forecast, 'id_produk' => $idProduk, 'jumlah_kontainer' => $karton]);
            }
            $this->catat($distributor->penggunaRecord, ($kirim ? 'Ajukan' : 'Simpan draft').' forecast '.$periode->format('Y-m'));
        });

        if ($kirim) {
            return redirect()->route('e-po.distributor.hitung-forecast', ['periode' => $periode->format('Y-m')])
                ->with('success', 'Forecast '.$this->tanggal($periode, 'F Y').' berhasil diajukan. Menunggu penetapan kuota Logistik Pusat.');
        }

        return redirect()->route('e-po.distributor.ajukan-forecast', ['periode' => $periode->format('Y-m')])->with('success', 'Draft forecast tersimpan.');
    }

    /** Hapus draft / tarik pengajuan forecast selama Logistik belum memprosesnya dan belum ada E-PO. */
    public function hapusForecast(Request $request, Forecast $forecast): RedirectResponse
    {
        $distributor = $this->distributorAktif($request);
        abort_unless($distributor && $forecast->id_distributor === $distributor->id_distributor, 403, 'Forecast bukan milik distributor aktif.');
        abort_if($forecast->periode->lessThan($this->periodeMinimumForecast()) || $forecast->periode->greaterThan($this->periodeMaximumForecast()), 422,
            'Forecast di luar horizon rolling lead time 2-3 bulan tidak dapat diubah atau dibatalkan.');
        abort_unless(in_array($forecast->status, [self::STATUS_DRAFT, self::STATUS_DIAJUKAN], true), 422,
            'Forecast yang sudah diproses atau disetujui Logistik tidak dapat dihapus.');
        abort_if($forecast->purchaseOrderRecords()->exists(), 422, 'Forecast sudah memiliki E-PO sehingga tidak dapat dihapus.');

        DB::transaction(function () use ($forecast, $distributor) {
            $forecast->detailForecastRecords()->delete();
            $forecast->delete();
            $this->catat($distributor->penggunaRecord, 'Hapus forecast '.$forecast->periode->format('Y-m'));
        });

        return redirect()->route('e-po.distributor.hitung-forecast')->with('success', 'Forecast '.$this->tanggal($forecast->periode, 'F Y').' berhasil dihapus.');
    }

    // ------------------------------------------------------------------
    // LAYAR 5 - Input E-PO (keranjang pesanan; mode valid & over-quota dalam 1 view)
    // ------------------------------------------------------------------
    public function inputEpo(Request $request): View
    {
        $konteks = $this->konteksDistributor($request);
        if (! $konteks) {
            return view('pages.e-po.distributor.hitung-forecast', $this->helperTampilan());
        }
        $distributor = $konteks['distributor'];
        $idForecastDipilih = $request->integer('forecast');
        $forecast = $idForecastDipilih > 0
            ? Forecast::query()
                ->whereKey($idForecastDipilih)
                ->where('id_distributor', $distributor->id_distributor)
                ->where('status', self::STATUS_DISETUJUI)
                ->whereDate('periode', '>=', $this->periodeMinimumForecast()->toDateString())
                ->whereDate('periode', '<=', $this->periodeMaximumEpo()->toDateString())
                ->with(['detailForecastRecords.produk', 'purchaseOrderRecords.detailPoRecordsAsPo'])
                ->first()
            : $this->forecastSiapPo($distributor);
        if ($forecast && ! $this->sisaKuota($forecast)->contains(fn ($sisa) => $sisa > 0)) {
            $forecast = null;
        }
        $katalog = collect();
        $keranjang = [];
        $draft = null;

        if ($forecast) {
            $sisa = $this->sisaKuota($forecast);
            $draft = $forecast->purchaseOrderRecords->firstWhere('status', self::STATUS_DRAFT);
            $kuotaProduk = $forecast->detailForecastRecords->keyBy('id_produk');

            $katalog = $forecast->detailForecastRecords
                ->sortBy(fn (DetailForecast $detail) => $detail->produk->kode_sku)
                ->filter(fn (DetailForecast $detail) => (int) ($sisa[$detail->id_produk] ?? 0) > 0)
                ->map(fn (DetailForecast $detail) => [
                    'id' => $detail->produk->id_produk,
                    'kode' => $detail->produk->kode_sku,
                    'nama' => $detail->produk->nama_produk,
                    'kategori' => $detail->produk->kategori,
                    'harga' => (float) $detail->produk->harga,
                    'kuota' => (int) ($kuotaProduk[$detail->id_produk]->jumlah_disetujui ?? 0),
                    'sisa' => (int) ($sisa[$detail->id_produk] ?? 0),
                ])
                ->values();

            // Isi keranjang: input terakhir (saat validasi gagal) > draft tersimpan > kosong.
            $lama = old('qty');
            $sumber = is_array($lama)
                ? collect($lama)->map(fn ($qty) => (int) $qty)
                : ($draft?->detailPoRecordsAsPo->pluck('jumlah_kontainer', 'id_produk') ?? collect());
            $keranjang = $sumber->filter(fn ($qty, $id) => $katalog->contains('id', (int) $id))
                ->map(fn ($qty, $id) => ['id' => (int) $id, 'qty' => (int) $qty])->values()->all();
        }
        $daftarForecastEpo = Forecast::query()
            ->where('id_distributor', $distributor->id_distributor)
            ->where('status', self::STATUS_DISETUJUI)
            ->whereDate('periode', '>=', $this->periodeMinimumForecast()->toDateString())
            ->whereDate('periode', '<=', $this->periodeMaximumEpo()->toDateString())
            ->with(['detailForecastRecords', 'purchaseOrderRecords.detailPoRecordsAsPo'])
            ->orderBy('periode')
            ->get()
            ->filter(fn (Forecast $item) => $this->sisaKuota($item)->contains(fn ($sisa) => $sisa > 0))
            ->values();

        return view('pages.e-po.distributor.input-epo', $konteks + $this->helperTampilan() + [
            'forecast' => $forecast, 'draft' => $draft, 'katalog' => $katalog, 'keranjang' => $keranjang,
            'minimumOrder' => self::MINIMUM_ORDER_KARTON, 'kartonPerKontainer' => self::KARTON_PER_KONTAINER,
            'pabrik' => self::PABRIK,
            'jadwal' => $this->jadwalGelombang(now(), null, $forecast?->periode),
            'cutoffDay' => self::CUTOFF_DAY,
            'daftarForecastEpo' => $daftarForecastEpo,
        ]);
    }

    public function submitEpo(Request $request): RedirectResponse
    {
        $nilai = $request->validate([
            'id_forecast' => ['required', 'integer', 'exists:forecast,id_forecast'],
            'aksi' => ['required', 'in:draft,kirim'],
            'qty' => ['nullable', 'array'],
            'qty.*' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ], ['qty.*.integer' => 'Kuantitas pesanan harus berupa bilangan bulat (karton).']);
        $distributor = $this->distributorAktif($request);
        abort_unless($distributor?->penggunaRecord, 422, 'Distributor belum memiliki akun pengguna.');
        $kirim = $nilai['aksi'] === 'kirim';

        $po = DB::transaction(function () use ($nilai, $distributor, $kirim) {
            $forecast = Forecast::query()->whereKey($nilai['id_forecast'])->where('id_distributor', $distributor->id_distributor)
                ->where('status', self::STATUS_DISETUJUI)->lockForUpdate()
                ->with(['detailForecastRecords.produk', 'purchaseOrderRecords.detailPoRecordsAsPo'])->first();
            if (! $forecast) {
                throw ValidationException::withMessages(['id_forecast' => 'Forecast tidak ditemukan atau kuotanya belum ditetapkan.']);
            }
            if ($forecast->periode->lessThan($this->periodeMinimumForecast()) || $forecast->periode->greaterThan($this->periodeMaximumEpo())) {
                throw ValidationException::withMessages(['id_forecast' => 'E-PO hanya dapat dibuat untuk forecast yang sudah masuk window pemrosesan 2-3 bulan mendatang.']);
            }

            // Auto-lock sisi server: pesanan per SKU tidak boleh melebihi sisa kuota.
            $sisa = $this->sisaKuota($forecast);
            $jumlah = [];
            $galat = [];
            foreach ($forecast->detailForecastRecords->keyBy('id_produk') as $idProduk => $detail) {
                $qty = (int) ($nilai['qty'][$idProduk] ?? 0);
                $batas = (int) ($sisa[$idProduk] ?? 0);
                if ($qty > $batas) {
                    $galat['qty.'.$idProduk] = $detail->produk->kode_sku.': pesanan '.number_format($qty, 0, ',', '.').' karton melebihi sisa kuota '.number_format($batas, 0, ',', '.').' karton.';
                }
                if ($qty > 0) {
                    $jumlah[$idProduk] = $qty;
                }
            }
            $produkAsing = collect($nilai['qty'] ?? [])->filter(fn ($q) => (int) $q > 0)->keys()
                ->diff($forecast->detailForecastRecords->pluck('id_produk'));
            if ($produkAsing->isNotEmpty()) {
                $galat['qty'] = 'Terdapat produk yang tidak memiliki kuota pada forecast ini.';
            }
            if ($kirim && ! $jumlah) {
                $galat['qty'] = 'Tambahkan minimal satu produk ke daftar pesanan.';
            }
            if ($kirim && $jumlah && array_sum($jumlah) < self::MINIMUM_ORDER_KARTON) {
                $galat['qty'] = 'Minimum order '.number_format(self::MINIMUM_ORDER_KARTON, 0, ',', '.').' karton (1 kontainer). Total pesanan Anda baru '.number_format(array_sum($jumlah), 0, ',', '.').' karton.';
            }
            if ($galat) {
                throw ValidationException::withMessages($galat);
            }

            $po = $forecast->purchaseOrderRecords->firstWhere('status', self::STATUS_DRAFT)
                ?? new PurchaseOrder(['id_forecast' => $forecast->id_forecast, 'nomor_po' => $this->nomorPoBaru()]);
            $po->fill(['tanggal_pengajuan' => now(), 'status' => $kirim ? self::STATUS_DIAJUKAN : self::STATUS_DRAFT])->save();
            DetailPo::query()->where('id_po', $po->id_po)->delete();
            foreach ($jumlah as $idProduk => $karton) {
                DetailPo::query()->create(['id_po' => $po->id_po, 'id_produk' => $idProduk, 'jumlah_kontainer' => $karton]);
            }
            $this->catat($distributor->penggunaRecord, ($kirim ? 'Kirim' : 'Simpan draft').' E-PO '.$po->nomor_po);

            return $po;
        });

        if ($kirim) {
            return redirect()->route('e-po.distributor.epo-selesai', $po)->with('success', 'E-PO '.$po->nomor_po.' terkirim ke Logistik Pusat untuk diverifikasi.');
        }

        return redirect()->route('e-po.distributor.input-epo')->with('success', 'Draft E-PO '.$po->nomor_po.' tersimpan.');
    }

    /** Hapus draft / tarik E-PO yang belum diproses Logistik. Sisa kuota otomatis kembali. */
    public function hapusEpo(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $distributor = $this->distributorAktif($request);
        $po->load('forecast');
        abort_unless($distributor && $po->forecast->id_distributor === $distributor->id_distributor, 403, 'E-PO bukan milik distributor aktif.');
        abort_unless(in_array($po->status, [self::STATUS_DRAFT, self::STATUS_DIAJUKAN], true), 422,
            'E-PO yang sudah diproses atau disetujui Logistik tidak dapat dihapus.');

        DB::transaction(function () use ($po, $distributor) {
            $po->detailPoRecordsAsPo()->delete();
            $po->delete();
            $this->catat($distributor->penggunaRecord, 'Hapus E-PO '.$po->nomor_po);
        });

        return redirect()->route('e-po.distributor.input-epo')->with('success', 'E-PO '.$po->nomor_po.' dihapus. Sisa kuota dikembalikan.');
    }

    // ------------------------------------------------------------------
    // LAYAR 7 - Dokumen PO resmi (siap cetak)
    // ------------------------------------------------------------------
    public function epoSelesai(Request $request, ?PurchaseOrder $po = null): View
    {
        $konteks = $this->konteksDistributor($request);
        if (! $konteks) {
            return view('pages.e-po.distributor.hitung-forecast', $this->helperTampilan());
        }
        $distributor = $konteks['distributor'];
        $relasi = ['forecast.distributor.wilayah', 'forecast.distributor.penggunaRecord', 'detailPoRecordsAsPo.produk', 'penyetuju', 'pengirimanRecordsAsPo'];

        if ($po) {
            $po->load($relasi);
            abort_unless($po->forecast->id_distributor === $distributor->id_distributor, 403, 'PO bukan milik distributor aktif.');
            abort_if($po->status === self::STATUS_DRAFT, 404);
        } else {
            $po = PurchaseOrder::query()->whereHas('forecast', fn ($q) => $q->where('id_distributor', $distributor->id_distributor))
                ->where('status', '!=', self::STATUS_DRAFT)->with($relasi)
                ->orderByRaw("CASE status WHEN 'Disetujui' THEN 0 ELSE 1 END")->orderByDesc('tanggal_pengajuan')->first();
        }

        return view('pages.e-po.distributor.epo-selesai', $konteks + $this->helperTampilan() + [
            'po' => $po, 'dok' => $po ? $this->dokumenPo($po) : null, 'pabrik' => self::PABRIK,
        ]);
    }

    public function ubahKonteks(Request $request): RedirectResponse
    {
        $nilai = $request->validate(['id_distributor' => ['required', 'integer', 'exists:distributor,id_distributor']]);
        $request->session()->put('active_distributor_id', (int) $nilai['id_distributor']);

        return redirect()->route('e-po.distributor.hitung-forecast')->with('success', 'Perusahaan simulasi aktif diperbarui.');
    }

    // ------------------------------------------------------------------
    // Helper privat
    // ------------------------------------------------------------------
    private function distributorAktif(Request $request): ?Distributor
    {
        $dipilih = $request->session()->get('active_distributor_id');

        return Distributor::query()->with('wilayah', 'penggunaRecord')->where('status_aktif', true)
            ->when($dipilih, fn ($q) => $q->whereKey($dipilih))->first()
            ?? Distributor::query()->with('wilayah', 'penggunaRecord')->where('status_aktif', true)->orderBy('id_distributor')->first();
    }

    /** @return array{distributor: Distributor, daftarDistributor: Collection}|null */
    private function konteksDistributor(Request $request): ?array
    {
        $distributor = $this->distributorAktif($request);

        return $distributor ? [
            'distributor' => $distributor,
            'daftarDistributor' => Distributor::query()->where('status_aktif', true)->orderBy('nama')->get(['id_distributor', 'nama']),
        ] : null;
    }

    /** Closure pembantu untuk Blade: $badge($status, 'forecast'|'po'). Tanggal memakai $tanggalEpo dari AppServiceProvider. */
    private function helperTampilan(): array
    {
        return ['badge' => $this->badge(...)];
    }

    /** Forecast Disetujui paling awal yang masih punya sisa kuota > 0 pada minimal satu SKU. */
    private function forecastSiapPo(Distributor $distributor, ?CarbonInterface $periode = null): ?Forecast
    {
        return Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->where('status', self::STATUS_DISETUJUI)
            ->whereDate('periode', '>=', $this->periodeMinimumForecast()->toDateString())
            ->whereDate('periode', '<=', $this->periodeMaximumEpo()->toDateString())
            ->when($periode, fn ($query) => $query->whereDate('periode', CarbonImmutable::instance($periode)->toDateString()))
            ->with(['detailForecastRecords.produk', 'purchaseOrderRecords.detailPoRecordsAsPo'])
            ->orderBy('periode')->get()
            ->first(fn (Forecast $forecast) => $this->sisaKuota($forecast)->contains(fn ($sisa) => $sisa > 0));
    }

    /**
     * Sisa kuota per produk = kuota disetujui - total pesanan E-PO yang sudah diajukan/diproses/disetujui.
     * Draft tidak mengurangi kuota.
     *
     * @return Collection<int, int> id_produk => sisa (karton)
     */
    private function sisaKuota(Forecast $forecast): Collection
    {
        $terpakai = $forecast->purchaseOrderRecords
            ->whereIn('status', [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES, self::STATUS_DISETUJUI])
            ->flatMap(fn (PurchaseOrder $po) => $po->detailPoRecordsAsPo)
            ->groupBy('id_produk')->map(fn (Collection $baris) => (int) $baris->sum('jumlah_kontainer'));

        return $forecast->detailForecastRecords->mapWithKeys(fn (DetailForecast $d) => [
            $d->id_produk => max(0, (int) $d->jumlah_disetujui - (int) ($terpakai[$d->id_produk] ?? 0)),
        ]);
    }

    private function periodeTarget(?string $input = null): CarbonImmutable
    {
        if ($input && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $input)) {
            return CarbonImmutable::createFromFormat('!Y-m', $input);
        }

        return $this->periodeMinimumForecast();
    }

    private function tenggatPengajuan(CarbonImmutable $periode): CarbonImmutable
    {
        $bulanCutoff = $periode->subMonthsNoOverflow(self::LEAD_TIME_MIN_MONTHS)->startOfMonth();

        return $bulanCutoff->setDay(min(self::CUTOFF_DAY, $bulanCutoff->daysInMonth))->setTime(17, 0);
    }

    private function periodeMinimumForecast(): CarbonImmutable
    {
        $bulanMinimum = CarbonImmutable::now()->startOfMonth()
            ->addMonthsNoOverflow(self::LEAD_TIME_MIN_MONTHS);

        if (CarbonImmutable::now()->day > self::CUTOFF_DAY) {
            $bulanMinimum = $bulanMinimum->addMonthNoOverflow();
        }

        return $bulanMinimum;
    }

    private function periodeMaximumForecast(): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfMonth()
            ->addMonthsNoOverflow(
                CarbonImmutable::now()->day > self::CUTOFF_DAY
                    ? self::ROLLING_PLANNING_MAX_MONTHS
                    : self::ROLLING_PLANNING_MAX_MONTHS - 1
            );
    }

    private function periodeMaximumEpo(): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfMonth()
            ->addMonthsNoOverflow(
                CarbonImmutable::now()->day > self::CUTOFF_DAY
                    ? self::LEAD_TIME_MAX_MONTHS + 1
                    : self::LEAD_TIME_MAX_MONTHS
            );
    }

    private function tahunFiskalUntuk(CarbonInterface $tanggal): ?TahunFiskal
    {
        return TahunFiskal::query()->whereDate('tanggal_mulai', '<=', $tanggal->toDateString())
            ->whereDate('tanggal_selesai', '>=', $tanggal->toDateString())->orderByDesc('tanggal_mulai')->first();
    }

    /**
     * Rekomendasi (karton) = rata-rata Sell-Out tertimbang {bulan lama x1, x2, bulan terbaru x3}
     * + stok pengaman, - stok layak jual terbaru.
     *
     * @return Collection<int, array{rata_rata: float, stok_layak_jual: int, karton: int}>
     */
    private function rekomendasi(int $idDistributor, CarbonImmutable $periodeTarget): Collection
    {
        $bulanMusiman = $periodeTarget->subYearNoOverflow();
        $mulai = $bulanMusiman->startOfMonth()->subMonthsNoOverflow(self::BULAN_RIWAYAT_PENJUALAN - 1);
        $akhir = $bulanMusiman->endOfMonth();

        $penjualan = SellOut::query()->where('id_distributor', $idDistributor)
            ->whereDate('tanggal', '>=', $mulai->toDateString())->whereDate('tanggal', '<=', $akhir->toDateString())
            ->get(['id_produk', 'tanggal', 'jumlah'])
            ->groupBy('id_produk')
            ->map(fn (Collection $baris) => $baris->groupBy(fn ($b) => CarbonImmutable::parse($b->tanggal)->format('Y-m'))
                ->map(fn (Collection $perBulan) => (float) $perBulan->sum('jumlah')));
        $stok = StokGudang::query()->where('id_distributor', $idDistributor)->whereDate('periode', '<=', $akhir->toDateString())
            ->orderByDesc('periode')->get()->unique('id_produk')->keyBy('id_produk');

        $bobotTotal = 0;
        $bobot = [];
        for ($i = 0; $i < self::BULAN_RIWAYAT_PENJUALAN; $i++) {
            $bobot[$mulai->addMonthsNoOverflow($i)->format('Y-m')] = $i + 1;
            $bobotTotal += $i + 1;
        }

        return Produk::query()->orderBy('kode_sku')->get()->mapWithKeys(function (Produk $produk) use ($penjualan, $stok, $bobot, $bobotTotal) {
            $bulanan = $penjualan[$produk->id_produk] ?? collect();
            $rataRata = collect($bobot)->sum(fn (int $b, string $bulan) => ((float) ($bulanan[$bulan] ?? 0)) * $b) / $bobotTotal;
            $stokLayak = (int) ($stok[$produk->id_produk]->stok_layak_jual ?? 0);

            return [$produk->id_produk => [
                'rata_rata' => round($rataRata, 1), 'stok_layak_jual' => $stokLayak,
                'karton' => (int) ceil(max(0.0, $rataRata * (1 + self::STOK_PENGAMAN) - $stokLayak)),
            ]];
        });
    }

    /**
     * Jadwal gelombang dan estimasi tiba didasarkan pada tanggal pengajuan E-PO.
     * Tidak ada kolom tambahan: informasi ini dihitung dari konfigurasi modul.
     *
     * @return array{is_lewat_cutoff: bool, gelombang_kirim: CarbonImmutable, eta_min: CarbonImmutable, eta_max: CarbonImmutable}
     */
    private function jadwalGelombang(?CarbonInterface $tanggalAcuan = null, ?PurchaseOrder $po = null, ?CarbonInterface $periodeTarget = null): array
    {
        $acuan = CarbonImmutable::instance($tanggalAcuan ? CarbonImmutable::parse($tanggalAcuan) : now());
        $isLewatCutoff = $acuan->day > self::CUTOFF_DAY;
        $gelombang = $acuan->startOfMonth()->setDay(self::CUTOFF_DAY);

        if ($isLewatCutoff) {
            $gelombang = $gelombang->addMonthNoOverflow();
        }

        $etaMin = $gelombang->addDays(self::LEAD_TIME_MIN_DAYS);
        $etaMax = $gelombang->addDays(self::LEAD_TIME_MAX_DAYS);

        if ($po && $po->relationLoaded('pengirimanRecordsAsPo')) {
            $pengiriman = $po->pengirimanRecordsAsPo->sortByDesc('id_pengiriman')->first();
            if ($pengiriman?->eta_awal) {
                $etaMin = CarbonImmutable::instance($pengiriman->eta_awal);
            }
            if ($pengiriman?->eta_terkini) {
                $etaMax = CarbonImmutable::instance($pengiriman->eta_terkini);
            }
        }

        $targetSesuai = true;
        $keteranganTarget = null;
        if ($periodeTarget) {
            $target = CarbonImmutable::instance($periodeTarget)->startOfMonth();
            $targetSesuai = $etaMax->startOfMonth()->lessThanOrEqualTo($target->endOfMonth());
            $keteranganTarget = $targetSesuai
                ? 'Estimasi kedatangan masih berada pada periode target.'
                : 'Estimasi kedatangan melewati periode target '.$target->locale('id')->translatedFormat('F Y').'. Forecast perlu dipindah ke periode berikutnya.';
        }

        return [
            'is_lewat_cutoff' => $isLewatCutoff,
            'gelombang_kirim' => $gelombang,
            'eta_min' => $etaMin,
            'eta_max' => $etaMax,
            'target_sesuai' => $targetSesuai,
            'keterangan_target' => $keteranganTarget,
        ];
    }

    /** @return array{rows: list<array<string, mixed>>, total_karton: int, total_kontainer: float, total_nilai: float, kode_verifikasi: ?string} */
    private function dokumenPo(PurchaseOrder $po): array
    {
        $rows = $po->detailPoRecordsAsPo->sortBy(fn ($d) => $d->produk->kode_sku)->values()->map(fn ($detail, $i) => [
            'no' => $i + 1, 'kode' => $detail->produk->kode_sku, 'nama' => $detail->produk->nama_produk,
            'kategori' => $detail->produk->kategori, 'karton' => (int) $detail->jumlah_kontainer,
            'harga' => (float) $detail->produk->harga, 'subtotal' => $detail->jumlah_kontainer * (float) $detail->produk->harga,
        ]);
        $totalKarton = (int) $rows->sum('karton');
        $jadwal = $this->jadwalGelombang($po->tanggal_pengajuan, $po, $po->forecast->periode);

        return [
            'rows' => $rows->all(), 'total_karton' => $totalKarton,
            'total_kontainer' => $totalKarton / self::KARTON_PER_KONTAINER, 'total_nilai' => (float) $rows->sum('subtotal'),
            'jadwal' => $jadwal,
            'kode_verifikasi' => $po->status === self::STATUS_DISETUJUI
                ? implode('-', str_split(strtoupper(substr(hash('sha256', $po->nomor_po.'|'.$po->id_po.'|'.$po->tanggal_disetujui), 0, 12)), 4))
                : null,
        ];
    }

    private function nomorPoBaru(): string
    {
        $awalan = self::AWALAN_PO.'-'.now()->year.'-';
        $terakhir = PurchaseOrder::query()->where('nomor_po', 'like', $awalan.'%')->lockForUpdate()->orderByDesc('nomor_po')->value('nomor_po');

        return $awalan.str_pad((string) ($terakhir ? ((int) Str::afterLast($terakhir, '-')) + 1 : 1), 4, '0', STR_PAD_LEFT);
    }

    private function catat(Pengguna $pengguna, string $aksi): void
    {
        LogAktivitas::query()->create(['id_pengguna' => $pengguna->id_pengguna, 'modul' => 'E-PO',
            'aksi' => Str::limit($aksi, 60, ''), 'waktu' => now()]);
    }

    private function tanggal(?CarbonInterface $tanggal, string $format = 'd F Y'): string
    {
        return $tanggal ? $tanggal->copy()->locale('id')->translatedFormat($format) : '-';
    }

    /** @return array{label: string, class: string} */
    private function badge(string $status, string $jenis = 'forecast'): array
    {
        $po = $jenis === 'po';

        return match ($status) {
            self::STATUS_DRAFT => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-600'],
            self::STATUS_DIAJUKAN => ['label' => $po ? 'Menunggu Verifikasi' : 'Menunggu Penetapan Kuota', 'class' => 'bg-amber-50 text-amber-700'],
            self::STATUS_DIPROSES => ['label' => 'Sedang Diproses', 'class' => 'bg-blue-50 text-blue-700'],
            self::STATUS_DISETUJUI => ['label' => $po ? 'PO Terbit' : 'Kuota Ditetapkan', 'class' => 'bg-emerald-50 text-emerald-700'],
            default => ['label' => $status, 'class' => 'bg-slate-100 text-slate-600'],
        };
    }
}
