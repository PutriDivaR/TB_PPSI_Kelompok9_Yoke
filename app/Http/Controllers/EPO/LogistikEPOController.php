<?php

namespace App\Http\Controllers\EPO;

use App\Http\Controllers\Controller;
use App\Models\DetailForecast;
use App\Models\Forecast;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\PurchaseOrder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * E-PO sisi Logistik Pusat.
 *
 * SATUAN: karton (lihat catatan di DistributorEPOController). Kolom jumlah_kontainer /
 * jumlah_disetujui menyimpan jumlah KARTON.
 */
class LogistikEPOController extends Controller implements HasMiddleware
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_DIAJUKAN = 'Diajukan';

    public const STATUS_DIPROSES = 'Diproses';

    public const STATUS_DISETUJUI = 'Disetujui';

    private const KARTON_PER_KONTAINER = 2000;

    private const MINIMUM_ORDER_KARTON = 2000;

    private const KAPASITAS_BULANAN_KARTON = 30000;

    private const CUTOFF_DAY = 15;

    private const LEAD_TIME_MIN_DAYS = 60;

    private const LEAD_TIME_MAX_DAYS = 75;

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
    // LAYAR 3 - Antrean forecast & E-PO
    // ------------------------------------------------------------------
    public function antrean(): View
    {
        $urut = "CASE status WHEN 'Diajukan' THEN 0 WHEN 'Diproses' THEN 1 ELSE 2 END";
        $forecasts = Forecast::query()->where('status', '!=', self::STATUS_DRAFT)
            ->with(['distributor.wilayah', 'detailForecastRecords.produk'])
            ->orderByRaw($urut)->orderByDesc('periode')->orderByDesc('tanggal_pengajuan')->limit(50)->get()
            ->each(fn (Forecast $f) => $f->setAttribute('totals', [
                'usulan' => (int) $f->detailForecastRecords->sum('jumlah_kontainer'),
                'disetujui' => (int) $f->detailForecastRecords->sum('jumlah_disetujui'),
                'nilai_usulan' => $f->detailForecastRecords->sum(fn (DetailForecast $d) => $d->jumlah_kontainer * (float) $d->produk->harga),
            ]));
        $pos = PurchaseOrder::query()->where('status', '!=', self::STATUS_DRAFT)
            ->with(['forecast.distributor.wilayah', 'detailPoRecordsAsPo.produk'])
            ->orderByRaw($urut)->orderByDesc('tanggal_pengajuan')->limit(50)->get()
            ->each(function (PurchaseOrder $po) {
                $po->setAttribute('total_karton', (int) $po->detailPoRecordsAsPo->sum('jumlah_kontainer'));
                $po->setAttribute('total_nilai', $po->detailPoRecordsAsPo->sum(fn ($d) => $d->jumlah_kontainer * (float) $d->produk->harga));
            });

        $periodeIni = CarbonImmutable::now()->startOfMonth();
        $terpakai = $this->kapasitasTerpakai($periodeIni);
        $menungguForecast = $forecasts->whereIn('status', [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES]);

        return view('pages.e-po.logistik.antrean', $this->helperTampilan() + [
            'forecasts' => $forecasts, 'pos' => $pos, 'periodeIni' => $periodeIni,
            'kartonPerKontainer' => self::KARTON_PER_KONTAINER,
            'stats' => [
                'forecast_pending' => $menungguForecast->count(),
                'volume_antrean' => $menungguForecast->sum(fn ($f) => $f->totals['usulan']),
                'po_verifikasi' => $pos->whereIn('status', [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES])->count(),
                'utilisasi' => round($terpakai / self::KAPASITAS_BULANAN_KARTON * 100, 1),
                'terpakai' => $terpakai, 'kapasitas' => self::KAPASITAS_BULANAN_KARTON,
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // LAYAR 4 - Detail forecast & penetapan kuota
    // ------------------------------------------------------------------
    public function detailForecast(Forecast $forecast): View
    {
        abort_if($forecast->status === self::STATUS_DRAFT, 404);
        $forecast->load(['distributor.wilayah', 'detailForecastRecords.produk', 'penyetuju']);

        $baris = $forecast->detailForecastRecords->sortBy(fn ($d) => $d->produk->kode_sku)->map(fn ($d) => [
            'id' => $d->id_detail_forecast, 'kode' => $d->produk->kode_sku, 'nama' => $d->produk->nama_produk,
            'harga' => (float) $d->produk->harga, 'usulan' => (int) $d->jumlah_kontainer,
            'kuota' => (int) old('kuota.'.$d->id_detail_forecast, $d->jumlah_disetujui ?? $d->jumlah_kontainer),
        ])->values();

        return view('pages.e-po.logistik.penetapan-kuota', $this->helperTampilan() + [
            'forecast' => $forecast, 'baris' => $baris,
            'kapasitas' => self::KAPASITAS_BULANAN_KARTON,
            'sisaKapasitas' => $this->sisaKapasitas($forecast),
            'editable' => in_array($forecast->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES], true),
            'catatan' => old('catatan', $forecast->catatan_penyesuaian ?? ''),
            'kartonPerKontainer' => self::KARTON_PER_KONTAINER,
        ]);
    }

    public function mulaiForecast(Forecast $forecast): RedirectResponse
    {
        abort_if($forecast->status !== self::STATUS_DIAJUKAN, 422, 'Forecast ini tidak sedang menunggu diproses.');
        $forecast->update(['status' => self::STATUS_DIPROSES]);

        return redirect()->route('e-po.logistik.detail-forecast', $forecast)->with('success', 'Forecast ditandai sedang diproses.');
    }

    /**
     * aksi: draft (simpan sementara) | setujui (pakai angka yang diedit) | setujui_semua (kuota = usulan semua SKU).
     */
    public function tetapkanKuota(Request $request, Forecast $forecast): RedirectResponse
    {
        $nilai = $request->validate([
            'aksi' => ['required', 'in:draft,setujui,setujui_semua'],
            'kuota' => ['nullable', 'array'],
            'kuota.*' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], ['kuota.*.integer' => 'Kuota harus berupa bilangan bulat (karton).', 'catatan.max' => 'Catatan maksimal 255 karakter.']);
        $pengguna = $this->penggunaLogistik();
        abort_unless($pengguna, 422, 'Belum ada pengguna aktif berperan Logistik Pusat.');
        $catatan = trim((string) ($nilai['catatan'] ?? ''));

        DB::transaction(function () use ($nilai, $forecast, $pengguna, $catatan) {
            $forecast = Forecast::query()->whereKey($forecast->id_forecast)->lockForUpdate()->with('detailForecastRecords.produk')->firstOrFail();
            if (! in_array($forecast->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES], true)) {
                throw ValidationException::withMessages(['kuota' => 'Forecast ini tidak lagi menunggu penetapan kuota.']);
            }
            $semua = $nilai['aksi'] === 'setujui_semua';
            $final = $nilai['aksi'] !== 'draft';

            // Tentukan kuota tiap SKU.
            $galat = [];
            $kuota = [];
            foreach ($forecast->detailForecastRecords as $detail) {
                $kuota[$detail->id_detail_forecast] = $semua ? (int) $detail->jumlah_kontainer : (int) ($nilai['kuota'][$detail->id_detail_forecast] ?? 0);
                if ($kuota[$detail->id_detail_forecast] > $detail->jumlah_kontainer) {
                    $galat['kuota.'.$detail->id_detail_forecast] = $detail->produk->kode_sku.': kuota tidak boleh melebihi usulan ('.number_format($detail->jumlah_kontainer, 0, ',', '.').' karton).';
                }
            }
            $total = array_sum($kuota);
            $sisa = $this->sisaKapasitas($forecast);
            $dikurangi = $forecast->detailForecastRecords->contains(fn ($d) => $kuota[$d->id_detail_forecast] < $d->jumlah_kontainer);

            if ($final && $total < 1) {
                $galat['kuota'] = 'Total kuota harus lebih dari 0 karton.';
            }
            if ($final && $total > $sisa) {
                $galat['kuota'] = 'Total kuota '.number_format($total, 0, ',', '.').' karton melebihi sisa kapasitas pabrik ('.number_format($sisa, 0, ',', '.').' karton). Kurangi kuota beberapa SKU atau gunakan "Sesuaikan ke Kapasitas".';
            }
            if ($final && $dikurangi && mb_strlen($catatan) < 5) {
                $galat['catatan'] = 'Isi catatan penyesuaian (minimal 5 karakter) karena ada kuota SKU yang dikurangi dari usulan.';
            }
            if ($galat) {
                throw ValidationException::withMessages($galat);
            }

            foreach ($forecast->detailForecastRecords as $detail) {
                $detail->update(['jumlah_disetujui' => $kuota[$detail->id_detail_forecast]]);
            }
            // Kolom catatan belum ada di ERD; bila ditambahkan (forecast.catatan_penyesuaian) otomatis ikut tersimpan.
            if (Schema::hasColumn('forecast', 'catatan_penyesuaian')) {
                $forecast->catatan_penyesuaian = $catatan !== '' ? $catatan : null;
                $forecast->save();
            }
            if ($final) {
                $forecast->update(['status' => self::STATUS_DISETUJUI, 'tanggal_disetujui' => now(), 'id_penyetuju' => $pengguna->id_pengguna]);
            }
            $this->catat($pengguna, ($final ? ($dikurangi ? 'Setujui kuota (disesuaikan) FC #' : 'Setujui kuota sesuai usulan FC #') : 'Simpan draft kuota FC #').$forecast->id_forecast);
            if ($catatan !== '') {
                $this->catat($pengguna, 'Catatan FC #'.$forecast->id_forecast.': '.$catatan);
            }
        });

        if ($nilai['aksi'] !== 'draft') {
            return redirect()->route('e-po.logistik.antrean')->with('success', 'Kuota ditetapkan. Distributor kini dapat membuat E-PO.');
        }

        return redirect()->route('e-po.logistik.detail-forecast', $forecast)->with('success', 'Draft penetapan kuota tersimpan.');
    }

    // ------------------------------------------------------------------
    // LAYAR 6 - Verifikasi E-PO + pratinjau dokumen PO resmi (siap cetak)
    // ------------------------------------------------------------------
    public function verifikasiEpo(PurchaseOrder $po): View
    {
        abort_if($po->status === self::STATUS_DRAFT, 404);
        $po->load(['forecast.distributor.wilayah', 'forecast.distributor.penggunaRecord', 'forecast.detailForecastRecords', 'forecast.purchaseOrderRecords.detailPoRecordsAsPo', 'detailPoRecordsAsPo.produk', 'penyetuju', 'pengirimanRecordsAsPo']);
        $pemeriksaan = $this->pemeriksaan($po);
        $jadwal = $this->jadwalGelombang($po->tanggal_pengajuan, $po, $po->forecast->periode);

        return view('pages.e-po.logistik.verifikasi-epo', $this->helperTampilan() + [
            'po' => $po, 'dok' => $this->dokumenPo($po), 'pemeriksaan' => $pemeriksaan,
            'semuaLolos' => collect($pemeriksaan)->every(fn ($p) => $p['lolos']),
            'pabrik' => self::PABRIK,
            'bisaDisetujui' => in_array($po->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES], true),
            'jadwal' => $jadwal,
        ]);
    }

    public function mulaiEpo(PurchaseOrder $po): RedirectResponse
    {
        abort_if($po->status !== self::STATUS_DIAJUKAN, 422, 'E-PO ini tidak sedang menunggu diproses.');
        $po->update(['status' => self::STATUS_DIPROSES]);

        return redirect()->route('e-po.logistik.verifikasi-epo', $po)->with('success', 'E-PO ditandai sedang diproses.');
    }

    public function setujuiEpo(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $request->validate(['konfirmasi' => ['required', 'accepted']], [
            'konfirmasi.accepted' => 'Centang konfirmasi stok dan jadwal sebelum menyetujui.',
            'konfirmasi.required' => 'Centang konfirmasi stok dan jadwal sebelum menyetujui.',
        ]);
        $pengguna = $this->penggunaLogistik();
        abort_unless($pengguna, 422, 'Belum ada pengguna aktif berperan Logistik Pusat.');

        DB::transaction(function () use ($po, $pengguna) {
            $po = PurchaseOrder::query()->whereKey($po->id_po)->lockForUpdate()
                ->with(['forecast.distributor', 'forecast.detailForecastRecords', 'forecast.purchaseOrderRecords.detailPoRecordsAsPo', 'detailPoRecordsAsPo.produk'])->firstOrFail();
            if (! in_array($po->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES], true)) {
                throw ValidationException::withMessages(['konfirmasi' => 'E-PO ini tidak sedang menunggu verifikasi.']);
            }
            // Verifikasi ulang di server agar PO yang tidak valid tidak bisa disetujui.
            $gagal = collect($this->pemeriksaan($po))->where('lolos', false)->pluck('label');
            if ($gagal->isNotEmpty()) {
                throw ValidationException::withMessages(['konfirmasi' => 'E-PO belum lolos pemeriksaan: '.$gagal->implode('; ').'.']);
            }
            $po->update(['status' => self::STATUS_DISETUJUI, 'tanggal_disetujui' => now(), 'id_penyetuju' => $pengguna->id_pengguna]);
            $this->catat($pengguna, 'Setujui E-PO '.$po->nomor_po);
        });

        return redirect()->route('e-po.logistik.verifikasi-epo', $po)->with('success', 'E-PO '.$po->nomor_po.' disetujui. Dokumen PO resmi siap dicetak.');
    }

    // ------------------------------------------------------------------
    // Helper privat
    // ------------------------------------------------------------------
    /**
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
                : 'Estimasi kedatangan melewati periode target '.$target->locale('id')->translatedFormat('F Y').'.';
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

    private function helperTampilan(): array
    {
        return ['badge' => $this->badge(...)];
    }

    private function penggunaLogistik(): ?Pengguna
    {
        return Pengguna::query()->where('status_aktif', true)
            ->whereHas('peran', fn ($q) => $q->where('nama_peran', 'Logistik Pusat'))->orderBy('id_pengguna')->first();
    }

    /** Total kuota (karton) yang sudah ditetapkan ke distributor lain pada sebuah periode. */
    private function kapasitasTerpakai(CarbonInterface $periode, ?int $kecualiForecast = null): int
    {
        return (int) DetailForecast::query()->whereHas('forecast', function ($query) use ($periode, $kecualiForecast) {
            $query->whereDate('periode', $periode->toDateString())->where('status', self::STATUS_DISETUJUI)
                ->when($kecualiForecast, fn ($q) => $q->where('id_forecast', '!=', $kecualiForecast));
        })->sum('jumlah_disetujui');
    }

    private function sisaKapasitas(Forecast $forecast): int
    {
        return max(0, self::KAPASITAS_BULANAN_KARTON - $this->kapasitasTerpakai($forecast->periode, $forecast->id_forecast));
    }

    /** @return list<array{label: string, lolos: bool}> */
    private function pemeriksaan(PurchaseOrder $po): array
    {
        $forecast = $po->forecast;
        $kuota = $forecast->detailForecastRecords->keyBy('id_produk');
        $lain = $forecast->purchaseOrderRecords
            ->where('id_po', '!=', $po->id_po)
            ->whereIn('status', [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES, self::STATUS_DISETUJUI])
            ->flatMap(fn (PurchaseOrder $o) => $o->detailPoRecordsAsPo)
            ->groupBy('id_produk')->map(fn (Collection $b) => (int) $b->sum('jumlah_kontainer'));
        $total = (int) $po->detailPoRecordsAsPo->sum('jumlah_kontainer');

        return [
            ['label' => 'Kuantitas tiap SKU tidak melebihi sisa kuota forecast',
                'lolos' => $po->detailPoRecordsAsPo->every(fn ($d) => $d->jumlah_kontainer <= max(0, (int) ($kuota[$d->id_produk]->jumlah_disetujui ?? 0) - (int) ($lain[$d->id_produk] ?? 0)))],
            ['label' => 'Seluruh produk tercantum pada forecast yang disetujui', 'lolos' => $po->detailPoRecordsAsPo->every(fn ($d) => $kuota->has($d->id_produk))],
            ['label' => 'Total pesanan memenuhi minimum order '.number_format(self::MINIMUM_ORDER_KARTON, 0, ',', '.').' karton', 'lolos' => $total >= self::MINIMUM_ORDER_KARTON],
            ['label' => 'Forecast induk berstatus Disetujui', 'lolos' => $forecast->status === self::STATUS_DISETUJUI],
            ['label' => 'Distributor aktif dan memiliki nomor MoU', 'lolos' => (bool) $forecast->distributor->status_aktif && filled($forecast->distributor->nomor_mou)],
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

        return [
            'rows' => $rows->all(), 'total_karton' => $totalKarton,
            'total_kontainer' => $totalKarton / self::KARTON_PER_KONTAINER, 'total_nilai' => (float) $rows->sum('subtotal'),
            'kode_verifikasi' => $po->status === self::STATUS_DISETUJUI
                ? implode('-', str_split(strtoupper(substr(hash('sha256', $po->nomor_po.'|'.$po->id_po.'|'.$po->tanggal_disetujui), 0, 12)), 4))
                : null,
        ];
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
