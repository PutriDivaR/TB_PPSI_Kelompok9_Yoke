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
    public static function hitungRekomendasi($distributorId, $periode): array
    {
        $carbonPeriode = Carbon::parse($periode);
        $startDate = $carbonPeriode->copy()->subMonths(3)->startOfMonth();
        $endDate = $carbonPeriode->copy()->subMonth()->endOfMonth();

        $produks = Produk::all();
        $rekomendasi = [];

        foreach ($produks as $produk) {
            // Total Sell Out 3 bulan terakhir
            $totalSellOut = SellOut::where('id_distributor', $distributorId)
                ->where('id_produk', $produk->id_produk)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->sum('jumlah');

            $rataRataSellOut = $totalSellOut / 3;

            // Stok layak jual terbaru
            $stok = StokGudang::where('id_distributor', $distributorId)
                ->where('id_produk', $produk->id_produk)
                ->orderBy('periode', 'desc')
                ->first();

            $stokLayak = $stok ? $stok->stok_layak_jual : 0;

            // Rekomendasi dalam kontainer
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
