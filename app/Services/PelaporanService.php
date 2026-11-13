<?php

namespace App\Services;

use App\Models\Distributor;
use App\Models\Produk;
use App\Models\SellOut;
use App\Models\StokGudang;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PelaporanService
{
    /** @return Collection<int, array<string,mixed>> */
    public function summary(string $start, string $end, ?int $distributorId): Collection
    {
        $endTime = CarbonImmutable::parse($end)->endOfDay()->toDateTimeString();
        $distributors = Distributor::query()->when($distributorId, fn ($q) => $q->whereKey($distributorId))->with('wilayah')->get()->keyBy('id_distributor');
        $ids = $distributors->keys();
        $sellIn = DB::table('sell_in as si')
            ->join('detail_pengiriman as dg', 'dg.id_detail_pengiriman', '=', 'si.id_detail_pengiriman')
            ->join('detail_po as dp', 'dp.id_detail_po', '=', 'dg.id_detail_po')
            ->join('pengiriman as g', 'g.id_pengiriman', '=', 'dg.id_pengiriman')
            ->join('purchase_order as po', 'po.id_po', '=', 'g.id_po')
            ->join('forecast as f', 'f.id_forecast', '=', 'po.id_forecast')
            ->whereIn('f.id_distributor', $ids)->whereDate('si.tanggal', '>=', $start)->whereDate('si.tanggal', '<=', $end)
            ->selectRaw('f.id_distributor, dp.id_produk, SUM(si.jumlah) AS jumlah')
            ->groupBy('f.id_distributor', 'dp.id_produk')->get();
        $receipts = DB::table('detail_penerimaan as dt')
            ->join('penerimaan_barang as pb', 'pb.id_penerimaan', '=', 'dt.id_penerimaan')
            ->join('detail_pengiriman as dg', 'dg.id_detail_pengiriman', '=', 'dt.id_detail_pengiriman')
            ->join('detail_po as dp', 'dp.id_detail_po', '=', 'dg.id_detail_po')
            ->join('pengiriman as g', 'g.id_pengiriman', '=', 'pb.id_pengiriman')
            ->join('purchase_order as po', 'po.id_po', '=', 'g.id_po')
            ->join('forecast as f', 'f.id_forecast', '=', 'po.id_forecast')
            ->whereIn('f.id_distributor', $ids)->where('pb.tanggal_terima', '<=', $endTime)
            ->selectRaw('f.id_distributor, dp.id_produk, DATE(pb.tanggal_terima) AS tanggal, SUM(dt.jumlah_baik) AS jumlah')
            ->groupByRaw('f.id_distributor, dp.id_produk, DATE(pb.tanggal_terima)')->get();
        $sellOut = SellOut::query()->whereIn('id_distributor', $ids)->whereDate('tanggal', '<=', $end)->get();
        $stocks = StokGudang::query()->whereIn('id_distributor', $ids)->whereDate('periode', '<=', $end)->orderByDesc('periode')->get();
        $products = Produk::query()->get()->keyBy('id_produk');
        $key = fn ($record) => $record->id_distributor.':'.$record->id_produk;
        $keys = $sellIn->concat($receipts)->concat($sellOut)->concat($stocks)->map($key)->unique();

        return $keys->map(function (string $group) use ($sellIn, $receipts, $sellOut, $stocks, $key, $start, $end, $distributors, $products) {
            [$distributor,$product] = array_map('intval', explode(':', $group));
            $in = $sellIn->filter(fn ($r) => $key($r) === $group);
            $arrivals = $receipts->filter(fn ($r) => $key($r) === $group);
            $sales = $sellOut->filter(fn ($r) => $key($r) === $group);
            $snapshots = $stocks->filter(fn ($r) => $key($r) === $group);
            $closing = $snapshots->first();
            $opening = $snapshots->first(fn ($r) => $r->periode->format('Y-m-d') < $start);
            $expected = null;
            if ($opening) {
                $since = $opening->periode->format('Y-m-d');
                $expected = $opening->stok_layak_jual
                    + $arrivals->filter(fn ($r) => $r->tanggal > $since)->sum('jumlah')
                    - $sales->filter(fn ($r) => $r->tanggal->format('Y-m-d') > $since)->sum('jumlah');
            }
            $isClosingDate = $closing && $closing->periode->format('Y-m-d') === $end;
            $gap = $expected !== null && $isClosingDate ? (int) ($closing->stok_layak_jual - $expected) : null;

            return [
                'id_distributor' => $distributor,
                'distributor' => $distributors[$distributor]->nama,
                'wilayah' => $distributors[$distributor]->wilayah?->nama_wilayah,
                'produk' => $products[$product]->nama_produk,
                'sku' => $products[$product]->kode_sku,
                'sell_in' => (int) $in->sum('jumlah'),
                'sell_out' => (int) $sales->filter(fn ($r) => $r->tanggal->format('Y-m-d') >= $start)->sum('jumlah'),
                'penerimaan_baik' => (int) $arrivals->filter(fn ($r) => $r->tanggal >= $start)->sum('jumlah'),
                'stok_fisik' => $closing?->stok_fisik,
                'stok_layak_jual' => $closing?->stok_layak_jual,
                'tanggal_stok' => $closing?->periode->format('Y-m-d'),
                'ekspektasi_layak_jual' => $expected,
                'selisih' => $gap,
                'status' => $expected === null ? 'Perlu saldo awal' : (! $isClosingDate ? 'Perlu stok akhir periode' : ($gap === 0 ? 'Sesuai' : 'Perlu pemeriksaan')),
            ];
        })->values();
    }
}
