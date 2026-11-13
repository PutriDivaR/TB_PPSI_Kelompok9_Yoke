<?php

namespace Database\Seeders;

use App\Models\Peran;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Admin', 'Distributor', 'Logistik Pusat', 'SAM'] as $role) {
            Peran::firstOrCreate(['nama_peran' => $role]);
        }
    }
}
