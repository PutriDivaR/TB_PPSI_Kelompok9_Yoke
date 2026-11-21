<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PurchaseOrderResource;
use App\Models\DetailPo;
use App\Models\Forecast;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $user = $request->user();
        $orders = PurchaseOrder::query()->with(['forecast.distributor', 'penyetuju', 'detailPoRecordsAsPo.produk'])
            ->when($user->peran->nama_peran === 'Distributor', fn ($query) => $query->whereHas(
                'forecast', fn ($forecast) => $forecast->where('id_distributor', $user->id_distributor)
            ))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('tanggal_pengajuan')->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return PurchaseOrderResource::collection($orders)->response();
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $user = $request->user();
        if ($user->peran->nama_peran === 'Distributor') {
            abort_unless((int) $purchaseOrder->forecast()->value('id_distributor') === (int) $user->id_distributor,
                403, 'PO bukan milik perusahaan Anda.');
        }

        return (new PurchaseOrderResource($purchaseOrder->load(['forecast.distributor', 'penyetuju', 'detailPoRecordsAsPo.produk'])))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $values = $request->validate([
            'id_forecast' => ['required', 'integer', 'exists:forecast,id_forecast'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_produk' => ['required', 'integer', 'distinct', 'exists:produk,id_produk'],
            'items.*.jumlah_kontainer' => ['required', 'integer', 'min:1', 'max:100000'],
            'submit' => ['sometimes', 'boolean'],
        ]);
        $user = $request->user();

        $order = DB::transaction(function () use ($values, $user) {
            $forecast = Forecast::query()->whereKey($values['id_forecast'])->lockForUpdate()
                ->with('detailForecastRecords')->firstOrFail();
            abort_unless((int) $forecast->id_distributor === (int) $user->id_distributor || $user->peran->nama_peran !== 'Distributor',
                403, 'Forecast bukan milik perusahaan Anda.');
            if ($forecast->status !== 'Disetujui') {
                throw ValidationException::withMessages(['id_forecast' => ['PO hanya dapat dibuat dari forecast yang sudah disetujui.']]);
            }
            if ($forecast->purchaseOrderRecords()->where('status', '!=', 'Draft')->exists()) {
                throw ValidationException::withMessages(['id_forecast' => ['Forecast ini sudah memiliki PO yang diajukan.']]);
            }

            $forecastItems = $forecast->detailForecastRecords->keyBy('id_produk');
            foreach ($values['items'] as $item) {
                $forecastItem = $forecastItems->get($item['id_produk']);
                abort_unless($forecastItem, 422, 'Produk PO harus tercantum dalam forecast.');
                if ($item['jumlah_kontainer'] > (int) $forecastItem->jumlah_disetujui) {
                    throw ValidationException::withMessages(['items' => ['Jumlah PO tidak boleh melebihi kuota produk yang disetujui.']]);
                }
            }

            $order = $forecast->purchaseOrderRecords()->where('status', 'Draft')->first();
            if (! $order) {
                $order = PurchaseOrder::query()->create([
                    'nomor_po' => 'TMP-'.(string) Str::ulid(),
                    'id_forecast' => $forecast->id_forecast,
                    'tanggal_pengajuan' => now(),
                    'status' => 'Draft',
                ]);
                $order->update(['nomor_po' => 'PO-YFI-'.now()->format('Y').'-'.str_pad((string) $order->id_po, 6, '0', STR_PAD_LEFT)]);
            }
            $order->fill([
                'tanggal_pengajuan' => now(),
                'status' => ($values['submit'] ?? false) ? 'Diajukan' : 'Draft',
            ])->save();
            DetailPo::query()->where('id_po', $order->id_po)->delete();
            foreach ($values['items'] as $item) {
                DetailPo::query()->create([
                    'id_po' => $order->id_po,
                    'id_produk' => $item['id_produk'],
                    'jumlah_kontainer' => $item['jumlah_kontainer'],
                ]);
            }

            $this->recordActivity($user, 'E-PO', ($values['submit'] ?? false) ? 'Ajukan PO' : 'Simpan draft PO');

            return $order;
        });

        return (new PurchaseOrderResource($order->load(['forecast.distributor', 'penyetuju', 'detailPoRecordsAsPo.produk'])))
            ->response()->setStatusCode($order->status === 'Diajukan' ? 201 : 200);
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $user = $request->user();
        DB::transaction(function () use ($purchaseOrder, $user) {
            $order = PurchaseOrder::query()->whereKey($purchaseOrder->id_po)->lockForUpdate()->with('forecast')->firstOrFail();
            if ($order->status !== 'Diajukan' || $order->forecast->status !== 'Disetujui') {
                throw ValidationException::withMessages(['status' => ['PO ini belum memenuhi status untuk disetujui.']]);
            }
            $approvedQuantities = $order->forecast->detailForecastRecords()->pluck('jumlah_disetujui', 'id_produk');
            foreach ($order->detailPoRecordsAsPo as $line) {
                if ($line->jumlah_kontainer > (int) $approvedQuantities->get($line->id_produk, 0)) {
                    throw ValidationException::withMessages(['items' => ['Jumlah PO melebihi kuota forecast yang disetujui.']]);
                }
            }
            $order->update([
                'status' => 'Disetujui',
                'tanggal_disetujui' => now(),
                'id_penyetuju' => $user->id_pengguna,
            ]);
            $this->recordActivity($user, 'E-PO', 'Setujui PO');
        });

        return (new PurchaseOrderResource($purchaseOrder->refresh()->load(['forecast.distributor', 'penyetuju', 'detailPoRecordsAsPo.produk'])))->response();
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
