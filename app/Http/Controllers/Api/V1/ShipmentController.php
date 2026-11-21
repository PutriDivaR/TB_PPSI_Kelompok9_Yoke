<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShipmentResource;
use App\Models\DetailPenerimaan;
use App\Models\DetailPengiriman;
use App\Models\LogAktivitas;
use App\Models\PenerimaanBarang;
use App\Models\Pengguna;
use App\Models\Pengiriman;
use App\Models\PurchaseOrder;
use App\Models\SellIn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $user = $request->user();
        $shipments = Pengiriman::query()->with([
            'po.forecast.distributor',
            'detailPengirimanRecords.detailPo.produk',
            'milestonePengirimanRecords' => fn ($query) => $query->orderBy('urutan'),
            'penerimaanBarangRecord.detailPenerimaanRecordsAsPenerimaan',
        ])->when($user->peran->nama_peran === 'Distributor', fn ($query) => $query->whereHas(
            'po.forecast', fn ($forecast) => $forecast->where('id_distributor', $user->id_distributor)
        ))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('id_pengiriman')->paginate(max(1, min($request->integer('per_page', 20), 100)));

        return ShipmentResource::collection($shipments)->response();
    }

    public function show(Request $request, Pengiriman $shipment): JsonResponse
    {
        $this->authorizeShipment($request, $shipment);

        return (new ShipmentResource($shipment->load([
            'po.forecast.distributor',
            'detailPengirimanRecords.detailPo.produk',
            'milestonePengirimanRecords' => fn ($query) => $query->orderBy('urutan'),
            'penerimaanBarangRecord.detailPenerimaanRecordsAsPenerimaan',
        ])))->response();
    }

    public function store(Request $request): JsonResponse
    {
        $values = $request->validate([
            'id_po' => ['required', 'integer', 'exists:purchase_order,id_po'],
            'nomor_kontainer' => ['required', 'string', 'max:60'],
            'nama_vessel' => ['nullable', 'string', 'max:150'],
            'eta_awal' => ['required', 'date_format:Y-m-d'],
            'eta_terkini' => ['required', 'date_format:Y-m-d', 'after_or_equal:eta_awal'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_detail_po' => ['required', 'integer', 'distinct', 'exists:detail_po,id_detail_po'],
            'items.*.jumlah_dikirim' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'nomor_invoice' => ['nullable', 'string', 'max:60'],
            'nomor_do' => ['nullable', 'string', 'max:60'],
        ]);
        $user = $request->user();

        $shipment = DB::transaction(function () use ($values, $user) {
            $order = PurchaseOrder::query()->whereKey($values['id_po'])->lockForUpdate()
                ->with('detailPoRecordsAsPo')->firstOrFail();
            if ($order->status !== 'Disetujui') {
                throw ValidationException::withMessages(['id_po' => ['Pengiriman hanya dapat dibuat untuk PO yang sudah disetujui.']]);
            }
            if ($user->peran->nama_peran === 'Distributor' && (int) $order->forecast->id_distributor !== (int) $user->id_distributor) {
                abort(403, 'PO bukan milik perusahaan Anda.');
            }
            $orderItems = $order->detailPoRecordsAsPo->keyBy('id_detail_po');
            foreach ($values['items'] as $item) {
                $orderItem = $orderItems->get($item['id_detail_po']);
                abort_unless($orderItem, 422, 'Rincian pengiriman harus berasal dari PO yang dipilih.');
                $alreadyShipped = DetailPengiriman::query()->where('id_detail_po', $orderItem->id_detail_po)->sum('jumlah_dikirim');
                if ($alreadyShipped + $item['jumlah_dikirim'] > $orderItem->jumlah_kontainer) {
                    throw ValidationException::withMessages(['items' => ['Jumlah terkirim melebihi jumlah pada PO.']]);
                }
            }

            $shipment = Pengiriman::query()->create([
                'id_po' => $order->id_po,
                'nomor_kontainer' => $values['nomor_kontainer'],
                'nama_vessel' => $values['nama_vessel'] ?? null,
                'eta_awal' => $values['eta_awal'],
                'eta_terkini' => $values['eta_terkini'],
                'status' => 'Dalam Pengiriman',
            ]);
            foreach ($values['items'] as $item) {
                $line = DetailPengiriman::query()->create([
                    'id_pengiriman' => $shipment->id_pengiriman,
                    'id_detail_po' => $item['id_detail_po'],
                    'jumlah_dikirim' => $item['jumlah_dikirim'],
                ]);
                SellIn::query()->create([
                    'id_detail_pengiriman' => $line->id_detail_pengiriman,
                    'nomor_invoice' => $values['nomor_invoice'] ?? null,
                    'nomor_do' => $values['nomor_do'] ?? null,
                    'jumlah' => $item['jumlah_dikirim'],
                    'tanggal' => now()->toDateString(),
                ]);
            }
            $this->recordActivity($user, 'Monitoring', 'Catat pengiriman');

            return $shipment;
        });

        return (new ShipmentResource($shipment->load([
            'po.forecast.distributor',
            'detailPengirimanRecords.detailPo.produk',
            'milestonePengirimanRecords',
        ])))->response()->setStatusCode(201);
    }

    public function update(Request $request, Pengiriman $shipment): JsonResponse
    {
        $values = $request->validate([
            'nama_vessel' => ['sometimes', 'nullable', 'string', 'max:150'],
            'eta_terkini' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'required', 'string', 'max:40'],
        ]);
        $shipment->fill($values)->save();
        $this->recordActivity($request->user(), 'Monitoring', 'Perbarui pengiriman');

        return (new ShipmentResource($shipment->refresh()->load(['po.forecast.distributor', 'detailPengirimanRecords.detailPo.produk', 'milestonePengirimanRecords'])))->response();
    }

    public function storeMilestone(Request $request, Pengiriman $shipment): JsonResponse
    {
        $values = $request->validate([
            'nama_tahap' => ['required', 'string', 'max:150'],
            'tanggal_pembaruan' => ['required', 'date_format:Y-m-d H:i:s'],
            'keterangan' => ['nullable', 'string'],
        ]);
        $milestone = DB::transaction(function () use ($shipment, $values) {
            $lockedShipment = Pengiriman::query()->whereKey($shipment->id_pengiriman)->lockForUpdate()->firstOrFail();
            $sequence = (int) $lockedShipment->milestonePengirimanRecords()->max('urutan') + 1;

            return $lockedShipment->milestonePengirimanRecords()->create([
                'urutan' => $sequence,
                'nama_tahap' => $values['nama_tahap'],
                'tanggal_pembaruan' => $values['tanggal_pembaruan'],
                'keterangan' => $values['keterangan'] ?? null,
            ]);
        });
        $this->recordActivity($request->user(), 'Monitoring', 'Tambah milestone pengiriman');

        return response()->json(['data' => $milestone], 201);
    }

    public function storeReceipt(Request $request, Pengiriman $shipment): JsonResponse
    {
        $this->authorizeShipment($request, $shipment);
        $values = $request->validate([
            'tanggal_terima' => ['required', 'date_format:Y-m-d H:i:s'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id_detail_pengiriman' => ['required', 'integer', 'distinct', 'exists:detail_pengiriman,id_detail_pengiriman'],
            'items.*.jumlah_diterima' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'items.*.jumlah_baik' => ['required', 'integer', 'min:0', 'lte:items.*.jumlah_diterima'],
        ]);

        $receipt = DB::transaction(function () use ($shipment, $values, $request) {
            $locked = Pengiriman::query()->whereKey($shipment->id_pengiriman)->lockForUpdate()
                ->with('detailPengirimanRecords')->firstOrFail();
            if ($locked->penerimaanBarangRecord()->exists()) {
                throw ValidationException::withMessages(['shipment' => ['Penerimaan untuk kedatangan ini sudah tercatat.']]);
            }
            $lines = $locked->detailPengirimanRecords->keyBy('id_detail_pengiriman');
            $receivedIds = collect($values['items'])->pluck('id_detail_pengiriman')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $shipmentLineIds = $lines->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
            if ($receivedIds !== $shipmentLineIds) {
                throw ValidationException::withMessages(['items' => ['Semua baris dalam kedatangan harus dicatat, termasuk jumlah 0 bila barang belum diterima.']]);
            }
            foreach ($values['items'] as $item) {
                abort_unless($lines->has($item['id_detail_pengiriman']), 422, 'Barang penerimaan harus berasal dari pengiriman ini.');
                $line = $lines[$item['id_detail_pengiriman']];
                if ($item['jumlah_diterima'] > $line->jumlah_dikirim) {
                    throw ValidationException::withMessages(['items' => ['Jumlah diterima melebihi jumlah yang dikirim.']]);
                }
            }

            $receipt = PenerimaanBarang::query()->create([
                'id_pengiriman' => $locked->id_pengiriman,
                'tanggal_terima' => $values['tanggal_terima'],
            ]);
            foreach ($values['items'] as $item) {
                $jumlahBaik = (int) $item['jumlah_baik'];
                $jumlahDiterima = (int) $item['jumlah_diterima'];
                $condition = $jumlahBaik === $jumlahDiterima
                    ? 'Baik'
                    : ($jumlahBaik === 0 ? 'Rusak' : 'Sebagian Rusak');
                DetailPenerimaan::query()->create([
                    'id_penerimaan' => $receipt->id_penerimaan,
                    'id_detail_pengiriman' => $item['id_detail_pengiriman'],
                    'jumlah_diterima' => $item['jumlah_diterima'],
                    'jumlah_baik' => $item['jumlah_baik'],
                    'kondisi' => $condition,
                ]);
            }
            $locked->update(['status' => 'Diterima']);
            $this->recordActivity($request->user(), 'Monitoring', 'Catat penerimaan');

            return $receipt;
        });

        return response()->json(['data' => $receipt->load('detailPenerimaanRecordsAsPenerimaan')], 201);
    }

    private function authorizeShipment(Request $request, Pengiriman $shipment): void
    {
        $user = $request->user();
        if ($user->peran->nama_peran === 'Distributor') {
            abort_unless((int) $shipment->po()->with('forecast')->firstOrFail()->forecast->id_distributor === (int) $user->id_distributor, 403,
                'Pengiriman bukan milik perusahaan Anda.');
        }
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
