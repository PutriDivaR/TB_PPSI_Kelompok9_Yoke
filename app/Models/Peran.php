<?php

namespace App\Models;

use Database\Factories\PeranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peran extends YokeModel
{
    /** @use HasFactory<PeranFactory> */
    use HasFactory;

    protected $table = 'peran';

    protected $primaryKey = 'id_peran';

    protected $fillable = ['nama_peran', 'deskripsi'];

    protected function casts(): array
    {
        return [

        ];
    }

    public function penggunaRecords(): HasMany
    {
        return $this->hasMany(Pengguna::class, 'id_peran', 'id_peran');
    }
}
