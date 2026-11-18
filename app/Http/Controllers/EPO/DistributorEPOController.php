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

class DistributorEPOController extends Controller implements HasMiddleware
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_DIAJUKAN = 'Diajukan';

    public const STATUS_DIPROSES = 'Diproses';

    public const STATUS_DISETUJUI = 'Disetujui';

    private const KAPASITAS_BULANAN = 60;

    private const BATAS_PENGAJUAN_TANGGAL = 25;

    private const BULAN_RIWAYAT_PENJUALAN = 3;

    private const STOK_PENGAMAN = 0.10;

    private const AWALAN_PO = 'PO-YFI';

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

    public function hitungForecast(Request $request): View
    {
        $konteks = $this->konteksDistributor($request);
        if (! $konteks) {
            return view('pages.e-po.distributor.hitung-forecast');
        }
        $distributor = $konteks['distributor'];
        $request->validate(['periode' => ['nullable', 'date_format:Y-m']]);
        $periode = $this->periodeTarget($request->query('periode'));
        $tenggat = $this->tenggatPengajuan($periode);
        $forecastTarget = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->whereDate('periode', $periode->toDateString())->with('purchaseOrderRecords')->first();
        $riwayat = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->withSum('detailForecastRecords as total_usulan', 'jumlah_kontainer')
            ->withSum('detailForecastRecords as total_disetujui', 'jumlah_disetujui')
            ->with(['detailForecastRecords.produk', 'purchaseOrderRecords'])->orderByDesc('periode')->limit(6)->get();
        $siapInputEpo = $this->forecastSiapPo($distributor);
        $terakhirBerPo = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->where('status', self::STATUS_DISETUJUI)
            ->whereHas('purchaseOrderRecords', fn ($q) => $q->where('status', self::STATUS_DISETUJUI))
            ->with(['detailForecastRecords', 'purchaseOrderRecords.detailPoRecordsAsPo'])
            ->orderByDesc('periode')->first();
        $realisasi = null;
        if ($terakhirBerPo) {
            $kuota = (int) $terakhirBerPo->detailForecastRecords->sum('jumlah_disetujui');
            $dipesan = (int) $terakhirBerPo->purchaseOrderRecords
                ->where('status', self::STATUS_DISETUJUI)
                ->sum(fn (PurchaseOrder $po) => $po->detailPoRecordsAsPo->sum('jumlah_kontainer'));
            $realisasi = ['periode' => $terakhirBerPo->periode, 'kuota' => $kuota, 'dipesan' => $dipesan,
                'persen' => $kuota > 0 ? (int) round($dipesan / $kuota * 100) : 0];
        }
        $periodePilihan = collect(range(-1, 5))
            ->map(fn (int $offset) => CarbonImmutable::now()->startOfMonth()->addMonthsNoOverflow($offset))
            ->filter(fn (CarbonImmutable $candidate) => $this->tahunFiskalUntuk($candidate))
            ->values();

        return view('pages.e-po.distributor.hitung-forecast', $konteks + [
            'periode' => $periode, 'tenggat' => $tenggat,
            'hariSisa' => (int) now()->startOfDay()->diffInDays($tenggat->startOfDay(), false),
            'forecastTarget' => $forecastTarget, 'riwayat' => $riwayat,
            'tahunFiskal' => $this->tahunFiskalUntuk($periode),
            'siapInputEpo' => $siapInputEpo, 'realisasi' => $realisasi,
            'statusLabel' => $forecastTarget ? $this->statusForecast($forecastTarget->status) : null,
            'poTarget' => $forecastTarget?->purchaseOrderRecords->first(fn (PurchaseOrder $po) => $po->status !== self::STATUS_DRAFT),
            'periodePilihan' => $periodePilihan,
        ]);
    }

    public function ajukanForecast(Request $request): View
    {
        $konteks = $this->konteksDistributor($request);
        if (! $konteks) {
            return view('pages.e-po.distributor.hitung-forecast');
        }
        $request->validate(['periode' => ['nullable', 'date_format:Y-m']]);
        $distributor = $konteks['distributor'];
        $periode = $this->periodeTarget($request->query('periode'));
        $forecast = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->whereDate('periode', $periode->toDateString())->with('detailForecastRecords')->first();
        $editable = ! $forecast || $forecast->status === self::STATUS_DRAFT;
        $tersimpan = $forecast?->detailForecastRecords->keyBy('id_produk') ?? collect();
        $rekomendasi = $this->rekomendasi($distributor->id_distributor);
        $usulanLama = is_array(old('usulan')) ? old('usulan') : [];
        $baris = Produk::query()->orderBy('kode_sku')->get()->map(function (Produk $produk) use ($rekomendasi, $tersimpan, $usulanLama) {
            $rek = $rekomendasi[$produk->id_produk] ?? ['rata_rata' => 0, 'stok_layak_jual' => 0, 'kebutuhan_karton' => 0, 'kontainer' => 0];
            $tersimpanProduk = $tersimpan[$produk->id_produk] ?? null;
            $adaUsulanLama = array_key_exists((string) $produk->id_produk, $usulanLama);
            $awal = $adaUsulanLama
                ? (int) $usulanLama[$produk->id_produk]
                : (int) ($tersimpanProduk->jumlah_kontainer ?? 0);
            $selected = $adaUsulanLama ? $awal > 0 : (bool) $tersimpanProduk;

            return ['id' => $produk->id_produk, 'kode' => $produk->kode_sku, 'nama' => $produk->nama_produk,
                'kategori' => $produk->kategori, 'kpk' => $produk->karton_per_kontainer, 'harga' => (float) $produk->harga,
                'rata_rata' => $rek['rata_rata'], 'stok' => $rek['stok_layak_jual'], 'kebutuhan_karton' => $rek['kebutuhan_karton'],
                'rekomendasi' => $rek['kontainer'], 'usulan' => $awal, 'selected' => $selected];
        })->values();

        return view('pages.e-po.distributor.ajukan-forecast', $konteks + [
            'periode' => $periode, 'tenggat' => $this->tenggatPengajuan($periode),
            'forecast' => $forecast, 'editable' => $editable, 'baris' => $baris,
            'kapasitas' => self::KAPASITAS_BULANAN, 'jumlahBulan' => self::BULAN_RIWAYAT_PENJUALAN,
        ]);
    }

    public function submitForecast(Request $request): RedirectResponse
    {
        $nilai = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'aksi' => ['required', 'in:draft,kirim'],
            'usulan' => ['nullable', 'array'],
            'usulan.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'konfirmasi' => ['nullable', 'required_if:aksi,kirim', 'accepted'],
        ], [
            'konfirmasi.accepted' => 'Centang konfirmasi kebenaran data cabang sebelum mengajukan forecast.',
            'konfirmasi.required_if' => 'Centang konfirmasi kebenaran data cabang sebelum mengajukan forecast.',
            'usulan.*.integer' => 'Jumlah usulan harus berupa bilangan bulat (kontainer).',
        ]);
        $distributor = $this->distributorAktif($request);
        abort_unless($distributor?->penggunaRecord, 422, 'Distributor belum memiliki akun pengguna.');
        $periode = $this->periodeTarget($nilai['periode']);
        $tahunFiskal = $this->tahunFiskalUntuk($periode);
        if (! $tahunFiskal) {
            throw ValidationException::withMessages(['periode' => 'Periode ini belum berada dalam tahun fiskal yang terdaftar.']);
        }
        $usulan = collect($nilai['usulan'] ?? [])->map(fn ($v) => (int) $v)->filter(fn ($v) => $v > 0);
        $produkValid = Produk::query()->whereIn('id_produk', $usulan->keys())->pluck('id_produk');
        if ($produkValid->count() !== $usulan->count()) {
            throw ValidationException::withMessages(['usulan' => 'Terdapat produk yang tidak dikenal.']);
        }
        $kirim = $nilai['aksi'] === 'kirim';
        if ($kirim && $usulan->isEmpty()) {
            throw ValidationException::withMessages(['usulan' => 'Isi minimal satu SKU dengan jumlah lebih dari 0 kontainer.']);
        }
        DB::transaction(function () use ($distributor, $periode, $tahunFiskal, $usulan, $kirim) {
            $forecast = Forecast::query()->where('id_distributor', $distributor->id_distributor)
                ->whereDate('periode', $periode->toDateString())->lockForUpdate()->first();
            if ($forecast && $forecast->status !== self::STATUS_DRAFT) {
                throw ValidationException::withMessages(['periode' => 'Forecast periode ini sudah diajukan dan tidak dapat diubah.']);
            }
            $forecast ??= new Forecast(['id_distributor' => $distributor->id_distributor, 'periode' => $periode->toDateString()]);
            $forecast->fill(['id_tahun_fiskal' => $tahunFiskal->id_tahun_fiskal, 'tanggal_pengajuan' => now(),
                'status' => $kirim ? self::STATUS_DIAJUKAN : self::STATUS_DRAFT])->save();
            DetailForecast::query()->where('id_forecast', $forecast->id_forecast)->delete();
            foreach ($usulan as $idProduk => $kontainer) {
                DetailForecast::query()->create(['id_forecast' => $forecast->id_forecast, 'id_produk' => $idProduk, 'jumlah_kontainer' => $kontainer]);
            }
            $this->catat($distributor->penggunaRecord, ($kirim ? 'Ajukan' : 'Simpan draft').' forecast '.$periode->format('Y-m'));
        });
        if ($kirim) {
            return redirect()->route('e-po.distributor.hitung-forecast')->with('success', 'Forecast '.$this->tanggal($periode, 'F Y').' berhasil diajukan. Menunggu penetapan kuota Logistik Pusat.');
        }

        return redirect()->route('e-po.distributor.ajukan-forecast', ['periode' => $periode->format('Y-m')])->with('success', 'Draft forecast tersimpan.');
    }

    public function hapusForecast(Request $request, Forecast $forecast): RedirectResponse
    {
        $distributor = $this->distributorAktif($request);
        abort_unless($distributor && $forecast->id_distributor === $distributor->id_distributor, 403);
        abort_unless($forecast->status === self::STATUS_DRAFT, 422, 'Hanya draft forecast yang dapat dihapus.');

        DB::transaction(function () use ($forecast, $distributor) {
            $forecast->detailForecastRecords()->delete();
            $forecast->delete();
            if ($distributor->penggunaRecord) {
                $this->catat($distributor->penggunaRecord, 'Hapus draft forecast '.$forecast->periode->format('Y-m'));
            }
        });

        return redirect()->route('e-po.distributor.hitung-forecast')->with('success', 'Draft forecast berhasil dihapus.');
    }

    public function inputEpo(Request $request): View
    {
        $konteks = $this->konteksDistributor($request);
        if (! $konteks) {
            return view('pages.e-po.distributor.hitung-forecast');
        }
        $distributor = $konteks['distributor'];
        $forecast = $this->forecastSiapPo($distributor);
        $baris = collect();
        $draft = null;
        if ($forecast) {
            $draft = PurchaseOrder::query()->where('id_forecast', $forecast->id_forecast)
                ->where('status', self::STATUS_DRAFT)->with('detailPoRecordsAsPo')->first();
            $tersimpan = $draft?->detailPoRecordsAsPo->keyBy('id_produk') ?? collect();
            $terpakai = $this->terpakaiPerProduk($forecast);
            $baris = $forecast->detailForecastRecords->filter(fn ($d) => $d->jumlah_disetujui > 0)
                ->sortBy(fn ($d) => $d->produk->kode_sku)->map(function (DetailForecast $d) use ($tersimpan, $terpakai) {
                    $produk = $d->produk;
                    $sisa = max(0, (int) $d->jumlah_disetujui - (int) ($terpakai[$d->id_produk] ?? 0));

                    return ['id' => $produk->id_produk, 'kode' => $produk->kode_sku, 'nama' => $produk->nama_produk,
                        'kpk' => $produk->karton_per_kontainer, 'harga' => (float) $produk->harga, 'kuota' => $sisa,
                        'input' => (int) old('qty.'.$produk->id_produk, $tersimpan[$produk->id_produk]->jumlah_kontainer ?? $sisa)];
                })->values();
        }

        return view('pages.e-po.distributor.input-epo', $konteks + ['forecast' => $forecast, 'draft' => $draft, 'baris' => $baris]);
    }

    public function submitEpo(Request $request): RedirectResponse
    {
        $nilai = $request->validate([
            'id_forecast' => ['required', 'integer', 'exists:forecast,id_forecast'],
            'aksi' => ['required', 'in:draft,kirim'],
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], ['qty.*.integer' => 'Kuantitas pesanan harus berupa bilangan bulat (kontainer).']);
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
            $terpakai = $this->terpakaiPerProduk($forecast);
            $jumlah = [];
            $galat = [];
            foreach ($forecast->detailForecastRecords as $detail) {
                $qty = (int) ($nilai['qty'][$detail->id_produk] ?? 0);
                $sisa = max(0, (int) $detail->jumlah_disetujui - (int) ($terpakai[$detail->id_produk] ?? 0));
                if ($qty > $sisa) {
                    $galat['qty.'.$detail->id_produk] = $detail->produk->kode_sku.': pesanan '.$qty.' kontainer melebihi sisa kuota '.$sisa.' kontainer.';
                }
                if ($qty > 0) {
                    $jumlah[$detail->id_produk] = $qty;
                }
            }
            if ($galat) {
                throw ValidationException::withMessages($galat);
            }
            if ($kirim && ! $jumlah) {
                throw ValidationException::withMessages(['qty' => 'Isi minimal satu SKU dengan jumlah lebih dari 0 kontainer.']);
            }
            $po = PurchaseOrder::query()->where('id_forecast', $forecast->id_forecast)
                ->where('status', self::STATUS_DRAFT)->lockForUpdate()->first()
                ?? new PurchaseOrder(['id_forecast' => $forecast->id_forecast, 'nomor_po' => $this->nomorPoBaru()]);
            $po->fill(['tanggal_pengajuan' => now(), 'status' => $kirim ? self::STATUS_DIAJUKAN : self::STATUS_DRAFT])->save();
            DetailPo::query()->where('id_po', $po->id_po)->delete();
            foreach ($jumlah as $idProduk => $kontainer) {
                DetailPo::query()->create(['id_po' => $po->id_po, 'id_produk' => $idProduk, 'jumlah_kontainer' => $kontainer]);
            }
            $this->catat($distributor->penggunaRecord, ($kirim ? 'Kirim' : 'Simpan draft').' E-PO '.$po->nomor_po);

            return $po;
        });
        if ($kirim) {
            return redirect()->route('e-po.distributor.epo-selesai', $po)->with('success', 'E-PO '.$po->nomor_po.' terkirim ke Logistik Pusat untuk diverifikasi.');
        }

        return redirect()->route('e-po.distributor.input-epo')->with('success', 'Draft E-PO tersimpan.');
    }

    public function hapusEpo(Request $request, PurchaseOrder $po): RedirectResponse
    {
        $distributor = $this->distributorAktif($request);
        $po->load('forecast');
        abort_unless($distributor && $po->forecast->id_distributor === $distributor->id_distributor, 403);
        abort_unless($po->status === self::STATUS_DRAFT, 422, 'Hanya draft E-PO yang dapat dihapus.');

        DB::transaction(function () use ($po, $distributor) {
            $po->detailPoRecordsAsPo()->delete();
            $po->delete();
            if ($distributor->penggunaRecord) {
                $this->catat($distributor->penggunaRecord, 'Hapus draft E-PO '.$po->nomor_po);
            }
        });

        return redirect()->route('e-po.distributor.input-epo')->with('success', 'Draft E-PO berhasil dihapus.');
    }

    public function epoSelesai(Request $request, ?PurchaseOrder $po = null): View
    {
        $konteks = $this->konteksDistributor($request);
        if (! $konteks) {
            return view('pages.e-po.distributor.hitung-forecast');
        }
        $distributor = $konteks['distributor'];
        $relasi = ['forecast.distributor.wilayah', 'forecast.distributor.penggunaRecord', 'forecast.detailForecastRecords', 'detailPoRecordsAsPo.produk', 'penyetuju'];
        if ($po) {
            $po->load($relasi);
            abort_unless($po->forecast->id_distributor === $distributor->id_distributor, 403, 'PO bukan milik distributor aktif.');
        } else {
            $po = PurchaseOrder::query()->whereHas('forecast', fn ($q) => $q->where('id_distributor', $distributor->id_distributor))
                ->where('status', '!=', self::STATUS_DRAFT)->with($relasi)
                ->orderByRaw("CASE status WHEN 'Disetujui' THEN 0 ELSE 1 END")->orderByDesc('tanggal_pengajuan')->first();
        }

        return view('pages.e-po.distributor.epo-selesai', $konteks + ['po' => $po, 'dok' => $po ? $this->dokumenPo($po) : null, 'pabrik' => self::PABRIK]);
    }

    public function ubahKonteks(Request $request): RedirectResponse
    {
        $nilai = $request->validate(['id_distributor' => ['required', 'integer', 'exists:distributor,id_distributor']]);
        $request->session()->put('active_distributor_id', (int) $nilai['id_distributor']);

        return redirect()->route('e-po.distributor.hitung-forecast')->with('success', 'Perusahaan simulasi aktif diperbarui.');
    }

    private function distributorAktif(Request $request): ?Distributor
    {
        $dipilih = $request->session()->get('active_distributor_id');

        return Distributor::query()->with('wilayah', 'penggunaRecord')->where('status_aktif', true)
            ->when($dipilih, fn ($q) => $q->whereKey($dipilih))->first()
            ?? Distributor::query()->with('wilayah', 'penggunaRecord')->where('status_aktif', true)->orderBy('id_distributor')->first();
    }

    private function konteksDistributor(Request $request): ?array
    {
        $distributor = $this->distributorAktif($request);

        return $distributor ? [
            'distributor' => $distributor,
            'daftarDistributor' => Distributor::query()->where('status_aktif', true)->orderBy('nama')->get(['id_distributor', 'nama']),
        ] : null;
    }

    private function forecastSiapPo(Distributor $distributor): ?Forecast
    {
        $forecasts = Forecast::query()->where('id_distributor', $distributor->id_distributor)
            ->where('status', self::STATUS_DISETUJUI)
            ->with(['detailForecastRecords.produk', 'purchaseOrderRecords.detailPoRecordsAsPo'])
            ->orderBy('periode')->get();

        return $forecasts->first(fn (Forecast $forecast) => $forecast->detailForecastRecords->contains(
            fn (DetailForecast $detail) => (int) $detail->jumlah_disetujui > (int) ($this->terpakaiPerProduk($forecast)[$detail->id_produk] ?? 0)
        ));
    }

    /**
     * Return submitted or approved quantities grouped by product.
     */
    private function terpakaiPerProduk(Forecast $forecast): Collection
    {
        return $forecast->purchaseOrderRecords
            ->whereIn('status', [self::STATUS_DIAJUKAN, self::STATUS_DIPROSES, self::STATUS_DISETUJUI])
            ->flatMap(fn (PurchaseOrder $po) => $po->detailPoRecordsAsPo)
            ->groupBy('id_produk')
            ->map(fn (Collection $details) => (int) $details->sum('jumlah_kontainer'));
    }

    private function periodeTarget(?string $input = null): CarbonImmutable
    {
        if ($input && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $input)) {
            return CarbonImmutable::createFromFormat('!Y-m', $input);
        }

        return CarbonImmutable::now()->startOfMonth()->addMonthNoOverflow();
    }

    private function tenggatPengajuan(CarbonImmutable $periode): CarbonImmutable
    {
        $bulanSebelumnya = $periode->subMonthNoOverflow()->startOfMonth();
        $hari = min(self::BATAS_PENGAJUAN_TANGGAL, $bulanSebelumnya->daysInMonth);

        return $bulanSebelumnya->setDay($hari)->setTime(17, 0);
    }

    private function tahunFiskalUntuk(CarbonInterface $tanggal): ?TahunFiskal
    {
        return TahunFiskal::query()->whereDate('tanggal_mulai', '<=', $tanggal->toDateString())
            ->whereDate('tanggal_selesai', '>=', $tanggal->toDateString())->orderByDesc('tanggal_mulai')->first();
    }

    private function rekomendasi(int $idDistributor): Collection
    {
        $awalBulanIni = CarbonImmutable::now()->startOfMonth();
        $mulai = $awalBulanIni->subMonthsNoOverflow(self::BULAN_RIWAYAT_PENJUALAN);
        $akhir = $awalBulanIni->subDay();
        $penjualan = SellOut::query()->where('id_distributor', $idDistributor)
            ->whereDate('tanggal', '>=', $mulai->toDateString())->whereDate('tanggal', '<=', $akhir->toDateString())
            ->selectRaw('id_produk, YEAR(tanggal) AS tahun, MONTH(tanggal) AS bulan, SUM(jumlah) AS total')
            ->groupBy('id_produk', 'tahun', 'bulan')->get()
            ->groupBy('id_produk');
        $stok = StokGudang::query()->where('id_distributor', $idDistributor)->whereDate('periode', '<=', $akhir->toDateString())
            ->orderByDesc('periode')->get()->unique('id_produk')->keyBy('id_produk');

        return Produk::query()->orderBy('kode_sku')->get()->mapWithKeys(function (Produk $produk) use ($penjualan, $stok, $mulai) {
            $bulanan = collect(range(0, self::BULAN_RIWAYAT_PENJUALAN - 1))->map(function (int $offset) use ($penjualan, $produk, $mulai) {
                $bulan = $mulai->addMonthsNoOverflow($offset);
                $baris = collect($penjualan[$produk->id_produk] ?? [])->first(fn ($item) => (int) $item->tahun === $bulan->year && (int) $item->bulan === $bulan->month);

                return (float) ($baris->total ?? 0);
            });
            $bobotTotal = 1 + 2 + 3;
            $rataRata = ($bulanan[0] + ($bulanan[1] * 2) + ($bulanan[2] * 3)) / $bobotTotal;
            $stokLayak = (int) ($stok[$produk->id_produk]->stok_layak_jual ?? 0);
            $kebutuhan = max(0.0, ($rataRata * (1 + self::STOK_PENGAMAN)) - $stokLayak);

            return [$produk->id_produk => ['rata_rata' => round($rataRata, 1), 'stok_layak_jual' => $stokLayak,
                'kebutuhan_karton' => (int) ceil($kebutuhan),
                'kontainer' => (int) ceil($kebutuhan / $produk->karton_per_kontainer)]];
        });
    }

    private function statusForecast(string $status): array
    {
        return match ($status) {
            self::STATUS_DRAFT => ['label' => 'Draft', 'class' => 'bg-slate-100 text-slate-700'],
            self::STATUS_DIAJUKAN => ['label' => 'Menunggu penetapan', 'class' => 'bg-amber-50 text-amber-700'],
            self::STATUS_DIPROSES => ['label' => 'Sedang diproses', 'class' => 'bg-blue-50 text-blue-700'],
            self::STATUS_DISETUJUI => ['label' => 'Disetujui', 'class' => 'bg-emerald-50 text-emerald-700'],
            default => ['label' => $status, 'class' => 'bg-slate-100 text-slate-700'],
        };
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

    private function nomorPoBaru(): string
    {
        $awalan = self::AWALAN_PO.'-'.now()->year.'-';
        $terakhir = PurchaseOrder::query()->where('nomor_po', 'like', $awalan.'%')->lockForUpdate()->orderByDesc('nomor_po')->value('nomor_po');
        $urutan = $terakhir ? ((int) Str::afterLast($terakhir, '-')) + 1 : 1;

        return $awalan.str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);
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
