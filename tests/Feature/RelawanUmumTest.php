<?php

namespace Tests\Feature;

use App\Mail\RelawanDiterima;
use App\Models\Kegiatan;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pendaftaran relawan UMUM (tanpa kegiatan): hanya satu per pengguna, pendaftaran per kegiatan
 * tetap berjalan, dan surel penerimaan tidak memuat kalimat "untuk kegiatan -".
 * Data rekaan; basis data = SQLite di memori, surel = Mail::fake().
 */
class RelawanUmumTest extends TestCase
{
    use RefreshDatabase;

    private const BODY = ['jenis' => 'Lansia', 'nomor_ktp' => '3404010101010001', 'motivasi' => 'uji'];

    private function akun(string $role, string $nama): User
    {
        return User::create([
            'nama_lengkap' => $nama, 'email' => $role . '.rekaan@contoh.test', 'username' => $role . '_rekaan',
            'password' => 'rahasia-rekaan-123', 'role' => $role,
        ]);
    }

    private function kegiatan(string $judul, string $status = 'aktif'): Kegiatan
    {
        return Kegiatan::create([
            'judul' => $judul, 'deskripsi' => 'uji', 'lokasi' => 'Sleman', 'kategori' => 'sosial',
            'tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => '2026-11-01', 'status' => $status, 'butuh_relawan' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
    }

    public function test_pendaftaran_umum_pertama_diterima(): void
    {
        Sanctum::actingAs($this->akun('user', 'Bu Relawan'));

        $this->postJson('/api/v1/relawan', self::BODY)->assertCreated();
        $this->assertSame(1, Relawan::whereNull('kegiatan_id')->count());
    }

    public function test_pendaftaran_umum_kedua_oleh_pengguna_yang_sama_ditolak(): void
    {
        Sanctum::actingAs($this->akun('user', 'Bu Relawan'));

        $this->postJson('/api/v1/relawan', self::BODY)->assertCreated();
        $this->postJson('/api/v1/relawan', self::BODY)
            ->assertStatus(400)
            ->assertJsonPath('message', 'Anda sudah memiliki pendaftaran relawan umum');

        $this->assertSame(1, Relawan::whereNull('kegiatan_id')->count());
    }

    public function test_pengguna_lain_tetap_boleh_mendaftar_umum(): void
    {
        Sanctum::actingAs($this->akun('user', 'Bu Relawan'));
        $this->postJson('/api/v1/relawan', self::BODY)->assertCreated();

        $lain = User::create(['nama_lengkap' => 'Pak Lain', 'email' => 'lain@contoh.test', 'username' => 'lain', 'password' => 'rahasia-rekaan-123', 'role' => 'user']);
        Sanctum::actingAs($lain);
        $this->postJson('/api/v1/relawan', self::BODY)->assertCreated();

        $this->assertSame(2, Relawan::whereNull('kegiatan_id')->count());
    }

    public function test_pendaftaran_umum_tidak_menghalangi_pendaftaran_ke_kegiatan(): void
    {
        $k = $this->kegiatan('Bakti Sosial Uji');
        Sanctum::actingAs($this->akun('user', 'Bu Relawan'));

        $this->postJson('/api/v1/relawan', self::BODY)->assertCreated();
        $this->postJson('/api/v1/relawan', self::BODY + ['kegiatan_id' => $k->id])->assertCreated();
        // duplikat pada kegiatan yang sama tetap ditolak
        $this->postJson('/api/v1/relawan', self::BODY + ['kegiatan_id' => $k->id])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Anda sudah terdaftar di kegiatan ini');
    }

    public function test_kegiatan_tidak_aktif_tetap_ditolak(): void
    {
        $k = $this->kegiatan('Kegiatan Selesai', 'selesai');
        Sanctum::actingAs($this->akun('user', 'Bu Relawan'));

        $this->postJson('/api/v1/relawan', self::BODY + ['kegiatan_id' => $k->id])->assertStatus(400);
    }

    public function test_surel_relawan_umum_tidak_memuat_kalimat_untuk_kegiatan_strip(): void
    {
        $pengguna = $this->akun('user', 'Bu Relawan');
        Sanctum::actingAs($pengguna);
        $id = $this->postJson('/api/v1/relawan', self::BODY)->assertCreated()->json('data.id');

        Sanctum::actingAs($this->akun('admin', 'Admin Rekaan'));
        $this->patchJson('/api/v1/admin/relawan/' . $id . '/status', ['status' => 'diterima'])->assertOk();

        Mail::assertSent(RelawanDiterima::class, function ($surel) {
            $teks = $surel->render();
            return str_contains($teks, 'untuk kegiatan umum Karinakas')
                && !str_contains($teks, 'untuk kegiatan -');
        });
    }

    public function test_surel_relawan_kegiatan_tetap_memuat_judul_kegiatan(): void
    {
        $k = $this->kegiatan('Bakti Sosial Uji');
        Sanctum::actingAs($this->akun('user', 'Bu Relawan'));
        $id = $this->postJson('/api/v1/relawan', self::BODY + ['kegiatan_id' => $k->id])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->akun('admin', 'Admin Rekaan'));
        $this->patchJson('/api/v1/admin/relawan/' . $id . '/status', ['status' => 'diterima'])->assertOk();

        Mail::assertSent(RelawanDiterima::class, fn ($surel) => str_contains($surel->render(), 'untuk kegiatan Bakti Sosial Uji'));
    }
}
