<?php

namespace App\Http\Controllers\EPO;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DistributorEPOController extends Controller
{
    private function getDistributorData()
    {
        return [
            'nama' => 'PT Minang Niaga Jaya',
            'cabang' => 'Cabang Padang',
            'alamat' => 'Jl. Raya By Pass Km. 12, Padang, Sumatera Barat',
            'npwp' => '01.234.567.8-201.000',
            'kode' => 'DST-PDG-001',
            'pic' => 'Budi Santoso',
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
            ['kode' => 'YFI-TM-500',  'nama' => 'YFI Teh Melati 500ml',        'kemasan' => 'Ctn @ 24 Btl', 'rekomendasi' => 2900, 'usulan' => 3000, 'kuota' => 3000, 'harga' => 95000, 'alasan' => 'Tren penjualan naik 8% MoM'],
            ['kode' => 'FAN-SPK-325', 'nama' => 'FAN Sparkling Water 325ml',   'kemasan' => 'Ctn @ 24 Can', 'rekomendasi' => 2500, 'usulan' => 2800, 'kuota' => 2500, 'harga' => 95000, 'alasan' => 'Antisipasi promo akhir tahun'],
            ['kode' => 'YFI-JA-330',  'nama' => 'YFI Jus Apel 330ml',          'kemasan' => 'Ctn @ 24 Btl', 'rekomendasi' => 2400, 'usulan' => 2500, 'kuota' => 2500, 'harga' => 88000, 'alasan' => 'Stok toko modern menipis'],
            ['kode' => 'YFI-ISO-330', 'nama' => 'YFI Isotonik Refresh 330ml',  'kemasan' => 'Ctn @ 24 Btl', 'rekomendasi' => 1400, 'usulan' => 1500, 'kuota' => 1500, 'harga' => 95000, 'alasan' => 'Event olahraga komunitas'],
        ];
    }

    private function getForecastSummary()
    {
        $skus = $this->getSKUData();

        return [
            'kode' => 'FC-PDG-2026-11',
            'periode' => 'November 2026',
            'rekomendasi' => array_sum(array_column($skus, 'rekomendasi')),
            'usulan' => array_sum(array_column($skus, 'usulan')),
            'kuota' => array_sum(array_column($skus, 'kuota')),
            'kapasitas' => 10500,
            'nilai_kuota' => array_sum(array_map(fn ($r) => $r['kuota'] * $r['harga'], $skus)),
        ];
    }

    public function hitungForecast()
    {
        $riwayat = [
            ['kode' => 'FC-PDG-2026-10', 'periode' => 'Oktober 2026',   'tgl' => '14 Sep 2026, 10:12', 'volume' => 9500, 'status' => 'Disetujui'],
            ['kode' => 'FC-PDG-2026-09', 'periode' => 'September 2026', 'tgl' => '12 Agu 2026, 09:40', 'volume' => 9200, 'status' => 'Disetujui'],
        ];

        return view('pages.e-po.distributor.hitung-forecast', [
            'distributor' => $this->getDistributorData(),
            'fc' => $this->getForecastSummary(),
            'riwayat' => $riwayat,
        ]);
    }

    public function ajukanForecast()
    {
        return view('pages.e-po.distributor.ajukan-forecast', [
            'distributor' => $this->getDistributorData(),
            'fc' => $this->getForecastSummary(),
            'skus' => $this->getSKUData(),
        ]);
    }

    public function submitForecast(Request $request)
    {
        return redirect()->route('e-po.distributor.input-epo')
            ->with('success', 'Forecast berhasil diajukan ke Logistik Pusat.');
    }

    public function inputEpo(Request $request)
    {
        $skus = $this->getSKUData();

        if ($request->has('simulasi_error')) {
            $skus[1]['input'] = 2800; // Menguji tampilan Layar 5B (Over Quota)
        }

        return view('pages.e-po.distributor.input-epo', [
            'distributor' => $this->getDistributorData(),
            'fc' => $this->getForecastSummary(),
            'skus' => $skus,
        ]);
    }

    public function submitEpo(Request $request)
    {
        $skus = collect($this->getSKUData())->keyBy('kode');
        foreach ($request->input('qty', []) as $kode => $qty) {
            if ((int) $qty > ($skus[$kode]['kuota'] ?? 0)) {
                return redirect()->route('e-po.distributor.input-epo', ['simulasi_error' => 1])
                    ->with('error', "Kuantitas {$kode} melebihi kuota yang ditetapkan.");
            }
        }

        return redirect()->route('pages.e-po.distributor.epo-selesai');
    }

    public function epoSelesai()
    {
        $skus = $this->getSKUData();
        $rows = array_map(fn ($r) => $r + ['qty' => $r['kuota'], 'subtotal' => $r['kuota'] * $r['harga']], $skus);

        $po = [
            'no_po' => 'PO-YFI-2026-0892',
            'no_sap' => 'SAP-4500098712',
            'tgl' => '29 Oktober 2026',
            'kirim' => '12 November 2026',
            'top' => '30 Hari Kalender Bank Transfer',
            'rows' => $rows,
            'total_qty' => array_sum(array_column($rows, 'qty')),
            'total' => array_sum(array_column($rows, 'subtotal')),
            'ttd_dist' => 'Budi Santoso',
            'jabatan_d' => 'Branch Manager - PT Minang Niaga Jaya',
            'ttd_log' => 'Hendrawan Pratama, S.T., M.Log.',
            'jabatan_l' => 'Head of Logistics & Supply Chain - PT YFI',
        ];

        return view('pages.e-po.distributor.epo-selesai', [
            'distributor' => $this->getDistributorData(),
            'pabrik' => $this->getPabrikData(),
            'po' => $po,
        ]);
    }
}
