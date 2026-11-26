<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    /** @use HasFactory<PenggunaFactory> */
    use HasFactory;

    protected $table = 'pengguna';

    protected $primaryKey = 'id_pengguna';

    protected $fillable = ['nama', 'email', 'kata_sandi', 'id_peran', 'status_aktif', 'id_distributor'];

    public $timestamps = false;

    protected $hidden = ['kata_sandi'];

    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    public function getFotoProfilUrlAttribute(): string
    {
        $customPath = "uploads/avatars/{$this->id_pengguna}.png";
        if (file_exists(public_path($customPath))) {
            return asset($customPath) . '?v=' . filemtime(public_path($customPath));
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->nama) . '&background=1E293B&color=fff';
    }

    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
            'kata_sandi' => 'hashed',
        ];
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'id_peran', 'id_peran');
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(Distributor::class, 'id_distributor', 'id_distributor');
    }

    public function logAktivitasRecords(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'id_pengguna', 'id_pengguna');
    }

    public function forecastRecordsAsPenyetuju(): HasMany
    {
        return $this->hasMany(Forecast::class, 'id_penyetuju', 'id_pengguna');
    }

    public function purchaseOrderRecordsAsPenyetuju(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'id_penyetuju', 'id_pengguna');
    }

    public function beritaAcaraKerusakanRecordsAsPengesah(): HasMany
    {
        return $this->hasMany(BeritaAcaraKerusakan::class, 'id_pengesah', 'id_pengguna');
    }
}
