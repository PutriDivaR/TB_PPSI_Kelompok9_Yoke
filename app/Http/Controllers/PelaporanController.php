<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSellOutRequest;
use App\Http\Requests\StoreStokGudangRequest;
use App\Models\Distributor;
use App\Models\Produk;
use App\Models\SellOut;
use App\Models\StokGudang;
use App\Models\TahunFiskal;
use App\Services\PelaporanService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PelaporanController extends Controller
{
    private function localOnly(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403, 'Integrasi lokal belum memakai autentikasi produksi.');
    }

    private function role(Request $request): string
    {
        return $request->session()->get('mock_role', 'distributor');
    }

    private function activeDistributor(Request $request): ?int
    {
        $selected = $request->session()->get('active_distributor_id');

        return Distributor::query()->where('status_aktif', true)->when($selected, fn ($q) => $q->whereKey($selected))->value('id_distributor')
            ?? Distributor::query()->where('status_aktif', true)->value('id_distributor');
    }

    /** @return array{0:string,1:string,2:?int} */
    private function filters(Request $request): array
    {
        $input = $request->validate([
            'mulai' => ['nullable', 'date_format:Y-m-d'],
            'selesai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:mulai'],
            'id_distributor' => ['nullable', 'integer', 'exists:distributor,id_distributor'],
            'id_tahun_fiskal' => ['nullable', 'integer', 'exists:tahun_fiskal,id_tahun_fiskal'],
        ]);
        $year = isset($input['id_tahun_fiskal']) ? TahunFiskal::findOrFail($input['id_tahun_fiskal']) : null;
        $start = $input['mulai'] ?? $year?->tanggal_mulai->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d');
        $end = $input['selesai'] ?? $year?->tanggal_selesai->format('Y-m-d') ?? now()->format('Y-m-d');
        abort_if($end < $start, 422, 'Tanggal akhir harus setelah tanggal mulai.');
        if ($year) {
            abort_if($start < $year->tanggal_mulai->format('Y-m-d') || $end > $year->tanggal_selesai->format('Y-m-d'), 422, 'Tanggal filter harus berada dalam tahun fiskal yang dipilih.');
        }
        $distributor = isset($input['id_distributor']) ? (int) $input['id_distributor'] : null;
        if ($this->role($request) === 'distributor') {
            $active = $this->activeDistributor($request);
            abort_if($distributor && $distributor !== $active, 403, 'Distributor hanya dapat melihat data perusahaan aktif.');
            $distributor = $active;
        }

        return [$start, $end, $distributor];
    }

    public function index(Request $request, PelaporanService $reports): View
    {
        $this->localOnly();
        [$start,$end,$distributor] = $this->filters($request);
        $rows = $reports->summary($start, $end, $distributor);

        return view('pages.pelaporan.index', [
            'rows' => $rows,
            'start' => $start,
            'end' => $end,
            'selectedDistributor' => $distributor,
            'activeDistributor' => $this->activeDistributor($request),
            'distributors' => Distributor::query()->orderBy('nama')->get(),
            'products' => Produk::query()->orderBy('nama_produk')->get(),
            'years' => TahunFiskal::query()->orderByDesc('tanggal_mulai')->get(),
            'role' => $this->role($request),
            'sales' => SellOut::query()->with(['distributor', 'produk'])->when($distributor, fn ($q) => $q->where('id_distributor', $distributor))->whereDate('tanggal', '>=', $start)->whereDate('tanggal', '<=', $end)->orderByDesc('tanggal')->paginate(20),
        ]);
    }

    public function context(Request $request): RedirectResponse
    {
        $this->localOnly();
        $values = $request->validate(['id_distributor' => ['required', 'integer', 'exists:distributor,id_distributor']]);
        $request->session()->put('active_distributor_id', (int) $values['id_distributor']);

        return redirect()->route('pelaporan.index')->with('success', 'Perusahaan simulasi aktif diperbarui.');
    }

    public function storeSellOut(StoreSellOutRequest $request): RedirectResponse
    {
        $values = $request->validated();
        abort_unless((int) $values['id_distributor'] === $this->activeDistributor($request), 403, 'Perusahaan tidak sesuai konteks distributor aktif.');
        DB::transaction(fn () => SellOut::create($values));

        return redirect()->route('pelaporan.index', [
            'mulai' => CarbonImmutable::parse($values['tanggal'])->startOfMonth()->format('Y-m-d'),
            'selesai' => $values['tanggal'],
        ])->with('success', 'Sell-Out berhasil disimpan ke MySQL.');
    }

    public function storeStock(StoreStokGudangRequest $request): RedirectResponse
    {
        $values = $request->validated();
        abort_unless((int) $values['id_distributor'] === $this->activeDistributor($request), 403, 'Perusahaan tidak sesuai konteks distributor aktif.');
        DB::transaction(function () use ($values) {
            StokGudang::query()->upsert([$values], ['id_distributor', 'id_produk', 'periode'], ['stok_fisik', 'stok_layak_jual']);
        });

        return redirect()->route('pelaporan.index', [
            'mulai' => CarbonImmutable::parse($values['periode'])->startOfMonth()->format('Y-m-d'),
            'selesai' => $values['periode'],
        ])->with('success', 'Stok gudang berhasil disimpan ke MySQL. Snapshot tanggal yang sama diperbarui.');
    }

    public function export(Request $request, PelaporanService $reports): StreamedResponse
    {
        $this->localOnly();
        [$start,$end,$distributor] = $this->filters($request);
        $rows = $reports->summary($start, $end, $distributor);

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Distributor', 'Produk', 'SKU', 'Sell-In', 'Sell-Out', 'Penerimaan Baik', 'Stok Fisik', 'Stok Layak Jual', 'Tanggal Stok', 'Ekspektasi', 'Selisih', 'Status'], ';', '"', '');
            foreach ($rows as $row) {
                $values = [$row['distributor'], $row['produk'], $row['sku'], $row['sell_in'], $row['sell_out'], $row['penerimaan_baik'], $row['stok_fisik'], $row['stok_layak_jual'], $row['tanggal_stok'], $row['ekspektasi_layak_jual'], $row['selisih'], $row['status']];
                $values = array_map(function ($value) {
                    if (is_string($value) && preg_match('/^[=+@\-\t\r]/', $value)) {
                        return "'".$value;
                    }

                    return $value;
                }, $values);
                fputcsv($output, $values, ';', '"', '');
            }
            fclose($output);
        }, 'laporan-yoke-'.$start.'-'.$end.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function databaseStatus(): JsonResponse
    {
        $this->localOnly();
        try {
            $schema = json_decode(file_get_contents(database_path('schema/schema_erd.json')), true, flags: JSON_THROW_ON_ERROR);
            $counts = [];
            foreach ($schema['tables'] as $table) {
                if (! Schema::hasTable($table['name'])) {
                    return response()->json(['connected' => true, 'ready' => false, 'missing_table' => $table['name']], 503);
                }
                $counts[$table['name']] = DB::table($table['name'])->count();
            }

            return response()->json(['connected' => true, 'ready' => true, 'tables' => count($counts), 'records' => $counts]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['connected' => false, 'ready' => false], 503);
        }
    }
}
