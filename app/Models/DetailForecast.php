<?php

namespace App\Models;

use Database\Factories\DetailForecastFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailForecast extends YokeModel
{
    /** @use HasFactory<DetailForecastFactory> */
    use HasFactory;

    protected $table = 'detail_forecast';

    protected $primaryKey = 'id_detail_forecast';

    protected $fillable = ['id_forecast', 'id_produk', 'jumlah_kontainer', 'jumlah_disetujui'];

    protected function casts(): array
    {
        return [
            'jumlah_kontainer' => 'integer',
            'jumlah_disetujui' => 'integer',
        ];
    }

    public function forecast(): BelongsTo
    {
        return $this->belongsTo(Forecast::class, 'id_forecast', 'id_forecast');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'id_produk', 'id_produk');
    }
}
