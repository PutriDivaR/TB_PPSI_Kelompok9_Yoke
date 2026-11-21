<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForecastResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_forecast,
            'period' => $this->periode?->format('Y-m'),
            'status' => $this->status,
            'submitted_at' => $this->tanggal_pengajuan,
            'approved_at' => $this->tanggal_disetujui,
            'distributor' => $this->whenLoaded('distributor', fn () => [
                'id' => $this->distributor->id_distributor,
                'name' => $this->distributor->nama,
            ]),
            'fiscal_year_id' => $this->id_tahun_fiskal,
            'approver' => $this->whenLoaded('penyetuju', fn () => $this->penyetuju ? [
                'id' => $this->penyetuju->id_pengguna,
                'name' => $this->penyetuju->nama,
            ] : null),
            'items' => $this->whenLoaded('detailForecastRecords', fn () => $this->detailForecastRecords->map(fn ($item) => [
                'id' => $item->id_detail_forecast,
                'product' => $item->produk ? [
                    'id' => $item->produk->id_produk,
                    'sku' => $item->produk->kode_sku,
                    'name' => $item->produk->nama_produk,
                ] : null,
                'requested_containers' => $item->jumlah_kontainer,
                'approved_containers' => $item->jumlah_disetujui,
            ])->values()),
        ];
    }
}
