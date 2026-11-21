<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ForecastResource;
use App\Models\DetailForecast;
use App\Models\Forecast;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\TahunFiskal;
use App\Services\EpoService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ForecastController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'periode' => ['nullable', 'date_format:Y-m'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $user = $request->user();
        $forecasts = Forecast::query()->with(['distributor', 'penyetuju', 'detailForecastRecords.produk'])
            ->when($user->peran->nama_peran === 'Distributor', fn ($query) => $query->where('id_distributor', $user->id_distributor))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('periode'), fn ($query) => $query->whereYear('periode', substr((string) $request->input('periode'), 0, 4))
                ->whereMonth('periode', substr((string) $request->input('periode'), 5, 2)))
            ->orderByDesc('periode')->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return ForecastResource::collection($forecasts)->response();
    }

    public function show(Request $request, Forecast $forecast): JsonResponse
    {
        $user = $request->user();
        if ($user->peran->nama_peran === 'Distributor') {
            abort_unless((int) $forecast->id_distributor === (int) $user->id_distributor, 403,
                'Forecast bukan milik perusahaan Anda.');
        }

        return (new ForecastResource($forecast->load(['distributor', 'penyetuju', 'detailForecastRecords.produk'])))->response();
    }

    public function recommendations(Request $request): JsonResponse
    {
        $period = $request->validate(['periode' => ['required', 'date_format:Y-m']])['periode'];
        $user = $request->user();
        abort_unless($user->id_distributor, 422, 'Akun tidak terhubung ke perusahaan Distributor.');

        return response()->json(['data' => [
            'periode' => $period,
            'items' => EpoService::hitungRekomendasi($user->id_distributor, $period),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->id_distributor, 422, 'Akun tidak terhubung ke perusahaan Distributor.');
        $values = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_produk' => ['required', 'integer', 'distinct', 'exists:produk,id_produk'],
            'items.*.jumlah_kontainer' => ['required', 'integer', 'min:1', 'max:100000'],
            'submit' => ['sometimes', 'boolean'],
        ]);
        $period = CarbonImmutable::createFromFormat('!Y-m', $values['periode']);
        $fiscalYear = TahunFiskal::query()->whereDate('tanggal_mulai', '<=', $period->endOfMonth())
            ->whereDate('tanggal_selesai', '>=', $period->startOfMonth())->first();
        abort_unless($fiscalYear, 422, 'Periode tidak berada dalam tahun fiskal yang terdaftar.');

        $forecast = DB::transaction(function () use ($values, $period, $fiscalYear, $user) {
            $forecast = Forecast::query()->where('id_distributor', $user->id_distributor)
                ->whereDate('periode', $period->startOfMonth())->lockForUpdate()->first();

            if ($forecast && $forecast->status !== 'Draft') {
                throw ValidationException::withMessages(['periode' => ['Forecast bulan ini sudah diajukan dan tidak dapat diubah.']]);
            }
            if ($forecast && $forecast->purchaseOrderRecords()->exists()) {
                throw ValidationException::withMessages(['periode' => ['Forecast yang sudah memiliki draft PO tidak dapat diubah.']]);
            }

            $forecast ??= Forecast::query()->create([
                'id_distributor' => $user->id_distributor,
                'id_tahun_fiskal' => $fiscalYear->id_tahun_fiskal,
                'periode' => $period->startOfMonth()->toDateString(),
                'tanggal_pengajuan' => now(),
                'status' => 'Draft',
            ]);
            $forecast->fill([
                'id_tahun_fiskal' => $fiscalYear->id_tahun_fiskal,
                'tanggal_pengajuan' => now(),
                'status' => ($values['submit'] ?? false) ? 'Diajukan' : 'Draft',
            ])->save();

            DetailForecast::query()->where('id_forecast', $forecast->id_forecast)->delete();
            foreach ($values['items'] as $item) {
                DetailForecast::query()->create([
                    'id_forecast' => $forecast->id_forecast,
                    'id_produk' => $item['id_produk'],
                    'jumlah_kontainer' => $item['jumlah_kontainer'],
                ]);
            }

            $this->recordActivity($user, 'E-PO', ($values['submit'] ?? false) ? 'Ajukan forecast' : 'Simpan draft forecast');

            return $forecast;
        });

        return (new ForecastResource($forecast->load(['distributor', 'penyetuju', 'detailForecastRecords.produk'])))
            ->response()->setStatusCode($forecast->status === 'Diajukan' ? 201 : 200);
    }

    public function approve(Request $request, Forecast $forecast): JsonResponse
    {
        $values = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_detail_forecast' => ['required', 'integer', 'distinct'],
            'items.*.jumlah_disetujui' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
        /** @var Pengguna $user */
        $user = $request->user();

        DB::transaction(function () use ($forecast, $values, $user) {
            $locked = Forecast::query()->whereKey($forecast->id_forecast)->lockForUpdate()
                ->with('detailForecastRecords')->firstOrFail();
            if (! in_array($locked->status, ['Diajukan', 'Diproses'], true)) {
                throw ValidationException::withMessages(['status' => ['Forecast ini tidak menunggu persetujuan.']]);
            }

            $approved = collect($values['items'])->keyBy('id_detail_forecast');
            $details = $locked->detailForecastRecords->keyBy('id_detail_forecast');
            if ($approved->keys()->sort()->values()->all() !== $details->keys()->sort()->values()->all()) {
                throw ValidationException::withMessages(['items' => ['Semua baris forecast harus memiliki nilai persetujuan.']]);
            }
            if ($approved->sum('jumlah_disetujui') < 1) {
                throw ValidationException::withMessages(['items' => ['Setidaknya satu produk harus mendapat kuota.']]);
            }

            foreach ($approved as $id => $item) {
                $details[$id]->update(['jumlah_disetujui' => $item['jumlah_disetujui']]);
            }
            $locked->update([
                'status' => 'Disetujui',
                'tanggal_disetujui' => now(),
                'id_penyetuju' => $user->id_pengguna,
            ]);
            $this->recordActivity($user, 'E-PO', 'Setujui forecast');
        });

        return (new ForecastResource($forecast->refresh()->load(['distributor', 'penyetuju', 'detailForecastRecords.produk'])))->response();
    }

    public function startProcessing(Request $request, Forecast $forecast): JsonResponse
    {
        DB::transaction(function () use ($request, $forecast) {
            $locked = Forecast::query()->whereKey($forecast->id_forecast)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'Diajukan') {
                throw ValidationException::withMessages(['status' => ['Hanya forecast yang baru diajukan dapat mulai diproses.']]);
            }
            $locked->update(['status' => 'Diproses']);
            $this->recordActivity($request->user(), 'E-PO', 'Mulai proses forecast');
        });

        return (new ForecastResource($forecast->refresh()->load(['distributor', 'penyetuju', 'detailForecastRecords.produk'])))->response();
    }

    private function recordActivity(Pengguna $user, string $module, string $action): void
    {
        LogAktivitas::query()->create([
            'id_pengguna' => $user->id_pengguna,
            'modul' => $module,
            'aksi' => $action,
            'waktu' => now(),
        ]);
    }
}
