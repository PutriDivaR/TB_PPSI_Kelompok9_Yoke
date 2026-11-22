<?php

namespace Database\Seeders;

use App\Models\DetailPenerimaan;
use App\Models\DetailPengiriman;
use App\Models\MilestonePengiriman;
use App\Models\PenerimaanBarang;
use App\Models\Pengiriman;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MonitoringDummySeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan ada PO yang disetujui
        $poPadang1 = PurchaseOrder::query()->where('nomor_po', 'PO-YFI-2026-0041')->with('detailPoRecordsAsPo')->first();
        $poPadang2 = PurchaseOrder::query()->where('nomor_po', 'PO-YFI-2026-0057')->with('detailPoRecordsAsPo')->first();
        $poMedan = PurchaseOrder::query()->where('nomor_po', 'PO-YFI-2026-0058')->with('detailPoRecordsAsPo')->first();
        $poRiau = PurchaseOrder::query()->where('nomor_po', 'PO-YFI-2026-0059')->with('detailPoRecordsAsPo')->first();

        if (! $poPadang1) {
            $this->command?->warn('PO-YFI-2026-0041 tidak ditemukan. Pastikan data_dummy_epo.txt sudah diimpor.');

            return;
        }

        DB::transaction(function () use ($poPadang1, $poPadang2, $poMedan, $poRiau) {
            // Bersihkan data lama jika ada
            $existingIds = Pengiriman::query()->whereIn('nomor_kontainer', [
                'CNTR-2025-014', 'CNTR-2025-015', 'CNTR-2025-016', 'CNTR-2025-017', 'CNTR-2025-018', 'TCLU 4829103',
            ])->pluck('id_pengiriman');

            if ($existingIds->isNotEmpty()) {
                DetailPenerimaan::query()->whereIn('id_penerimaan', PenerimaanBarang::query()->whereIn('id_pengiriman', $existingIds)->pluck('id_penerimaan'))->delete();
                PenerimaanBarang::query()->whereIn('id_pengiriman', $existingIds)->delete();
                MilestonePengiriman::query()->whereIn('id_pengiriman', $existingIds)->delete();
                DetailPengiriman::query()->whereIn('id_pengiriman', $existingIds)->delete();
                Pengiriman::query()->whereIn('id_pengiriman', $existingIds)->delete();
            }

            // 1. Kontainer 1: CNTR-2025-014 (Dalam Perjalanan ke Padang)
            $ship1 = Pengiriman::create([
                'id_po' => $poPadang1->id_po,
                'nomor_kontainer' => 'CNTR-2025-014',
                'nama_vessel' => 'MV Sinar Bintan V.24',
                'eta_awal' => Carbon::now()->addDays(5)->toDateString(),
                'eta_terkini' => Carbon::now()->addDays(5)->toDateString(),
                'status' => 'Dalam Perjalanan',
            ]);

            // Detail muatan
            if ($item = $poPadang1->detailPoRecordsAsPo->first()) {
                DetailPengiriman::create([
                    'id_pengiriman' => $ship1->id_pengiriman,
                    'id_detail_po' => $item->id_detail_po,
                    'jumlah_dikirim' => 2,
                ]);
            }

            // Milestones
            MilestonePengiriman::insert([
                [
                    'id_pengiriman' => $ship1->id_pengiriman,
                    'urutan' => 1,
                    'nama_tahap' => 'Booking Dikonfirmasi',
                    'tanggal_pembaruan' => Carbon::now()->subDays(4)->setTime(8, 15),
                    'keterangan' => 'Booking kontainer feeder vessel telah diverifikasi oleh Logistik Pusat.',
                ],
                [
                    'id_pengiriman' => $ship1->id_pengiriman,
                    'urutan' => 2,
                    'nama_tahap' => 'Muat di Pelabuhan Asal',
                    'tanggal_pembaruan' => Carbon::now()->subDays(3)->setTime(16, 40),
                    'keterangan' => 'Kontainer dimuat ke kapal MV Sinar Bintan di Pelabuhan Tanjung Priok.',
                ],
                [
                    'id_pengiriman' => $ship1->id_pengiriman,
                    'urutan' => 3,
                    'nama_tahap' => 'Dalam Perjalanan',
                    'tanggal_pembaruan' => Carbon::now()->subDays(1)->setTime(6, 0),
                    'keterangan' => 'Kapal sedang berlayar menuju Pelabuhan Teluk Bayur Padang.',
                ],
            ]);

            // 2. Kontainer 2: CNTR-2025-015 (Terlambat ke Pekanbaru / Riau)
            $poRiauTarget = $poRiau ?? $poPadang1;
            $ship2 = Pengiriman::create([
                'id_po' => $poRiauTarget->id_po,
                'nomor_kontainer' => 'CNTR-2025-015',
                'nama_vessel' => 'KM Meratus Deli',
                'eta_awal' => Carbon::now()->subDays(1)->toDateString(),
                'eta_terkini' => Carbon::now()->addDays(2)->toDateString(),
                'status' => 'Terlambat',
            ]);

            if ($item = $poRiauTarget->detailPoRecordsAsPo->first()) {
                DetailPengiriman::create([
                    'id_pengiriman' => $ship2->id_pengiriman,
                    'id_detail_po' => $item->id_detail_po,
                    'jumlah_dikirim' => 2,
                ]);
            }

            MilestonePengiriman::insert([
                [
                    'id_pengiriman' => $ship2->id_pengiriman,
                    'urutan' => 1,
                    'nama_tahap' => 'Booking Dikonfirmasi',
                    'tanggal_pembaruan' => Carbon::now()->subDays(6)->setTime(9, 0),
                    'keterangan' => 'Booking armada laut terkonfirmasi.',
                ],
                [
                    'id_pengiriman' => $ship2->id_pengiriman,
                    'urutan' => 2,
                    'nama_tahap' => 'Muat di Pelabuhan Asal',
                    'tanggal_pembaruan' => Carbon::now()->subDays(4)->setTime(14, 0),
                    'keterangan' => 'Kontainer tertahan di pelabuhan akibat verifikasi kelengkapan dokumen pabean.',
                ],
            ]);

            // 3. Kontainer 3: CNTR-2025-016 (Dalam Perjalanan ke Medan / Jambi)
            $poMedanTarget = $poMedan ?? $poPadang1;
            $ship3 = Pengiriman::create([
                'id_po' => $poMedanTarget->id_po,
                'nomor_kontainer' => 'CNTR-2025-016',
                'nama_vessel' => 'KM Armada Sejahtera',
                'eta_awal' => Carbon::now()->addDays(7)->toDateString(),
                'eta_terkini' => Carbon::now()->addDays(7)->toDateString(),
                'status' => 'Dalam Perjalanan',
            ]);

            if ($item = $poMedanTarget->detailPoRecordsAsPo->first()) {
                DetailPengiriman::create([
                    'id_pengiriman' => $ship3->id_pengiriman,
                    'id_detail_po' => $item->id_detail_po,
                    'jumlah_dikirim' => 3,
                ]);
            }

            MilestonePengiriman::insert([
                [
                    'id_pengiriman' => $ship3->id_pengiriman,
                    'urutan' => 1,
                    'nama_tahap' => 'Booking Dikonfirmasi',
                    'tanggal_pembaruan' => Carbon::now()->subDays(2)->setTime(10, 0),
                    'keterangan' => 'Booking disetujui.',
                ],
                [
                    'id_pengiriman' => $ship3->id_pengiriman,
                    'urutan' => 2,
                    'nama_tahap' => 'Dalam Perjalanan',
                    'tanggal_pembaruan' => Carbon::now()->subDays(1)->setTime(11, 30),
                    'keterangan' => 'Perjalanan laut menuju pelabuhan regional Belawan.',
                ],
            ]);

            // 4. Kontainer 4: CNTR-2025-017 (Menunggu Muat di Pabrik)
            $poPadang2Target = $poPadang2 ?? $poPadang1;
            $ship4 = Pengiriman::create([
                'id_po' => $poPadang2Target->id_po,
                'nomor_kontainer' => 'CNTR-2025-017',
                'nama_vessel' => 'MV Pulau Tello',
                'eta_awal' => Carbon::now()->addDays(10)->toDateString(),
                'eta_terkini' => Carbon::now()->addDays(10)->toDateString(),
                'status' => 'Menunggu Muat',
            ]);

            if ($item = $poPadang2Target->detailPoRecordsAsPo->first()) {
                DetailPengiriman::create([
                    'id_pengiriman' => $ship4->id_pengiriman,
                    'id_detail_po' => $item->id_detail_po,
                    'jumlah_dikirim' => 2,
                ]);
            }

            MilestonePengiriman::create([
                'id_pengiriman' => $ship4->id_pengiriman,
                'urutan' => 1,
                'nama_tahap' => 'Booking Dikonfirmasi',
                'tanggal_pembaruan' => Carbon::now()->subHours(12),
                'keterangan' => 'Menunggu jadwal stuffing kontainer di warehouse pabrik.',
            ]);

            // 5. Kontainer 5: CNTR-2025-018 (Terlambat karena Cuaca Buruk)
            $ship5 = Pengiriman::create([
                'id_po' => $poPadang1->id_po,
                'nomor_kontainer' => 'CNTR-2025-018',
                'nama_vessel' => 'MV Samudera Mas',
                'eta_awal' => Carbon::now()->subDays(2)->toDateString(),
                'eta_terkini' => Carbon::now()->addDays(3)->toDateString(),
                'status' => 'Terlambat',
            ]);

            if (isset($poPadang1->detailPoRecordsAsPo[1])) {
                DetailPengiriman::create([
                    'id_pengiriman' => $ship5->id_pengiriman,
                    'id_detail_po' => $poPadang1->detailPoRecordsAsPo[1]->id_detail_po,
                    'jumlah_dikirim' => 2,
                ]);
            }

            MilestonePengiriman::insert([
                [
                    'id_pengiriman' => $ship5->id_pengiriman,
                    'urutan' => 1,
                    'nama_tahap' => 'Booking Dikonfirmasi',
                    'tanggal_pembaruan' => Carbon::now()->subDays(7)->setTime(8, 0),
                    'keterangan' => 'Booking rute laut.',
                ],
                [
                    'id_pengiriman' => $ship5->id_pengiriman,
                    'urutan' => 2,
                    'nama_tahap' => 'Muat di Pelabuhan Asal',
                    'tanggal_pembaruan' => Carbon::now()->subDays(5)->setTime(16, 0),
                    'keterangan' => 'Cuaca buruk memperlambat izin keberangkatan kapal dari pelabuhan asal Tanjung Perak.',
                ],
            ]);

            // 6. Kontainer 6: TCLU 4829103 (Sudah Sampai / Selesai)
            $ship6 = Pengiriman::create([
                'id_po' => $poPadang1->id_po,
                'nomor_kontainer' => 'TCLU 4829103',
                'nama_vessel' => 'MV Sinar Bintan',
                'eta_awal' => Carbon::now()->subDays(3)->toDateString(),
                'eta_terkini' => Carbon::now()->subDays(3)->toDateString(),
                'status' => 'Selesai',
            ]);

            if ($item = $poPadang1->detailPoRecordsAsPo->first()) {
                $detShip = DetailPengiriman::create([
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'id_detail_po' => $item->id_detail_po,
                    'jumlah_dikirim' => 2,
                ]);

                // Penerimaan Barang
                $penerimaan = PenerimaanBarang::create([
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'tanggal_terima' => Carbon::now()->subDays(2)->setTime(14, 30),
                ]);

                DetailPenerimaan::create([
                    'id_penerimaan' => $penerimaan->id_penerimaan,
                    'id_detail_pengiriman' => $detShip->id_detail_pengiriman,
                    'jumlah_diterima' => 2,
                    'jumlah_baik' => 2,
                    'kondisi' => 'Baik',
                ]);
            }

            MilestonePengiriman::insert([
                [
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'urutan' => 1,
                    'nama_tahap' => 'Booking Dikonfirmasi',
                    'tanggal_pembaruan' => Carbon::now()->subDays(10)->setTime(8, 15),
                    'keterangan' => 'Pemesanan feeder vessel disetujui.',
                ],
                [
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'urutan' => 2,
                    'nama_tahap' => 'Muat di Pelabuhan Asal',
                    'tanggal_pembaruan' => Carbon::now()->subDays(8)->setTime(16, 40),
                    'keterangan' => 'Selesai muat di Tanjung Priok.',
                ],
                [
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'urutan' => 3,
                    'nama_tahap' => 'Dalam Perjalanan',
                    'tanggal_pembaruan' => Carbon::now()->subDays(6)->setTime(6, 0),
                    'keterangan' => 'Kapal berlayar.',
                ],
                [
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'urutan' => 4,
                    'nama_tahap' => 'Tiba di Pelabuhan Tujuan',
                    'tanggal_pembaruan' => Carbon::now()->subDays(4)->setTime(21, 10),
                    'keterangan' => 'Kapal sandar di Teluk Bayur.',
                ],
                [
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'urutan' => 5,
                    'nama_tahap' => 'Barang Diantar Jalur Darat',
                    'tanggal_pembaruan' => Carbon::now()->subDays(3)->setTime(6, 30),
                    'keterangan' => 'Kontainer dipindahkan ke truk darat menuju gudang distributor.',
                ],
                [
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'urutan' => 6,
                    'nama_tahap' => 'Barang Sudah Sampai',
                    'tanggal_pembaruan' => Carbon::now()->subDays(2)->setTime(14, 30),
                    'keterangan' => 'Kontainer tiba di gudang DC Padang By-Pass, segel utuh dan kondisi baik.',
                ],
                [
                    'id_pengiriman' => $ship6->id_pengiriman,
                    'urutan' => 7,
                    'nama_tahap' => 'Selesai',
                    'tanggal_pembaruan' => Carbon::now()->subDays(2)->setTime(15, 10),
                    'keterangan' => 'Pengiriman selesai dan seluruh dokumen penerimaan telah diterima.',
                ],
            ]);
        });
    }
}
