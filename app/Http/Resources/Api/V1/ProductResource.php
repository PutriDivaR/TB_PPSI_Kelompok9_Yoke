<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_produk,
            'sku' => $this->kode_sku,
            'name' => $this->nama_produk,
            'price' => (float) $this->harga,
            'cartons_per_container' => $this->karton_per_kontainer,
            'category' => $this->kategori,
        ];
    }
}
