<?php

namespace App\Services;

use App\Models\DetailForecast;
use App\Models\Forecast;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use App\Models\SellOut;
use App\Models\StokGudang;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class EpoService
{
    public const KARTON_PER_KONTAINER = 2000;

    public const MINIMUM_ORDER_KARTON = 2000;

    public const KAPASITAS_BULANAN_KARTON = 30000;

    public const CUTOFF_DAY = 15;

    public const LEAD_TIME_MIN_MONTHS = 2;

    public const LEAD_TIME_MAX_MONTHS = 3;

    public const ROLLING_PLANNING_MAX_MONTHS = 7;

    public const BULAN_RIWAYAT_PENJUALAN = 3;

    public const STOK_PENGAMAN = 0.10;

    public static function kodeForecast(Forecast $forecast): string
    {
        return 'FC-'.$forecast->id_forecast.'-'.Carbon::parse($forecast->periode)->format('Ym');
    }

    public static function periodeMinimumForecast(?CarbonInterface $acuan = null): CarbonImmutable
    {
        $tanggal = CarbonImmutable::instance($acuan ?? now());
        $periode = $tanggal->startOfMonth()->addMonthsNoOverflow(self::LEAD_TIME_MIN_MONTHS);

        return $tanggal->day > self::CUTOFF_DAY ? $periode->addMonthNoOverflow() : $periode;
    }

    public static function periodeMaximumForecast(?CarbonInterface $acuan = null): CarbonImmutable
    {
        $tanggal = CarbonImmutable::instance($acuan ?? now());
        $offset = self::ROLLING_PLANNING_MAX_MONTHS - ($tanggal->day > self::CUTOFF_DAY ? 0 : 1);

        return $tanggal->startOfMonth()->addMonthsNoOverflow($offset);
    }

    public static function periodeMaximumEpo(?CarbonInterface $acuan = null): CarbonImmutable
    {
        $tanggal = CarbonImmutable::instance($acuan ?? now());
        $offset = self::LEAD_TIME_MAX_MONTHS + ($tanggal->day > self::CUTOFF_DAY ? 1 : 0);

        return $tanggal->startOfMonth()->addMonthsNoOverflow($offset);
    }

    public static function periodeForecastDiizinkan(CarbonInterface $periode, ?CarbonInterface $acuan = null): bool
    {
        $target = CarbonImmutable::instance($periode)->startOfMonth();

        return $target->betweenIncluded(
            self::periodeMinimumForecast($acuan),
            self::periodeMaximumForecast($acuan)
        );
    }

    public static function periodeEpoDiizinkan(CarbonInterface $periode, ?CarbonInterface $acuan = null): bool
    {
        $target = CarbonImmutable::instance($periode)->startOfMonth();

        return $target->betweenIncluded(
            self::periodeMinimumForecast($acuan),
            self::periodeMaximumEpo($acuan)
        );
    }

    public static function lockForecastsForPeriod(CarbonInterface $periode): void
    {
        Forecast::query()
            ->whereDate('periode', CarbonImmutable::instance($periode)->startOfMonth()->toDateString())
            ->orderBy('id_forecast')
            ->lockForUpdate()
            ->pluck('id_forecast');
    }

    public static function kapasitasTerpakai(CarbonInterface $periode, ?int $kecualiForecast = null): int
    {
        return (int) DetailForecast::query()->whereHas('forecast', function ($query) use ($periode, $kecualiForecast) {
            $query->whereDate('periode', CarbonImmutable::instance($periode)->startOfMonth()->toDateString())
                ->where('status', 'Disetujui')
                ->when($kecualiForecast, fn ($q) => $q->where('id_forecast', '!=', $kecualiForecast));
        })->sum('jumlah_disetujui');
    }

    public static function sisaKapasitasForecast(Forecast $forecast): int
    {
        return max(0, self::KAPASITAS_BULANAN_KARTON - self::kapasitasTerpakai($forecast->periode, $forecast->id_forecast));
    }

    public static function generateNomorPo(): string
    {
        $tahun = date('Y');
        $count = PurchaseOrder::whereYear('tanggal_pengajuan', $tahun)->count() + 1;

        return 'PO-YFI-'.$tahun.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Weighted sell-out in the same seasonal three-month period, plus safety stock, minus sellable stock.
    public static function hitungRekomendasi(int $distributorId, string $periode): array
    {
        $target = CarbonImmutable::parse($periode)->startOfMonth()->subYearNoOverflow();
        $startDate = $target->startOfMonth()->subMonthsNoOverflow(self::BULAN_RIWAYAT_PENJUALAN - 1);
        $endDate = $target->endOfMonth();
        $monthlyWeights = [];
        $weightTotal = 0;
        for ($month = 0; $month < self::BULAN_RIWAYAT_PENJUALAN; $month++) {
            $weight = $month + 1;
            $monthlyWeights[$startDate->addMonthsNoOverflow($month)->format('Y-m')] = $weight;
            $weightTotal += $weight;
        }
        $sellOutByProduct = SellOut::query()->where('id_distributor', $distributorId)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['id_produk', 'tanggal', 'jumlah'])
            ->groupBy('id_produk')
            ->map(fn ($rows) => $rows->groupBy(fn ($row) => CarbonImmutable::parse($row->tanggal)->format('Y-m'))
                ->map(fn ($monthRows) => (float) $monthRows->sum('jumlah')));
        $stockByProduct = StokGudang::query()->where('id_distributor', $distributorId)
            ->whereDate('periode', '<=', $endDate->toDateString())
            ->orderByDesc('periode')->get()->unique('id_produk')->keyBy('id_produk');
        $produks = Produk::query()->orderBy('kode_sku')->get();
        $rekomendasi = [];

        foreach ($produks as $produk) {
            $monthlySales = $sellOutByProduct->get($produk->id_produk, collect());
            $rataRataSellOut = collect($monthlyWeights)->sum(
                fn (int $weight, string $month) => (float) ($monthlySales[$month] ?? 0) * $weight
            ) / $weightTotal;
            $stok = $stockByProduct->get($produk->id_produk);
            $stokLayak = (int) ($stok?->stok_layak_jual ?? 0);
            $rekomendasiKarton = (int) ceil(max(0, $rataRataSellOut * (1 + self::STOK_PENGAMAN) - $stokLayak));

            $rekomendasi[] = [
                'id_produk' => $produk->id_produk,
                'kode_sku' => $produk->kode_sku,
                'nama_produk' => $produk->nama_produk,
                'karton_per_kontainer' => $produk->karton_per_kontainer,
                'stok_layak_jual' => $stokLayak,
                'rata_rata_sell_out' => round($rataRataSellOut, 1),
                'rekomendasi' => $rekomendasiKarton,
            ];
        }

        return $rekomendasi;
    }
}
