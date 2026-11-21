<?php

namespace App\Services;

use App\Models\Forecast;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use App\Models\SellOut;
use App\Models\StokGudang;
use Carbon\Carbon;

class EpoService
{
    public static function kodeForecast(Forecast $forecast): string
    {
        return 'FC-'.$forecast->id_forecast.'-'.Carbon::parse($forecast->periode)->format('Ym');
    }

    public static function generateNomorPo(): string
    {
        $tahun = date('Y');
        $count = PurchaseOrder::whereYear('tanggal_pengajuan', $tahun)->count() + 1;

        return 'PO-YFI-'.$tahun.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Logika perhitungan rekomendasi (Sell-out 3 bulan terakhir - stok layak jual) / karton_per_kontainer
    public static function hitungRekomendasi(int $distributorId, string $periode): array
    {
        $carbonPeriode = Carbon::parse($periode);
        $startDate = $carbonPeriode->copy()->subMonths(3)->startOfMonth();
        $endDate = $carbonPeriode->copy()->subMonth()->endOfMonth();
        $sellOutByProduct = SellOut::query()->where('id_distributor', $distributorId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('id_produk, SUM(jumlah) AS total')
            ->groupBy('id_produk')->pluck('total', 'id_produk');
        $stockByProduct = StokGudang::query()->where('id_distributor', $distributorId)
            ->whereDate('periode', '<=', $endDate->toDateString())
            ->orderByDesc('periode')->get()->unique('id_produk')->keyBy('id_produk');
        $produks = Produk::query()->orderBy('kode_sku')->get();
        $rekomendasi = [];

        foreach ($produks as $produk) {
            $totalSellOut = (int) $sellOutByProduct->get($produk->id_produk, 0);
            $rataRataSellOut = $totalSellOut / 3;
            $stok = $stockByProduct->get($produk->id_produk);
            $stokLayak = $stok ? $stok->stok_layak_jual : 0;
            $kebutuhanKarton = max(0, $rataRataSellOut - $stokLayak);
            $rekomendasiKontainer = ceil($kebutuhanKarton / $produk->karton_per_kontainer);

            $rekomendasi[] = [
                'id_produk' => $produk->id_produk,
                'kode_sku' => $produk->kode_sku,
                'nama_produk' => $produk->nama_produk,
                'karton_per_kontainer' => $produk->karton_per_kontainer,
                'stok_layak_jual' => $stokLayak,
                'rata_rata_sell_out' => round($rataRataSellOut),
                'rekomendasi' => $rekomendasiKontainer,
            ];
        }

        return $rekomendasi;
    }
}
