<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_pengiriman,
            'container_number' => $this->nomor_kontainer,
            'vessel' => $this->nama_vessel,
            'initial_eta' => $this->eta_awal?->format('Y-m-d'),
            'current_eta' => $this->eta_terkini?->format('Y-m-d'),
            'status' => $this->status,
            'purchase_order' => $this->whenLoaded('po', fn () => [
                'id' => $this->po->id_po,
                'number' => $this->po->nomor_po,
                'distributor' => $this->po->forecast?->distributor?->nama,
            ]),
            'items' => $this->whenLoaded('detailPengirimanRecords', fn () => $this->detailPengirimanRecords->map(fn ($item) => [
                'id' => $item->id_detail_pengiriman,
                'purchase_order_item_id' => $item->id_detail_po,
                'sku' => $item->detailPo?->produk?->kode_sku,
                'product' => $item->detailPo?->produk?->nama_produk,
                'quantity_shipped' => $item->jumlah_dikirim,
            ])->values()),
            'milestones' => $this->whenLoaded('milestonePengirimanRecords', fn () => $this->milestonePengirimanRecords->map(fn ($milestone) => [
                'id' => $milestone->id_milestone,
                'sequence' => $milestone->urutan,
                'stage' => $milestone->nama_tahap,
                'updated_at' => $milestone->tanggal_pembaruan,
                'notes' => $milestone->keterangan,
            ])->values()),
            'receipt' => $this->whenLoaded('penerimaanBarangRecord', fn () => $this->penerimaanBarangRecord ? [
                'id' => $this->penerimaanBarangRecord->id_penerimaan,
                'received_at' => $this->penerimaanBarangRecord->tanggal_terima,
                'items' => $this->penerimaanBarangRecord->detailPenerimaanRecordsAsPenerimaan->map(fn ($line) => [
                    'id' => $line->id_detail_penerimaan,
                    'shipment_item_id' => $line->id_detail_pengiriman,
                    'quantity_received' => $line->jumlah_diterima,
                    'quantity_good' => $line->jumlah_baik,
                    'condition' => $line->kondisi,
                ])->values(),
            ] : null),
        ];
    }
}
