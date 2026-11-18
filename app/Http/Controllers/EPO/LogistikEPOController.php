<?php

namespace App\Http\Controllers\EPO;

use App\Http\Controllers\Controller;
use App\Models\DetailForecast;
use App\Models\Forecast;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LogistikEPOController extends Controller implements HasMiddleware
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_DIAJUKAN = 'Diajukan';

    public const STATUS_DIPROSES = 'Diproses';

    public const STATUS_DISETUJUI = 'Disetujui';

    private const KAPASITAS_BULANAN = 60;

    private const PABRIK = [
        'nama' => 'PT Yoke Food Industries Indonesia',
        'alamat' => 'Kawasan Industri Jababeka Blok F-12, Cikarang, Bekasi, Jawa Barat 17530',
        'plant' => 'Plant Cikarang #02',
    ];

    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul E-PO belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    public function antrean(): View
    {
        $urut = "CASE status WHEN 'Diajukan' THEN 0 ELSE 1 END";
        $forecasts = Forecast::query()->where('status', '!=', self::STATUS_DRAFT)
            ->with(['distributor.wilayah', 'detailForecastRecords.produk'])
            ->orderByRaw($urut)->orderByDesc('periode')->orderByDesc('tanggal_pengajuan')->limit(50)->get()
            ->each(fn (Forecast $f) => $f->setAttribute('totals', $this->totalForecast($f)));
        $pos = PurchaseOrder::query()->where('status', '!=', self::STATUS_DRAFT)
            ->with(['forecast.distributor.wilayah', 'detailPoRecordsAsPo.produk'])
            ->orderByRaw($urut)->orderByDesc('tanggal_pengajuan')->limit(50)->get()
            ->each(function (PurchaseOrder $po) {
                $po->setAttribute('total_kontainer', (int) $po->detailPoRecordsAsPo->sum('jumlah_kontainer'));
                $po->setAttribute('total_nilai', $po->detailPoRecordsAsPo->sum(fn ($d) => $this->nilai($d->jumlah_kontainer, $d->produk)));
            });
        $periodeIni = CarbonImmutable::now()->startOfMonth();
        $terpakai = $this->kapasitasTerpakai($periodeIni);

        return view('pages.e-po.logistik.antrean', [
            'forecasts' => $forecasts, 'pos' => $pos, 'periodeIni' => $periodeIni,
            'stats' => [
                'forecast_pending' => $forecasts->where('status', self::STATUS_DIAJUKAN)->count(),
                'volume_antrean' => $forecasts->where('status', self::STATUS_DIAJUKAN)->sum(fn ($f) => $f->totals['usulan']),
                'po_verifikasi' => $pos->where('status', self::STATUS_DIAJUKAN)->count(),
                'utilisasi' => self::KAPASITAS_BULANAN > 0 ? round($terpakai / self::KAPASITAS_BULANAN * 100, 1) : 0,
                'terpakai' => $terpakai, 'kapasitas' => self::KAPASITAS_BULANAN,
            ],
        ]);
    }

    public function detailForecast(Forecast $forecast): View
    {
        abort_if($forecast->status === self::STATUS_DRAFT, 404);
        $forecast->load(['distributor.wilayah', 'detailForecastRecords.produk', 'penyetuju']);
        $sisa = max(0, self::KAPASITAS_BULANAN - $this->kapasitasTerpakai($forecast->periode, $forecast->id_forecast));
        $baris = $forecast->detailForecastRecords->sortBy(fn ($d) => $d->produk->kode_sku)->map(fn ($d) => [
            'id' => $d->id_detail_forecast, 'kode' => $d->produk->kode_sku, 'nama' => $d->produk->nama_produk,
            'kpk' => $d->produk->karton_per_kontainer, 'harga' => (float) $d->produk->harga,
            'usulan' => $d->jumlah_kontainer,
            'kuota' => (int) old('kuota.'.$d->id_detail_forecast, $d->jumlah_disetujui ?? $d->jumlah_kontainer),
        ])->values();

        return view('pages.e-po.logistik.penetapan-kuota', [
            'forecast' => $forecast, 'baris' => $baris, 'kapasitas' => self::KAPASITAS_BULANAN,
            'sisaKapasitas' => $sisa,             'editable' => in_array($forecast->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES], true),
        ]);
    }

    public function mulaiForecast(Forecast $forecast): RedirectResponse
    {
        abort_if($forecast->status !== self::STATUS_DIAJUKAN, 422, 'Forecast ini tidak sedang menunggu diproses.');
        $forecast->update(['status' => self::STATUS_DIPROSES]);

        return redirect()->route('e-po.logistik.detail-forecast', $forecast)->with('success', 'Forecast masuk status sedang diproses.');
    }

    public function tetapkanKuota(Request $request, Forecast $forecast): RedirectResponse
    {
        $nilai = $request->validate([
            'aksi' => ['required', 'in:draft,setujui'],
            'kuota' => ['required', 'array'],
            'kuota.*' => ['required', 'integer', 'min:0', 'max:100000'],
        ], ['kuota.*.integer' => 'Kuota harus berupa bilangan bulat (kontainer).']);
        $pengguna = $this->penggunaLogistik();
        abort_unless($pengguna, 422, 'Belum ada pengguna aktif berperan Logistik Pusat.');
        $setujui = $nilai['aksi'] === 'setujui';
        DB::transaction(function () use ($nilai, $forecast, $pengguna, $setujui) {
            $forecast = Forecast::query()->whereKey($forecast->id_forecast)->lockForUpdate()->with('detailForecastRecords.produk')->firstOrFail();
            if (! in_array($forecast->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES], true)) {
                throw ValidationException::withMessages(['kuota' => 'Forecast ini tidak lagi menunggu penetapan kuota.']);
            }
            $galat = [];
            $total = 0;
            foreach ($forecast->detailForecastRecords as $detail) {
                $kuota = (int) ($nilai['kuota'][$detail->id_detail_forecast] ?? 0);
                $total += $kuota;
            }
            $sisa = max(0, self::KAPASITAS_BULANAN - $this->kapasitasTerpakai($forecast->periode, $forecast->id_forecast));
            if ($setujui && $total > $sisa) {
                $galat['kuota'] = "Total kuota {$total} kontainer melebihi sisa kapasitas pabrik ({$sisa} kontainer).";
            }
            if ($setujui && $total < 1) {
                $galat['kuota'] = 'Total kuota harus lebih dari 0 kontainer.';
            }
            if ($galat) {
                throw ValidationException::withMessages($galat);
            }
            foreach ($forecast->detailForecastRecords as $detail) {
                $detail->update(['jumlah_disetujui' => (int) ($nilai['kuota'][$detail->id_detail_forecast] ?? 0)]);
            }
            if ($setujui) {
                $forecast->update(['status' => self::STATUS_DISETUJUI, 'tanggal_disetujui' => now(), 'id_penyetuju' => $pengguna->id_pengguna]);
            }
            $this->catat($pengguna, ($setujui ? 'Tetapkan kuota forecast #' : 'Simpan draft kuota #').$forecast->id_forecast);
        });
        if ($setujui) {
            return redirect()->route('e-po.logistik.antrean')->with('success', 'Kuota ditetapkan. Distributor kini dapat membuat E-PO.');
        }

        return redirect()->route('e-po.logistik.detail-forecast', $forecast)->with('success', 'Draft penetapan kuota tersimpan.');
    }

    public function verifikasiEpo(PurchaseOrder $po): View
    {
        abort_if($po->status === self::STATUS_DRAFT, 404);
        $po->load(['forecast.distributor.wilayah', 'forecast.distributor.penggunaRecord', 'forecast.detailForecastRecords', 'detailPoRecordsAsPo.produk', 'penyetuju']);
        $dok = $this->dokumenPo($po);
        $kuotaProduk = $po->forecast->detailForecastRecords->keyBy('id_produk');
        $pemeriksaan = [
            ['label' => 'Kuantitas tiap SKU tidak melebihi kuota yang ditetapkan', 'lolos' => collect($dok['rows'])->every(fn ($r) => $r['kontainer'] <= $r['kuota'])],
            ['label' => 'Seluruh produk tercantum pada forecast yang disetujui', 'lolos' => $po->detailPoRecordsAsPo->every(fn ($d) => $kuotaProduk->has($d->id_produk))],
            ['label' => 'Forecast induk berstatus Disetujui', 'lolos' => $po->forecast->status === self::STATUS_DISETUJUI],
            ['label' => 'Distributor aktif dan memiliki nomor MoU', 'lolos' => (bool) $po->forecast->distributor->status_aktif && filled($po->forecast->distributor->nomor_mou)],
        ];

        return view('pages.e-po.logistik.verifikasi-epo', [
            'po' => $po, 'dok' => $dok, 'pemeriksaan' => $pemeriksaan,
            'semuaLolos' => collect($pemeriksaan)->every(fn ($p) => $p['lolos']),
            'pabrik' => self::PABRIK, 'bisaDisetujui' => in_array($po->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES], true),
        ]);
    }

    public function mulaiEpo(PurchaseOrder $po): RedirectResponse
    {
        abort_if($po->status !== self::STATUS_DIAJUKAN, 422, 'E-PO ini tidak sedang menunggu diproses.');
        $po->update(['status' => self::STATUS_DIPROSES]);

        return redirect()->route('e-po.logistik.verifikasi-epo', $po)->with('success', 'E-PO masuk status sedang diproses.');
    }

    public function setujuiEpo(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $request->validate(['konfirmasi' => ['required', 'accepted']],
            ['konfirmasi.accepted' => 'Centang konfirmasi stok dan jadwal sebelum menyetujui.', 'konfirmasi.required' => 'Centang konfirmasi stok dan jadwal sebelum menyetujui.']);
        $pengguna = $this->penggunaLogistik();
        abort_unless($pengguna, 422, 'Belum ada pengguna aktif berperan Logistik Pusat.');
        DB::transaction(function () use ($po, $pengguna) {
            $po = PurchaseOrder::query()->whereKey($po->id_po)->lockForUpdate()->firstOrFail();
            if (! in_array($po->status, [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES], true)) {
                throw ValidationException::withMessages(['konfirmasi' => 'E-PO ini tidak sedang menunggu verifikasi.']);
            }
            $po->update(['status' => self::STATUS_DISETUJUI, 'tanggal_disetujui' => now(), 'id_penyetuju' => $pengguna->id_pengguna]);
            $this->catat($pengguna, 'Setujui E-PO '.$po->nomor_po);
        });

        return redirect()->route('e-po.logistik.antrean')->with('success', 'E-PO '.$po->nomor_po.' disetujui dan dokumen PO resmi diterbitkan.');
    }

    private function penggunaLogistik(): ?Pengguna
    {
        return Pengguna::query()->where('status_aktif', true)
            ->whereHas('peran', fn ($q) => $q->where('nama_peran', 'Logistik Pusat'))
            ->orderBy('id_pengguna')->first();
    }

    private function kapasitasTerpakai(CarbonInterface $periode, ?int $kecualiForecast = null): int
    {
        return (int) DetailForecast::query()->whereHas('forecast', function ($query) use ($periode, $kecualiForecast) {
            $query->whereDate('periode', $periode->toDateString())->where('status', self::STATUS_DISETUJUI)
                ->when($kecualiForecast, fn ($q) => $q->where('id_forecast', '!=', $kecualiForecast));
        })->sum('jumlah_disetujui');
    }

    private function totalForecast(Forecast $forecast): array
    {
        $detail = $forecast->detailForecastRecords;

        return [
            'usulan' => (int) $detail->sum('jumlah_kontainer'),
            'disetujui' => (int) $detail->sum('jumlah_disetujui'),
            'nilai_usulan' => $detail->sum(fn (DetailForecast $d) => $this->nilai((int) $d->jumlah_kontainer, $d->produk)),
            'nilai_kuota' => $detail->sum(fn (DetailForecast $d) => $this->nilai((int) $d->jumlah_disetujui, $d->produk)),
        ];
    }

    private function nilai(int $kontainer, Produk $produk): float
    {
        return $kontainer * $produk->karton_per_kontainer * (float) $produk->harga;
    }

    private function dokumenPo(PurchaseOrder $po): array
    {
        $kuota = $po->forecast->detailForecastRecords->keyBy('id_produk');
        $rows = [];
        foreach ($po->detailPoRecordsAsPo->sortBy(fn ($d) => $d->produk->kode_sku)->values() as $i => $detail) {
            $produk = $detail->produk;
            $karton = $detail->jumlah_kontainer * $produk->karton_per_kontainer;
            $rows[] = ['no' => $i + 1, 'kode' => $produk->kode_sku, 'nama' => $produk->nama_produk,
                'kemasan' => number_format($produk->karton_per_kontainer, 0, ',', '.').' karton / kontainer',
                'kontainer' => $detail->jumlah_kontainer, 'karton' => $karton, 'harga' => (float) $produk->harga,
                'subtotal' => $karton * (float) $produk->harga,
                'kuota' => (int) ($kuota[$produk->id_produk]->jumlah_disetujui ?? 0)];
        }
        $rows = collect($rows);

        return ['rows' => $rows->all(), 'total_kontainer' => (int) $rows->sum('kontainer'),
            'total_karton' => (int) $rows->sum('karton'), 'total_nilai' => (float) $rows->sum('subtotal'),
            'total_kuota' => (int) $rows->sum('kuota')];
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
}
