<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\SellOut;
use App\Models\StokGudang;
use App\Models\TahunFiskal;
use App\Services\PelaporanService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportingController extends Controller
{
    public function dashboard(Request $request, PelaporanService $reports): JsonResponse
    {
        [$start, $end, $distributorId] = $this->filters($request);
        $summary = $reports->summary($start, $end, $distributorId);
        $sellOut = SellOut::query()->whereDate('tanggal', '>=', $start)->whereDate('tanggal', '<=', $end)
            ->when($distributorId, fn ($query) => $query->where('id_distributor', $distributorId));
        $sellInQuery = DB::table('sell_in as si')
            ->join('detail_pengiriman as dg', 'dg.id_detail_pengiriman', '=', 'si.id_detail_pengiriman')
            ->join('detail_po as dp', 'dp.id_detail_po', '=', 'dg.id_detail_po')
            ->join('purchase_order as po', 'po.id_po', '=', 'dp.id_po')
            ->join('forecast as f', 'f.id_forecast', '=', 'po.id_forecast')
            ->whereDate('si.tanggal', '>=', $start)->whereDate('si.tanggal', '<=', $end)
            ->when($distributorId, fn ($query) => $query->where('f.id_distributor', $distributorId));
        $trendOut = (clone $sellOut)->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') AS period, SUM(jumlah) AS quantity")
            ->groupBy('period')->orderBy('period')->get()->keyBy('period');
        $trendIn = $sellInQuery->selectRaw("DATE_FORMAT(si.tanggal, '%Y-%m') AS period, SUM(si.jumlah) AS quantity")
            ->groupBy('period')->orderBy('period')->get()->keyBy('period');
        $periods = collect();
        $month = CarbonImmutable::parse($start)->startOfMonth();
        $lastMonth = CarbonImmutable::parse($end)->startOfMonth();
        while ($month <= $lastMonth) {
            $periods->push($month->format('Y-m'));
            $month = $month->addMonth();
        }

        return response()->json(['data' => [
            'period' => ['start' => $start, 'end' => $end],
            'totals' => [
                'sell_out' => (int) $sellOut->sum('jumlah'),
                'sell_in' => (int) $trendIn->sum('quantity'),
                'products' => $summary->pluck('id_produk')->unique()->count(),
                'distributors' => $summary->pluck('id_distributor')->unique()->count(),
            ],
            'trend' => $periods->map(fn (string $period) => [
                'period' => $period,
                'sell_in' => (int) ($trendIn[$period]->quantity ?? 0),
                'sell_out' => (int) ($trendOut[$period]->quantity ?? 0),
            ])->values(),
            'stock_reconciliation' => $summary,
        ]]);
    }

    public function summary(Request $request, PelaporanService $reports): JsonResponse
    {
        [$start, $end, $distributorId] = $this->filters($request);
        $rows = $reports->summary($start, $end, $distributorId)
            ->when($request->filled('id_produk'), fn ($collection) => $collection->where('id_produk', (int) $request->input('id_produk')))
            ->values();

        return response()->json(['data' => $rows, 'meta' => [
            'period' => ['start' => $start, 'end' => $end],
            'count' => $rows->count(),
        ]]);
    }

    public function storeSellOut(Request $request): JsonResponse
    {
        $values = $request->validate([
            'id_produk' => ['required', 'integer', 'exists:produk,id_produk'],
            'kanal' => ['required', 'in:Retail,General Trade,Modern Trade'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'tanggal' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        $user = $request->user();
        abort_unless($user->id_distributor, 422, 'Akun tidak terhubung ke perusahaan Distributor.');

        $sellOut = DB::transaction(fn () => SellOut::query()->create([
            ...$values,
            'id_distributor' => $user->id_distributor,
        ]));
        LogAktivitas::query()->create([
            'id_pengguna' => $user->id_pengguna, 'modul' => 'Pelaporan', 'aksi' => 'Catat Sell-Out', 'waktu' => now(),
        ]);

        return response()->json(['data' => $sellOut->load(['distributor', 'produk'])], 201);
    }

    public function storeStock(Request $request): JsonResponse
    {
        $values = $request->validate([
            'id_produk' => ['required', 'integer', 'exists:produk,id_produk'],
            'periode' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'stok_fisik' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'stok_layak_jual' => ['required', 'integer', 'min:0', 'lte:stok_fisik', 'max:2147483647'],
        ]);
        $user = $request->user();
        abort_unless($user->id_distributor, 422, 'Akun tidak terhubung ke perusahaan Distributor.');

        $stock = DB::transaction(function () use ($values, $user) {
            StokGudang::query()->upsert([[
                ...$values,
                'id_distributor' => $user->id_distributor,
            ]], ['id_distributor', 'id_produk', 'periode'], ['stok_fisik', 'stok_layak_jual']);

            return StokGudang::query()->where('id_distributor', $user->id_distributor)
                ->where('id_produk', $values['id_produk'])->whereDate('periode', $values['periode'])->firstOrFail();
        });
        LogAktivitas::query()->create([
            'id_pengguna' => $user->id_pengguna, 'modul' => 'Pelaporan', 'aksi' => 'Catat stok gudang', 'waktu' => now(),
        ]);

        return response()->json(['data' => $stock->load(['distributor', 'produk'])], 201);
    }

    public function export(Request $request, PelaporanService $reports): StreamedResponse
    {
        [$start, $end, $distributorId] = $this->filters($request);
        $rows = $reports->summary($start, $end, $distributorId);

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Distributor', 'Produk', 'SKU', 'Sell-In', 'Sell-Out', 'Penerimaan Baik', 'Stok Fisik', 'Stok Layak Jual', 'Tanggal Stok', 'Ekspektasi', 'Selisih', 'Status'], ';', '"', '');
            foreach ($rows as $row) {
                $values = [$row['distributor'], $row['produk'], $row['sku'], $row['sell_in'], $row['sell_out'], $row['penerimaan_baik'], $row['stok_fisik'], $row['stok_layak_jual'], $row['tanggal_stok'], $row['ekspektasi_layak_jual'], $row['selisih'], $row['status']];
                $values = array_map(fn ($value) => is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'".$value : $value, $values);
                fputcsv($output, $values, ';', '"', '');
            }
            fclose($output);
        }, 'laporan-yoke-'.$start.'-'.$end.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{string, string, ?int} */
    private function filters(Request $request): array
    {
        $values = $request->validate([
            'mulai' => ['nullable', 'date_format:Y-m-d'],
            'selesai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:mulai'],
            'id_distributor' => ['nullable', 'integer', 'exists:distributor,id_distributor'],
            'id_tahun_fiskal' => ['nullable', 'integer', 'exists:tahun_fiskal,id_tahun_fiskal'],
            'id_produk' => ['nullable', 'integer', 'exists:produk,id_produk'],
        ]);
        $year = isset($values['id_tahun_fiskal']) ? TahunFiskal::query()->findOrFail($values['id_tahun_fiskal']) : null;
        $start = $values['mulai'] ?? $year?->tanggal_mulai->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $end = $values['selesai'] ?? $year?->tanggal_selesai->format('Y-m-d') ?? now()->format('Y-m-d');
        abort_if($end < $start, 422, 'Tanggal akhir harus sama atau setelah tanggal mulai.');
        if ($year) {
            abort_if($start < $year->tanggal_mulai->format('Y-m-d') || $end > $year->tanggal_selesai->format('Y-m-d'), 422,
                'Tanggal filter harus berada dalam tahun fiskal yang dipilih.');
        }

        $distributorId = isset($values['id_distributor']) ? (int) $values['id_distributor'] : null;
        $user = $request->user();
        if ($user->peran->nama_peran === 'Distributor') {
            abort_unless($user->id_distributor, 422, 'Akun tidak terhubung ke perusahaan Distributor.');
            abort_if($distributorId && $distributorId !== (int) $user->id_distributor, 403, 'Anda hanya dapat melihat data perusahaan sendiri.');
            $distributorId = (int) $user->id_distributor;
        }

        return [$start, $end, $distributorId];
    }
}
