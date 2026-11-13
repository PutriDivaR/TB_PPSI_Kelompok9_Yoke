<?php

namespace App\Models;

use Database\Factories\WilayahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wilayah extends YokeModel
{
    /** @use HasFactory<WilayahFactory> */
    use HasFactory;

    protected $table = 'wilayah';

    protected $primaryKey = 'id_wilayah';

    protected $fillable = ['nama_wilayah'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function distributorRecords(): HasMany
    {
        return $this->hasMany(Distributor::class, 'id_wilayah', 'id_wilayah');
    }
}
