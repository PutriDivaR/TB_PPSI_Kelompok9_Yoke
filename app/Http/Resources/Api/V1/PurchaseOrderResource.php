<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_po,
            'number' => $this->nomor_po,
            'status' => $this->status,
            'submitted_at' => $this->tanggal_pengajuan,
            'approved_at' => $this->tanggal_disetujui,
            'forecast' => $this->whenLoaded('forecast', fn () => [
                'id' => $this->forecast->id_forecast,
                'period' => $this->forecast->periode?->format('Y-m'),
                'distributor' => $this->forecast->distributor?->nama,
            ]),
            'approver' => $this->whenLoaded('penyetuju', fn () => $this->penyetuju ? [
                'id' => $this->penyetuju->id_pengguna,
                'name' => $this->penyetuju->nama,
            ] : null),
            'items' => $this->whenLoaded('detailPoRecordsAsPo', fn () => $this->detailPoRecordsAsPo->map(fn ($item) => [
                'id' => $item->id_detail_po,
                'product' => $item->produk ? [
                    'id' => $item->produk->id_produk,
                    'sku' => $item->produk->kode_sku,
                    'name' => $item->produk->nama_produk,
                ] : null,
                'containers' => $item->jumlah_kontainer,
            ])->values()),
        ];
    }
}
