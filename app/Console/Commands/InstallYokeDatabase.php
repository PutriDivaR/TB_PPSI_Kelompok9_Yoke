<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallYokeDatabase extends Command
{
    protected $signature = 'yoke:database-install';

    protected $description = 'Pasang ERD di MySQL kosong; database yang sudah ada hanya diperiksa';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('Installer ini khusus MySQL 8.4+.');

            return self::FAILURE;
        }
        if (count(Schema::getTables(DB::connection()->getDatabaseName())) > 0) {
            $this->line('Database berisi tabel. Tidak ada tabel atau data yang dihapus.');

            return $this->call('yoke:database-check');
        }
        $delimiter = ';';
        $statement = '';
        foreach (file(database_path('schema/yoke_ppsi.sql')) as $line) {
            if (preg_match('/^DELIMITER\s+(\S+)/', trim($line), $matches)) {
                $delimiter = $matches[1];

                continue;
            }
            if (str_starts_with(trim($line), '--')) {
                continue;
            }
            $statement .= $line;
            if (str_ends_with(rtrim($statement), $delimiter)) {
                DB::unprepared(substr(rtrim($statement), 0, -strlen($delimiter)));
                $statement = '';
            }
        }
        $this->call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

        return $this->call('yoke:database-check');
    }
}
