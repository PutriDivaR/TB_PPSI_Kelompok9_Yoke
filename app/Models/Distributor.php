<?php

namespace App\Models;

use Database\Factories\DistributorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Distributor extends YokeModel
{
    /** @use HasFactory<DistributorFactory> */
    use HasFactory;

    protected $table = 'distributor';

    protected $primaryKey = 'id_distributor';

    protected $fillable = ['nama', 'id_wilayah', 'kontak', 'alamat_gudang', 'nomor_mou', 'status_aktif'];

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
        ];
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'id_wilayah', 'id_wilayah');
    }

    public function penggunaRecord(): HasOne
    {
        return $this->hasOne(Pengguna::class, 'id_distributor', 'id_distributor');
    }

    public function forecastRecords(): HasMany
    {
        return $this->hasMany(Forecast::class, 'id_distributor', 'id_distributor');
    }

    public function sellOutRecords(): HasMany
    {
        return $this->hasMany(SellOut::class, 'id_distributor', 'id_distributor');
    }

    public function stokGudangRecords(): HasMany
    {
        return $this->hasMany(StokGudang::class, 'id_distributor', 'id_distributor');
    }
}
