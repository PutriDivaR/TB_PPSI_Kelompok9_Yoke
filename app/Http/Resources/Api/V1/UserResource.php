<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_pengguna,
            'name' => $this->nama,
            'email' => $this->email,
            'role' => $this->whenLoaded('peran', fn () => $this->peran->nama_peran),
            'distributor' => $this->whenLoaded('distributor', fn () => $this->distributor ? [
                'id' => $this->distributor->id_distributor,
                'name' => $this->distributor->nama,
            ] : null),
            'active' => (bool) $this->status_aktif,
        ];
    }
}
