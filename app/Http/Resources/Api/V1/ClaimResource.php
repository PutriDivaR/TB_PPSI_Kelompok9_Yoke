<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_klaim,
            'status' => $this->status,
            'submitted_at' => $this->tanggal_pengajuan,
            'completed_at' => $this->tanggal_penyelesaian,
            'receipt' => $this->whenLoaded('penerimaan', fn () => [
                'id' => $this->penerimaan->id_penerimaan,
                'received_at' => $this->penerimaan->tanggal_terima,
                'shipment' => $this->penerimaan->pengiriman ? [
                    'id' => $this->penerimaan->pengiriman->id_pengiriman,
                    'container_number' => $this->penerimaan->pengiriman->nomor_kontainer,
                    'purchase_order' => $this->penerimaan->pengiriman->po?->nomor_po,
                    'distributor' => $this->penerimaan->pengiriman->po?->forecast?->distributor?->nama,
                ] : null,
            ]),
            'items' => $this->whenLoaded('detailKlaimRecords', fn () => $this->detailKlaimRecords->map(fn ($item) => [
                'id' => $item->id_detail_klaim,
                'receipt_item_id' => $item->id_detail_penerimaan,
                'product' => $item->detailPenerimaan?->detailPengiriman?->detailPo?->produk?->nama_produk,
                'sku' => $item->detailPenerimaan?->detailPengiriman?->detailPo?->produk?->kode_sku,
                'damage_type' => $item->jenis_kerusakan,
                'quantity' => $item->jumlah,
                'responsible_party' => $item->penanggung_jawab,
                'verification_status' => $item->status_verifikasi,
                'photos' => $item->fotoBuktiRecords->map(fn ($photo) => [
                    'id' => $photo->id_foto,
                    'url' => route('api.v1.claims.photos.show', [$this->id_klaim, $photo->id_foto]),
                ])->values(),
            ])->values()),
            'certificate' => $this->whenLoaded('beritaAcaraKerusakanRecord', fn () => $this->beritaAcaraKerusakanRecord ? [
                'id' => $this->beritaAcaraKerusakanRecord->id_bak,
                'number' => $this->beritaAcaraKerusakanRecord->nomor_bak,
                'status' => $this->beritaAcaraKerusakanRecord->status,
                'signed_at' => $this->beritaAcaraKerusakanRecord->tanggal_pengesahan,
                'signature_url' => route('api.v1.claims.signature', [$this->id_klaim, $this->beritaAcaraKerusakanRecord->id_bak]),
                'approver' => $this->beritaAcaraKerusakanRecord->pengesah?->nama,
            ] : null),
        ];
    }
}
