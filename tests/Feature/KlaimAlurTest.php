<?php

namespace Tests\Feature;

use App\Models\BeritaAcaraKerusakan;
use App\Models\DetailKlaim;
use App\Models\DetailPenerimaan;
use App\Models\DetailPengiriman;
use App\Models\DetailPo;
use App\Models\Distributor;
use App\Models\Forecast;
use App\Models\FotoBukti;
use App\Models\Klaim;
use App\Models\PenerimaanBarang;
use App\Models\Pengguna;
use App\Models\Pengiriman;
use App\Models\Peran;
use App\Models\Produk;
use App\Models\PurchaseOrder;
use App\Models\Wilayah;
use App\Services\KlaimService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembangunSkemaYoke;
use Tests\TestCase;

class KlaimAlurTest extends TestCase
{
    use MembangunSkemaYoke;

    private Distributor $distributor;

    private Pengguna $penggunaDistributor;

    private PenerimaanBarang $penerimaan;

    /** Baris penerimaan dengan 40 karton tidak layak jual. */
    private DetailPenerimaan $barisRusak;

    /** Baris penerimaan dengan 20 karton kurang terhadap invoice. */
    private DetailPenerimaan $barisKurang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-24 10:00:00'));
        $this->bangunSkemaYoke();
        Storage::fake('local');

        $this->distributor = Distributor::factory()->create([
            'nama' => 'PT Uji Niaga', 'id_wilayah' => Wilayah::factory()->create(['nama_wilayah' => 'Sumatera Barat'])->id_wilayah,
        ]);
        $this->penggunaDistributor = Pengguna::factory()->create([
            'id_peran' => Peran::factory()->create(['nama_peran' => 'Distributor'])->id_peran, 'id_distributor' => $this->distributor->id_distributor,
        ]);
        Pengguna::factory()->create(['nama' => 'SAM Uji', 'id_peran' => Peran::factory()->create(['nama_peran' => 'SAM'])->id_peran]);
        Pengguna::factory()->create(['id_peran' => Peran::factory()->create(['nama_peran' => 'Logistik Pusat'])->id_peran]);

        $this->penerimaan = $this->buatKedatangan($this->distributor, 'UJIU 1234567');
        [$this->barisRusak, $this->barisKurang] = $this->penerimaan->detailPenerimaanRecordsAsPenerimaan()->orderBy('id_detail_penerimaan')->get()->all();
    }

    protected function tearDown(): void
    {
        $this->bongkarSkemaYoke();
        $this->travelBack();
        parent::tearDown();
    }

    public function test_full_claim_flow_from_submission_to_completion(): void
    {
        $this->sebagaiDistributor()->post(route('klaim.distributor.simpan'), $this->formKlaim([
            ['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Penyok', 'jumlah' => 40, 'foto' => [UploadedFile::fake()->image('penyok.jpg')]],
            ['id_detail_penerimaan' => $this->barisKurang->id_detail_penerimaan, 'jenis_kerusakan' => 'Kurang Jumlah', 'jumlah' => 20],
        ]))->assertRedirect()->assertSessionHas('success');

        $klaim = Klaim::query()->sole();
        $this->assertSame(KlaimService::STATUS_MENUNGGU_SAM, $klaim->status);
        [$rusak, $kurang] = $klaim->detailKlaimRecords()->orderBy('id_detail_klaim')->get()->all();
        $this->assertSame('Perusahaan', $rusak->penanggung_jawab);
        Storage::disk('local')->assertExists(FotoBukti::query()->sole()->path_berkas);

        $this->post(route('klaim.sam.jadwal', $klaim), ['jadwal_kunjungan' => '2026-11-24'])->assertSessionHasNoErrors();
        $this->get(route('klaim.sam.antrean'))->assertOk()->assertViewHas('stats', fn (array $stats) => $stats['kunjungan_hari_ini'] === 1);

        $this->verifikasi($klaim, $rusak, $kurang)->assertRedirect(route('klaim.sam.pengesahan', $klaim));
        $this->assertSame([40, 15], [$rusak->fresh()->jumlah_terverifikasi, $kurang->fresh()->jumlah_terverifikasi]);
        $this->assertSame('SAM', FotoBukti::query()->latest('id_foto')->first()->sumber);

        $this->post(route('klaim.sam.sahkan', $klaim), ['tanda_tangan' => $this->tandaTangan()])->assertRedirect(route('klaim.sam.antrean'));
        $bak = BeritaAcaraKerusakan::query()->sole();
        $this->assertSame('BAK-00001', $bak->nomor_bak);
        $this->assertSame(KlaimService::STATUS_DI_LOGISTIK, $klaim->fresh()->status);

        // Logistik mengembalikan ke SAM; pengesahan ulang memperbarui BAK yang sama.
        $this->post(route('klaim.logistik.kembalikan', $klaim), ['catatan' => 'Foto lokasi kurang jelas.'])->assertSessionHasNoErrors();
        $this->assertSame(KlaimService::STATUS_MENUNGGU_SAM, $klaim->fresh()->status);
        $this->assertSame(KlaimService::BAK_DIBATALKAN, $bak->fresh()->status);
        $this->assertSame(KlaimService::VERIFIKASI_MENUNGGU, $rusak->fresh()->status_verifikasi);
        $this->get(route('klaim.sam.pengesahan', $klaim))->assertRedirect(route('klaim.sam.verifikasi', $klaim));

        $this->verifikasi($klaim, $rusak, $kurang);
        $this->post(route('klaim.sam.sahkan', $klaim), ['tanda_tangan' => $this->tandaTangan()])->assertSessionHasNoErrors();
        $this->assertSame(1, BeritaAcaraKerusakan::query()->count());
        $this->assertSame(['BAK-00001', KlaimService::BAK_DISAHKAN], [$bak->fresh()->nomor_bak, $bak->fresh()->status]);

        $this->post(route('klaim.logistik.selesaikan', $klaim), ['bentuk_penyelesaian' => 'Ganti Rugi', 'catatan' => 'Dibayar bersama tagihan.'])
            ->assertRedirect(route('klaim.logistik.proses'));
        $klaim->refresh();
        $this->assertSame([KlaimService::STATUS_SELESAI, 'Ganti Rugi'], [$klaim->status, $klaim->bentuk_penyelesaian]);
        $this->assertNotNull($klaim->tanggal_penyelesaian);

        $this->sebagaiDistributor()->get(route('klaim.distributor.detail', $klaim))->assertOk()->assertSee('BAK-00001')->assertSee('Ganti Rugi');
        $this->get(route('klaim.bak', $klaim))->assertOk()->assertSee('SAM Uji')->assertSee('UJIU 1234567');
    }

    public function test_returned_claim_can_be_corrected_and_resubmitted_for_the_same_arrival(): void
    {
        $this->sebagaiDistributor()->post(route('klaim.distributor.simpan'), $this->formKlaim([
            ['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Bocor', 'jumlah' => 10, 'foto' => [UploadedFile::fake()->image('lama.jpg')]],
        ]));
        $klaim = Klaim::query()->sole();
        $fotoLama = FotoBukti::query()->sole()->path_berkas;

        $this->post(route('klaim.sam.kembalikan', $klaim), ['catatan' => 'Jumlah tidak sesuai foto.'])->assertRedirect(route('klaim.sam.antrean'));
        $this->assertSame(KlaimService::STATUS_DIKEMBALIKAN, $klaim->fresh()->status);
        $this->sebagaiDistributor()->get(route('klaim.distributor.ajukan'))->assertOk()->assertSee('(perlu diperbaiki)');

        $this->sebagaiDistributor()->post(route('klaim.distributor.simpan'), $this->formKlaim([
            ['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Penyok', 'jumlah' => 35],
        ]))->assertSessionHasNoErrors();

        $klaim->refresh();
        $this->assertSame(1, Klaim::query()->count());
        $this->assertSame(KlaimService::STATUS_MENUNGGU_SAM, $klaim->status);
        $this->assertNull($klaim->catatan_pengembalian);
        $this->assertSame([['Penyok', 35]], $klaim->detailKlaimRecords->map(fn (DetailKlaim $d) => [$d->jenis_kerusakan, $d->jumlah])->all());
        $this->assertSame(0, FotoBukti::query()->count());
        Storage::disk('local')->assertMissing($fotoLama);
    }

    public function test_claim_above_damaged_or_missing_quantity_is_rejected(): void
    {
        $this->sebagaiDistributor()->post(route('klaim.distributor.simpan'), $this->formKlaim([
            ['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Penyok', 'jumlah' => 30],
            ['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Bocor', 'jumlah' => 11],
        ]))->assertSessionHasErrors('baris');

        $this->assertSame(0, Klaim::query()->count());
    }

    public function test_arrival_with_claim_in_progress_cannot_be_claimed_again(): void
    {
        $baris = [['id_detail_penerimaan' => $this->barisKurang->id_detail_penerimaan, 'jenis_kerusakan' => 'Kurang Jumlah', 'jumlah' => 5]];
        $this->sebagaiDistributor()->post(route('klaim.distributor.simpan'), $this->formKlaim($baris))->assertSessionHasNoErrors();
        $this->sebagaiDistributor()->post(route('klaim.distributor.simpan'), $this->formKlaim($baris))->assertSessionHasErrors('id_penerimaan');

        $this->assertSame(1, Klaim::query()->count());
    }

    public function test_other_distributor_cannot_see_or_claim_this_arrival(): void
    {
        $this->sebagaiDistributor()->post(route('klaim.distributor.simpan'), $this->formKlaim([
            ['id_detail_penerimaan' => $this->barisKurang->id_detail_penerimaan, 'jenis_kerusakan' => 'Kurang Jumlah', 'jumlah' => 5],
        ]));
        $klaim = Klaim::query()->sole();
        $lain = Distributor::factory()->create(['id_wilayah' => $this->distributor->id_wilayah]);

        $this->withSession(['active_distributor_id' => $lain->id_distributor])->get(route('klaim.distributor.detail', $klaim))->assertNotFound();
        $this->withSession(['active_distributor_id' => $lain->id_distributor])->post(route('klaim.distributor.simpan'), $this->formKlaim([
            ['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Penyok', 'jumlah' => 5],
        ]))->assertForbidden();
    }

    public function test_bak_cannot_be_signed_before_verification_or_with_invalid_signature(): void
    {
        $this->sebagaiDistributor()->post(route('klaim.distributor.simpan'), $this->formKlaim([
            ['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Penyok', 'jumlah' => 5],
        ]));
        $klaim = Klaim::query()->sole();

        $this->post(route('klaim.sam.sahkan', $klaim), ['tanda_tangan' => $this->tandaTangan()])->assertSessionHasErrors('tanda_tangan');
        $this->post(route('klaim.sam.sahkan', $klaim), ['tanda_tangan' => 'data:image/png;base64,'.base64_encode('bukan gambar')])->assertSessionHasErrors('tanda_tangan');
        $this->assertSame(0, BeritaAcaraKerusakan::query()->count());
    }

    public function test_api_computes_responsible_party_on_the_server(): void
    {
        $this->actingAs($this->penggunaDistributor)->postJson(route('api.v1.claims.store'), [
            'id_penerimaan' => $this->penerimaan->id_penerimaan,
            'items' => [
                ['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Kedaluwarsa', 'jumlah' => 5, 'penanggung_jawab' => 'Perusahaan'],
            ],
        ])->assertCreated()->assertJsonPath('data.items.0.responsible_party', 'Distributor');

        $this->actingAs($this->penggunaDistributor)->postJson(route('api.v1.claims.store'), [
            'id_penerimaan' => $this->penerimaan->id_penerimaan,
            'items' => [['id_detail_penerimaan' => $this->barisRusak->id_detail_penerimaan, 'jenis_kerusakan' => 'Rusak Bebas', 'jumlah' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items.0.jenis_kerusakan');
    }

    private function sebagaiDistributor(): static
    {
        return $this->withSession(['active_distributor_id' => $this->distributor->id_distributor]);
    }

    /** @param  list<array<string, mixed>>  $baris */
    private function formKlaim(array $baris): array
    {
        return ['id_penerimaan' => $this->penerimaan->id_penerimaan, 'baris' => $baris];
    }

    private function verifikasi(Klaim $klaim, DetailKlaim $sesuai, DetailKlaim $tidakSesuai)
    {
        return $this->post(route('klaim.sam.simpan-verifikasi', $klaim), [
            'hasil' => [
                $sesuai->id_detail_klaim => ['status_verifikasi' => 'Disetujui'],
                $tidakSesuai->id_detail_klaim => ['status_verifikasi' => 'Ditolak', 'jumlah_terverifikasi' => 15, 'catatan' => '5 karton ditemukan di palet lain.'],
            ],
            'foto_lokasi' => [$sesuai->id_detail_klaim => [UploadedFile::fake()->image('lokasi.jpg')]],
        ]);
    }

    private function tandaTangan(): string
    {
        $gambar = imagecreatetruecolor(20, 10);
        ob_start();
        imagepng($gambar);

        return 'data:image/png;base64,'.base64_encode(ob_get_clean());
    }

    private function buatKedatangan(Distributor $distributor, string $nomorKontainer): PenerimaanBarang
    {
        $po = PurchaseOrder::factory()->create(['id_forecast' => Forecast::factory()->create(['id_distributor' => $distributor->id_distributor])->id_forecast]);
        $pengiriman = Pengiriman::factory()->create(['id_po' => $po->id_po, 'nomor_kontainer' => $nomorKontainer]);
        $penerimaan = PenerimaanBarang::factory()->create(['id_pengiriman' => $pengiriman->id_pengiriman]);
        foreach ([[500, 500, 460], [500, 480, 480]] as [$dikirim, $diterima, $baik]) {
            $detailPengiriman = DetailPengiriman::factory()->create([
                'id_pengiriman' => $pengiriman->id_pengiriman,
                'id_detail_po' => DetailPo::factory()->create(['id_po' => $po->id_po, 'id_produk' => Produk::factory()->create()->id_produk])->id_detail_po,
                'jumlah_dikirim' => $dikirim,
            ]);
            DetailPenerimaan::factory()->create([
                'id_penerimaan' => $penerimaan->id_penerimaan, 'id_detail_pengiriman' => $detailPengiriman->id_detail_pengiriman,
                'jumlah_diterima' => $diterima, 'jumlah_baik' => $baik, 'kondisi' => 'Uji',
            ]);
        }

        return $penerimaan;
    }
}
