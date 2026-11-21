<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributorResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_distributor,
            'name' => $this->nama,
            'region' => $this->whenLoaded('wilayah', fn () => [
                'id' => $this->wilayah->id_wilayah,
                'name' => $this->wilayah->nama_wilayah,
            ]),
            'contact' => $this->kontak,
            'warehouse_address' => $this->alamat_gudang,
            'agreement_number' => $this->nomor_mou,
            'active' => (bool) $this->status_aktif,
        ];
    }
}
