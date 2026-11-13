<?php

namespace App\Models;

use Database\Factories\LogAktivitasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends YokeModel
{
    /** @use HasFactory<LogAktivitasFactory> */
    use HasFactory;

    protected $table = 'log_aktivitas';

    protected $primaryKey = 'id_log';

    protected $fillable = ['id_pengguna', 'modul', 'aksi', 'waktu'];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'id_pengguna', 'id_pengguna');
    }
}
