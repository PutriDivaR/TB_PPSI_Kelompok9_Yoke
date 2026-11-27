<?php

namespace Tests\Feature;

use App\Models\Distributor;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Models\Peran;
use App\Models\Produk;
use App\Models\SellOut;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MembangunSkemaYoke;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use MembangunSkemaYoke;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->bangunSkemaYoke();
    }

    protected function tearDown(): void
    {
        $this->bongkarSkemaYoke();
        parent::tearDown();
    }

    public function test_distributor_account_requires_a_company_not_assigned_to_another_account(): void
    {
        $admin = $this->buatPengguna('Admin');
        $company = Distributor::factory()->create();
        $this->buatPengguna('Distributor', ['id_distributor' => $company->id_distributor]);

        $this->followingRedirects()->from(route('admin.pengguna'))->actingAs($admin)->post(route('admin.pengguna.store'), [
            'nama' => 'Distributor Baru',
            'email' => 'distributor-baru@example.test',
            'peran' => 'Distributor',
            'id_distributor' => $company->id_distributor,
        ])->assertOk()
            ->assertSee('Setiap perusahaan hanya dapat memiliki satu akun distributor.')
            ->assertSee('Satu perusahaan distributor hanya dapat memiliki satu akun.');

        $this->assertDatabaseMissing('pengguna', ['email' => 'distributor-baru@example.test']);
    }

    public function test_distributor_account_requires_a_linked_company(): void
    {
        $admin = $this->buatPengguna('Admin');

        $this->from(route('admin.pengguna'))->actingAs($admin)->post(route('admin.pengguna.store'), [
            'nama' => 'Distributor Tanpa Perusahaan',
            'email' => 'tanpa-perusahaan@example.test',
            'peran' => 'Distributor',
        ])->assertSessionHasErrors('id_distributor');

        $this->assertDatabaseMissing('pengguna', ['email' => 'tanpa-perusahaan@example.test']);
    }

    public function test_account_with_audit_history_cannot_be_deleted(): void
    {
        $admin = $this->buatPengguna('Admin');
        $target = $this->buatPengguna('SAM');
        LogAktivitas::factory()->create(['id_pengguna' => $target->id_pengguna]);

        $this->from(route('admin.pengguna'))->actingAs($admin)
            ->delete(route('admin.pengguna.delete', $target))
            ->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('error', "Akun '{$target->nama}' sudah tercatat dalam riwayat aktivitas atau transaksi. Nonaktifkan akun agar riwayat tetap utuh.");

        $this->assertModelExists($target);
    }

    public function test_distributor_in_use_cannot_be_deleted(): void
    {
        $admin = $this->buatPengguna('Admin');
        $company = Distributor::factory()->create();
        $this->buatPengguna('Distributor', ['id_distributor' => $company->id_distributor]);

        $this->from(route('admin.distributor'))->actingAs($admin)
            ->delete(route('admin.distributor.delete', $company))
            ->assertRedirect(route('admin.distributor'))
            ->assertSessionHas('error');

        $this->assertModelExists($company);
    }

    public function test_product_in_use_cannot_be_deleted(): void
    {
        $admin = $this->buatPengguna('Admin');
        $product = Produk::factory()->create();
        SellOut::factory()->create(['id_produk' => $product->id_produk]);

        $this->from(route('admin.produk'))->actingAs($admin)
            ->delete(route('admin.produk.delete', $product))
            ->assertRedirect(route('admin.produk'))
            ->assertSessionHas('error');

        $this->assertModelExists($product);
    }

    public function test_new_user_creation_saves_the_account_and_activity_log(): void
    {
        $admin = $this->buatPengguna('Admin');
        $company = Distributor::factory()->create();

        $this->actingAs($admin)->post(route('admin.pengguna.store'), [
            'nama' => 'Distributor Baru',
            'email' => 'distributor-baru@example.test',
            'peran' => 'Distributor',
            'id_distributor' => $company->id_distributor,
            'kata_sandi' => 'rahasia123',
        ])->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('success');

        $user = Pengguna::query()->where('email', 'distributor-baru@example.test')->firstOrFail();
        $this->assertSame($company->id_distributor, $user->id_distributor);
        $this->assertTrue(Hash::check('rahasia123', $user->kata_sandi));
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna' => $admin->id_pengguna,
            'modul' => 'Kelola Pengguna',
            'aksi' => 'Tambah',
        ]);
    }

    public function test_admin_can_edit_the_same_distributor_account_without_changing_its_identity(): void
    {
        $admin = $this->buatPengguna('Admin');
        $company = Distributor::factory()->create();
        $account = $this->buatPengguna('Distributor', [
            'nama' => 'Pemegang Lama',
            'email' => 'pemegang-lama@example.test',
            'id_distributor' => $company->id_distributor,
        ]);
        $activity = LogAktivitas::factory()->create([
            'id_pengguna' => $account->id_pengguna,
        ]);

        $this->actingAs($admin)->get(route('admin.pengguna.edit', $account))
            ->assertOk()
            ->assertSee('Edit Pengguna')
            ->assertSee($company->nama)
            ->assertSee('Edit data pemegang yang sama');

        $this->put(route('admin.pengguna.update', $account), [
            'mode' => 'edit',
            'nama' => 'Pemegang Sama',
            'email' => 'pemegang-sama@example.test',
        ])->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('success');

        $account->refresh();
        $this->assertSame('Pemegang Sama', $account->nama);
        $this->assertSame('pemegang-sama@example.test', $account->email);
        $this->assertSame($company->id_distributor, $account->id_distributor);
        $this->assertTrue($account->status_aktif);
        $this->assertDatabaseHas('log_aktivitas', [
            'id_log' => $activity->id_log,
            'id_pengguna' => $account->id_pengguna,
        ]);
        $this->assertSame(1, Pengguna::query()->where('id_distributor', $company->id_distributor)->count());
    }

    public function test_distributor_replacement_archives_old_identity_and_creates_new_account(): void
    {
        $admin = $this->buatPengguna('Admin');
        $company = Distributor::factory()->create();
        $oldAccount = $this->buatPengguna('Distributor', [
            'id_distributor' => $company->id_distributor,
        ]);
        $oldActivity = LogAktivitas::factory()->create([
            'id_pengguna' => $oldAccount->id_pengguna,
        ]);

        $this->actingAs($admin)->get(route('admin.pengguna.edit', $oldAccount))
            ->assertOk()
            ->assertSee('Edit Pengguna')
            ->assertSee($company->nama)
            ->assertSee('Ganti pemegang (orang baru)');

        $this->actingAs($admin)->put(route('admin.pengguna.update', $oldAccount), [
            'mode' => 'ganti_pemegang',
            'nama' => 'Pemegang Baru',
            'email' => 'pemegang-baru@example.test',
            'kata_sandi' => 'password-baru-123',
            'kata_sandi_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('success');

        $oldAccount->refresh();
        $newAccount = Pengguna::query()->where('email', 'pemegang-baru@example.test')->firstOrFail();

        $this->assertFalse($oldAccount->status_aktif);
        $this->assertNull($oldAccount->id_distributor);
        $this->assertModelExists($oldAccount);
        $this->assertSame($company->id_distributor, $newAccount->id_distributor);
        $this->assertTrue($newAccount->status_aktif);
        $this->assertTrue(Hash::check('password-baru-123', $newAccount->kata_sandi));
        $this->assertDatabaseHas('log_aktivitas', [
            'id_log' => $oldActivity->id_log,
            'id_pengguna' => $oldAccount->id_pengguna,
        ]);
        $this->assertDatabaseHas('log_aktivitas', [
            'id_pengguna' => $admin->id_pengguna,
            'modul' => 'Kelola Pengguna',
            'aksi' => 'Ganti Akun',
        ]);

        $this->get(route('admin.pengguna'))
            ->assertOk()
            ->assertSee('Arsip')
            ->assertDontSee(route('admin.pengguna.edit', $oldAccount))
            ->assertDontSee(route('admin.pengguna.toggle', $oldAccount))
            ->assertDontSee(route('admin.pengguna.delete', $oldAccount));

        $this->from(route('admin.pengguna'))->post(route('admin.pengguna.toggle', $oldAccount))
            ->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('error', 'Akun distributor yang sudah diarsipkan tidak dapat diaktifkan kembali. Buat akun baru untuk pemegang berikutnya.');
        $this->assertFalse($oldAccount->fresh()->status_aktif);

        $this->delete(route('admin.pengguna.delete', $oldAccount))
            ->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('error');
        $this->assertModelExists($oldAccount);
    }

    public function test_non_distributor_account_cannot_be_replaced(): void
    {
        $admin = $this->buatPengguna('Admin');
        $target = $this->buatPengguna('SAM');

        $this->actingAs($admin)->put(route('admin.pengguna.update', $target), [
            'mode' => 'ganti_pemegang',
            'nama' => 'Pengguna Baru',
            'email' => 'pengguna-baru@example.test',
            'kata_sandi' => 'password-baru-123',
            'kata_sandi_confirmation' => 'password-baru-123',
        ])
            ->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('error', 'Akun ini tidak dapat diperbarui dengan pilihan tersebut.');

        $this->assertModelExists($target);
        $this->assertDatabaseMissing('pengguna', ['email' => 'pengguna-baru@example.test']);
    }

    public function test_replacement_validation_failure_leaves_the_existing_account_unchanged(): void
    {
        $admin = $this->buatPengguna('Admin');
        $company = Distributor::factory()->create();
        $oldAccount = $this->buatPengguna('Distributor', [
            'id_distributor' => $company->id_distributor,
        ]);

        $this->from(route('admin.pengguna.edit', $oldAccount))
            ->actingAs($admin)
            ->put(route('admin.pengguna.update', $oldAccount), [
                'mode' => 'ganti_pemegang',
                'nama' => 'Pemegang Baru',
                'email' => $admin->email,
                'kata_sandi' => 'password-baru-123',
                'kata_sandi_confirmation' => 'password-baru-123',
            ])->assertRedirect(route('admin.pengguna.edit', $oldAccount))
            ->assertSessionHasErrors('email');

        $oldAccount->refresh();
        $this->assertTrue($oldAccount->status_aktif);
        $this->assertSame($company->id_distributor, $oldAccount->id_distributor);
        $this->assertDatabaseMissing('pengguna', ['nama' => 'Pemegang Baru']);
    }

    public function test_deactivated_user_session_is_revoked_on_the_next_authenticated_request(): void
    {
        $company = Distributor::factory()->create();
        $user = $this->buatPengguna('Distributor', [
            'id_distributor' => $company->id_distributor,
            'status_aktif' => false,
        ]);

        $this->actingAs($user)->get(route('e-po.distributor.hitung-forecast'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.',
            ]);
    }

    public function test_administrator_cannot_deactivate_the_account_in_use(): void
    {
        $admin = $this->buatPengguna('Admin');

        $this->from(route('admin.pengguna'))->actingAs($admin)
            ->post(route('admin.pengguna.toggle', $admin))
            ->assertRedirect(route('admin.pengguna'))
            ->assertSessionHas('error', 'Anda tidak dapat menonaktifkan akun administrator yang sedang digunakan.');

        $this->assertTrue($admin->fresh()->status_aktif);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function buatPengguna(string $namaPeran, array $attributes = []): Pengguna
    {
        $role = Peran::factory()->create(['nama_peran' => $namaPeran]);

        return Pengguna::factory()->create([
            'id_peran' => $role->id_peran,
            ...$attributes,
        ]);
    }
}
