<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Siapkan tabel ERD Yoke untuk pengujian: SQLite in-memory dibangun dari schema_erd.json,
 * MySQL hanya boleh memakai database berakhiran _test. Setiap test dibungkus transaksi.
 */
trait MembangunSkemaYoke
{
    protected function bangunSkemaYoke(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            if (! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
                throw new \RuntimeException('Pengujian MySQL hanya boleh memakai database berakhiran _test.');
            }
            if (! Schema::hasTable('produk')) {
                $this->assertSame(0, Artisan::call('yoke:database-install'));
            }
        } else {
            $schema = json_decode(file_get_contents(database_path('schema/schema_erd.json')), true, flags: JSON_THROW_ON_ERROR);
            $tables = collect($schema['tables'])->keyBy('name');
            foreach ($tables as $name => $table) {
                Schema::create($name, function (Blueprint $blueprint) use ($table, $schema, $tables, $name) {
                    foreach ($table['attrs'] as $attribute) {
                        $column = $attribute['name'];
                        if ($attribute['pk']) {
                            $blueprint->bigIncrements($column);

                            continue;
                        }
                        $type = $attribute['type'];
                        $definition = match (true) {
                            $type === 'BIGINT' => $blueprint->unsignedBigInteger($column),
                            $type === 'INT' => $blueprint->integer($column),
                            $type === 'BOOLEAN' => $blueprint->boolean($column),
                            $type === 'DATE' => $blueprint->date($column),
                            $type === 'DATETIME' => $blueprint->dateTime($column),
                            $type === 'TEXT' => $blueprint->text($column),
                            str_starts_with($type, 'DECIMAL') => $blueprint->decimal($column, 15, 2),
                            default => $blueprint->string($column, (int) preg_replace('/\D/', '', $type)),
                        };
                        if ($attribute['nullable']) {
                            $definition->nullable();
                        }
                        if ($attribute['unique']) {
                            $definition->unique();
                        }
                        if ($attribute['fk']) {
                            $parent = $attribute['fk']['parent'];
                            $parentKey = collect($tables[$parent]['attrs'])->firstWhere('pk', true)['name'];
                            $blueprint->foreign($column)->references($parentKey)->on($parent)->restrictOnDelete()->restrictOnUpdate();
                        }
                    }
                    if (isset($schema['multi_unique'][$name])) {
                        $blueprint->unique($schema['multi_unique'][$name]);
                    }
                });
            }
        }
        DB::beginTransaction();
    }

    protected function bongkarSkemaYoke(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
}
