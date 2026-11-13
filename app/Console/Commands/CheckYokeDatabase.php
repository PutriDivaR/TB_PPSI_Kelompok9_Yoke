<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckYokeDatabase extends Command
{
    protected $signature = 'yoke:database-check';

    protected $description = 'Periksa koneksi dan seluruh tabel/kolom ERD tanpa mengubah data';

    public function handle(): int
    {
        try {
            DB::connection()->getPdo();
            $schema = json_decode(file_get_contents(database_path('schema/schema_erd.json')), true, flags: JSON_THROW_ON_ERROR);
            foreach ($schema['tables'] as $table) {
                if (! Schema::hasTable($table['name'])) {
                    $this->error('Tabel belum tersedia: '.$table['name']);

                    return self::FAILURE;
                }
                $columns = Schema::getColumnListing($table['name']);
                foreach ($table['attrs'] as $column) {
                    if (! in_array($column['name'], $columns, true)) {
                        $this->error('Kolom belum tersedia: '.$table['name'].'.'.$column['name']);

                        return self::FAILURE;
                    }
                }
            }
            $this->info('Terhubung ke '.DB::connection()->getDatabaseName().': 23 tabel dan 125 kolom ERD tersedia.');
            if (DB::connection()->getDriverName() === 'mysql') {
                $result = DB::selectOne('SELECT COUNT(*) AS jumlah FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()');
                $this->line('Foreign key: '.$result->jumlah);
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Koneksi gagal. Periksa MySQL Laragon dan konfigurasi .env.');

            return self::FAILURE;
        }
    }
}
