<?php

namespace App\Http\Controllers\EPO;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogistikEPOController extends Controller
{
    private function getDistributorData()
    {
        return [
            'nama' => 'PT Minang Niaga Jaya',
            'cabang' => 'Cabang Padang',
            'alamat' => 'Jl. Raya By Pass Km. 12, Padang, Sumatera Barat',
            'npwp' => '01.234.567.8-201.000',
            'kode' => 'DST-PDG-001',
        ];
    }

    private function getPabrikData()
    {
        return [
            'nama' => 'PT Yoke Food Industries Indonesia',
            'alamat' => 'Kawasan Industri Jababeka, Cikarang, Jawa Barat',
            'plant' => 'Plant Cikarang #02',
        ];
    }

    private function getSKUData()
    {
        return [
            ['kode' => 'YFI-TM-500',  'nama' => 'YFI Teh Melati 500ml',        'kemasan' => 'Ctn @ 24 Btl', 'rekomendasi' => 2900, 'usulan' => 3000, 'kuota' => 3000, 'harga' => 95000],
            ['kode' => 'FAN-SPK-325', 'nama' => 'FAN Sparkling Water 325ml',   'kemasan' => 'Ctn @ 24 Can', 'rekomendasi' => 2500, 'usulan' => 2800, 'kuota' => 2500, 'harga' => 95000],
            ['kode' => 'YFI-JA-330',  'nama' => 'YFI Jus Apel 330ml',          'kemasan' => 'Ctn @ 24 Btl', 'rekomendasi' => 2400, 'usulan' => 2500, 'kuota' => 2500, 'harga' => 88000],
            ['kode' => 'YFI-ISO-330', 'nama' => 'YFI Isotonik Refresh 330ml',  'kemasan' => 'Ctn @ 24 Btl', 'rekomendasi' => 1400, 'usulan' => 1500, 'kuota' => 1500, 'harga' => 95000],
        ];
    }

    public function antrean()
    {
        $forecasts = [
            ['id' => 1, 'distributor' => 'PT Minang Niaga Jaya', 'area' => 'Cabang Padang',   'periode' => 'Nov 2026', 'volume' => 9800,  'nilai' => 913500000,  'status' => 'Menunggu Penetapan', 'tgl' => '27 Okt 2026'],
            ['id' => 2, 'distributor' => 'PT Sumber Segar Distribusi', 'area' => 'Cabang Medan', 'periode' => 'Nov 2026', 'volume' => 14200, 'nilai' => 1320000000, 'status' => 'Menunggu Penetapan', 'tgl' => '27 Okt 2026'],
        ];

        $epos = [
            ['id' => 11, 'no' => 'PO-YFI-2026-0892', 'distributor' => 'PT Minang Niaga Jaya', 'area' => 'Cabang Padang', 'volume' => 9500, 'nilai' => 885000000, 'status' => 'Perlu Verifikasi', 'tgl' => '29 Okt 2026'],
        ];

        return view('pages.e-po.logistik.antrean', [
            'forecasts' => $forecasts,
            'epos' => $epos,
            'stats' => [
                'forecast_pending' => count($forecasts),
                'volume_antrean' => array_sum(array_column($forecasts, 'volume')),
                'epo_verifikasi' => count($epos),
                'utilisasi' => 78.4,
            ],
        ]);
    }

    public function detailForecast($id)
    {
        return view('pages.e-po.logistik.detail', [
            'id' => $id,
            'distributor' => $this->getDistributorData(),
            'pabrik' => $this->getPabrikData(),
            'fc' => ['kode' => 'FC-PDG-2026-11', 'periode' => 'November 2026', 'kapasitas' => 10500],
            'skus' => $this->getSKUData(),
        ]);
    }

    public function tetapkanKuota(Request $request, $id)
    {
        return redirect()->route('pages.e-po.logistik.antrean')
            ->with('success', 'Kuota berhasil ditetapkan & notifikasi dikirim ke distributor.');
    }

    public function verifikasiEpo($id)
    {
        $skus = $this->getSKUData();
        $rows = array_map(fn ($r) => $r + ['qty' => $r['kuota'], 'subtotal' => $r['kuota'] * $r['harga']], $skus);

        return view('pages.e-po.logistik.verifikasi-epo', [
            'id' => $id,
            'distributor' => $this->getDistributorData(),
            'pabrik' => $this->getPabrikData(),
            'fc' => ['kuota' => array_sum(array_column($skus, 'kuota'))],
            'po' => [
                'no_po' => 'PO-YFI-2026-0892',
                'tgl' => '29 Oktober 2026',
                'kirim' => '12 November 2026',
                'top' => '30 Hari Kalender Bank Transfer',
                'rows' => $rows,
                'total_qty' => array_sum(array_column($rows, 'qty')),
                'total' => array_sum(array_column($rows, 'subtotal')),
            ],
        ]);
    }

    public function setujuiEpo(Request $request, $id)
    {
        return redirect()->route('pages.e-po.logistik.antrean')
            ->with('success', 'E-PO disetujui dan dokumen PO resmi berkode SAP diterbitkan.');
    }
}
