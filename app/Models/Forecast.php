<?php

namespace App\Models;

use Database\Factories\ForecastFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forecast extends YokeModel
{
    /** @use HasFactory<ForecastFactory> */
    use HasFactory;

    protected $table = 'forecast';

    protected $primaryKey = 'id_forecast';

    protected $fillable = ['id_distributor', 'id_tahun_fiskal', 'periode', 'tanggal_pengajuan', 'status', 'tanggal_disetujui', 'id_penyetuju'];

    protected function casts(): array
    {
        return [
            'periode' => 'date',
            'tanggal_pengajuan' => 'datetime',
            'tanggal_disetujui' => 'datetime',
        ];
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class, 'id_distributor', 'id_distributor');
    }

    public function tahunFiskal(): BelongsTo
    {
        return $this->belongsTo(TahunFiskal::class, 'id_tahun_fiskal', 'id_tahun_fiskal');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_penyetuju', 'id_pengguna');
    }

    public function detailForecastRecords(): HasMany
    {
        return $this->hasMany(DetailForecast::class, 'id_forecast', 'id_forecast');
    }

    public function purchaseOrderRecords(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'id_forecast', 'id_forecast');
    }
}
