<?php

namespace App\Http\Controllers\Klaim;

use App\Http\Controllers\Controller;
use App\Models\DetailKlaim;
use App\Models\FotoBukti;
use App\Models\Klaim;
use App\Services\KlaimService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Berkas modul klaim (foto bukti, tanda tangan, dokumen BAK) yang tersimpan di disk privat. */
class KlaimDokumenController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [function (Request $request, Closure $next) {
            abort_unless(app()->environment(['local', 'testing']), 403, 'Modul Klaim belum memakai autentikasi produksi.');

            return $next($request);
        }];
    }

    public function foto(FotoBukti $foto): BinaryFileResponse
    {
        return $this->berkas($foto->path_berkas);
    }

    public function tandaTangan(Klaim $klaim): BinaryFileResponse
    {
        $bak = $klaim->beritaAcaraKerusakanRecord;
        abort_unless($bak, 404);

        return $this->berkas($bak->tanda_tangan_digital);
    }

    /** Dokumen BAK siap cetak; tombol Unduh membuka dialog cetak browser untuk disimpan sebagai PDF. */
    public function bak(Request $request, Klaim $klaim): View
    {
        $klaim->load([
            'penerimaan.pengiriman.po.forecast.distributor.wilayah',
            'detailKlaimRecords.detailPenerimaan.detailPengiriman.detailPo.produk',
            'beritaAcaraKerusakanRecord.pengesah',
        ]);
        $bak = $klaim->beritaAcaraKerusakanRecord;
        abort_unless($bak, 404);
        $baris = $klaim->detailKlaimRecords->map(fn (DetailKlaim $detail) => [
            'produk' => $detail->detailPenerimaan->detailPengiriman->detailPo->produk->nama_produk,
            'jenis' => $detail->jenis_kerusakan,
            'diajukan' => $detail->jumlah,
            'terverifikasi' => (int) $detail->jumlah_terverifikasi,
            'catatan' => $detail->catatan_verifikasi,
            'penanggung' => $detail->penanggung_jawab,
        ]);

        return view('pages.klaim.bak', [
            'klaim' => $klaim,
            'kodeKlaim' => KlaimService::kodeKlaim($klaim),
            'bak' => $bak,
            'distributor' => $klaim->penerimaan->pengiriman->po->forecast->distributor,
            'baris' => $baris,
            'perPenanggung' => $baris->groupBy('penanggung')->map(fn ($kelompok) => $kelompok->sum('terverifikasi')),
            'adaTandaTangan' => Storage::disk('local')->exists($bak->tanda_tangan_digital),
            'cetak' => $request->boolean('cetak'),
        ]);
    }

    private function berkas(string $path): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path));
    }
}
