<?php

namespace App\Models;

use Database\Factories\TahunFiskalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunFiskal extends YokeModel
{
    /** @use HasFactory<TahunFiskalFactory> */
    use HasFactory;

    protected $table = 'tahun_fiskal';

    protected $primaryKey = 'id_tahun_fiskal';

    protected $fillable = ['tanggal_mulai', 'tanggal_selesai', 'status'];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    public function forecastRecords(): HasMany
    {
        return $this->hasMany(Forecast::class, 'id_tahun_fiskal', 'id_tahun_fiskal');
    }
}
