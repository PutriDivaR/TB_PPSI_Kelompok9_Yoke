<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PelaporanController extends Controller
{
    private function localOnly(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403, 'Pratinjau Pelaporan hanya tersedia di lingkungan lokal.');
    }

    public function index(Request $request): View
    {
        $this->localOnly();

        return view('pages.pelaporan.index', [
            'start' => $request->query('mulai', now()->startOfMonth()->format('Y-m-d')),
            'end' => $request->query('selesai', now()->format('Y-m-d')),
        ]);
    }

    public function databaseStatus(): JsonResponse
    {
        $this->localOnly();
        try {
            $schema = json_decode(file_get_contents(database_path('schema/schema_erd.json')), true, flags: JSON_THROW_ON_ERROR);
            $counts = [];
            foreach ($schema['tables'] as $table) {
                if (! Schema::hasTable($table['name'])) {
                    return response()->json(['connected' => true, 'ready' => false, 'missing_table' => $table['name']], 503);
                }
                $counts[$table['name']] = DB::table($table['name'])->count();
            }

            return response()->json(['connected' => true, 'ready' => true, 'tables' => count($counts), 'records' => $counts]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['connected' => false, 'ready' => false], 503);
        }
    }
}
