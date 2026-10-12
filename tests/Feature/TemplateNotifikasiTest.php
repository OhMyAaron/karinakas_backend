<?php

namespace Tests\Feature;

use App\Mail\DonasiDiverifikasi;
use App\Mail\RelawanDiterima;
use App\Models\Donasi;
use App\Models\Kegiatan;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Templat notifikasi yang disunting admin harus benar-benar dipakai pada surel yang terkirim.
 * Semua data di bawah rekaan; basis data = SQLite di memori, surel = Mail::fake(), disk = Storage::fake().
 */
class TemplateNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function akun(string $role, string $nama): User
    {
        return User::create([
            'nama_lengkap' => $nama,
            'email' => $role . '.rekaan@contoh.test',
            'username' => $role . '_rekaan',
            'password' => 'rahasia-rekaan-123',
            'role' => $role,
        ]);
    }

    private function kegiatan(): Kegiatan
    {
        return Kegiatan::create([
            'judul' => 'Bakti Sosial Uji', 'deskripsi' => 'uji', 'lokasi' => 'Sleman',
            'kategori' => 'sosial', 'tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => '2026-11-01',
            'status' => 'aktif',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_templat_bawaan_dikembalikan_bila_belum_pernah_disunting(): void
    {
        Sanctum::actingAs($this->akun('admin', 'Admin Rekaan'));

        $this->getJson('/api/v1/admin/notifikasi/template')
            ->assertOk()
            ->assertJsonPath('data.relawan.judul', 'Selamat Datang Relawan Karinakas')
            ->assertJsonPath('data.donatur.judul', 'Terima Kasih atas Donasi Anda');
    }

    public function test_pengguna_biasa_tidak_boleh_membaca_atau_mengubah_templat(): void
    {
        Sanctum::actingAs($this->akun('user', 'Donatur Rekaan'));

        $this->getJson('/api/v1/admin/notifikasi/template')->assertForbidden();
        $this->putJson('/api/v1/admin/notifikasi/template', [])->assertForbidden();
    }

    public function test_admin_dan_super_admin_dapat_menyimpan_templat(): void
    {
        foreach (['admin', 'super_admin'] as $peran) {
            Sanctum::actingAs($this->akun($peran, 'Pengelola ' . $peran));

            $this->putJson('/api/v1/admin/notifikasi/template', [
                'relawan' => ['judul' => 'Judul R ' . $peran, 'isi' => 'Isi R [nama]'],
                'donatur' => ['judul' => 'Judul D ' . $peran, 'isi' => 'Isi D [nama]'],
            ])->assertOk();

            $this->getJson('/api/v1/admin/notifikasi/template')
                ->assertJsonPath('data.relawan.judul', 'Judul R ' . $peran)
                ->assertJsonPath('data.donatur.isi', 'Isi D [nama]');
        }
    }

    public function test_templat_kosong_ditolak(): void
    {
        Sanctum::actingAs($this->akun('admin', 'Admin Rekaan'));

        $this->putJson('/api/v1/admin/notifikasi/template', [
            'relawan' => ['judul' => '', 'isi' => 'x'],
            'donatur' => ['judul' => 'x', 'isi' => 'x'],
        ])->assertStatus(422);
    }

    public function test_surel_donasi_memakai_templat_yang_disunting(): void
    {
        Mail::fake();

        $super = $this->akun('super_admin', 'Super Rekaan');
        $donatur = $this->akun('user', 'Bu Donatur');
        $donasi = Donasi::create([
            'user_id' => $donatur->id, 'kegiatan_id' => $this->kegiatan()->id,
            'jumlah' => 150000, 'metode_pembayaran' => 'QRIS', 'status_pembayaran' => 'pending',
            'tujuan_donasi' => 'Donasi Umum',
        ]);

        Sanctum::actingAs($super);
        $this->putJson('/api/v1/admin/notifikasi/template', [
            'relawan' => ['judul' => 'R', 'isi' => 'R'],
            'donatur' => [
                'judul' => 'Matur Nuwun [nama]',
                'isi'   => "Kiriman [nominal] untuk [kegiatan] sudah kami terima.\n<b>tebal</b>",
            ],
        ])->assertOk();

        $this->patchJson('/api/v1/admin/donasi/' . $donasi->id . '/status', ['status_pembayaran' => 'berhasil'])
            ->assertOk();

        Mail::assertSent(DonasiDiverifikasi::class, function ($surel) {
            $html = $surel->render();
            return $surel->hasTo('user.rekaan@contoh.test')
                && $surel->hasSubject('Matur Nuwun Bu Donatur')
                && str_contains($html, 'Kiriman Rp 150.000 untuk Bakti Sosial Uji sudah kami terima.')
                && str_contains($html, '&lt;b&gt;tebal&lt;/b&gt;');   // HTML dari templat tidak boleh dijalankan
        });

        // Judul pada riwayat notifikasi admin ikut mencerminkan templat
        $this->assertDatabaseHas('notifikasi_logs', [
            'trigger' => 'donasi_verified',
            'judul' => 'Matur Nuwun Bu Donatur',
            'status' => 'terkirim',
        ]);
    }

    public function test_surel_relawan_memakai_templat_yang_disunting(): void
    {
        Mail::fake();

        $admin = $this->akun('admin', 'Admin Rekaan');
        $relawanUser = $this->akun('user', 'Pak Relawan');
        $relawan = Relawan::create([
            'user_id' => $relawanUser->id, 'kegiatan_id' => $this->kegiatan()->id,
            'jenis' => 'Lansia', 'nomor_ktp' => '1234567890123456',
            'motivasi' => 'ingin membantu', 'kebutuhan_khusus' => 'tidak ada', 'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);
        $this->putJson('/api/v1/admin/notifikasi/template', [
            'relawan' => ['judul' => 'Selamat bergabung, [nama]', 'isi' => 'Anda terpilih sebagai relawan [jenis] di [kegiatan].'],
            'donatur' => ['judul' => 'D', 'isi' => 'D'],
        ])->assertOk();

        $this->patchJson('/api/v1/admin/relawan/' . $relawan->id . '/status', ['status' => 'diterima'])
            ->assertOk();

        Mail::assertSent(RelawanDiterima::class, function ($surel) {
            $html = $surel->render();   // render() menjalankan build() sehingga subjek terisi
            return $surel->hasTo('user.rekaan@contoh.test')
                && $surel->hasSubject('Selamat bergabung, Pak Relawan')
                && str_contains($html, 'Anda terpilih sebagai relawan Lansia di Bakti Sosial Uji.');
        });

        $this->assertDatabaseHas('notifikasi_logs', [
            'trigger' => 'relawan_approved',
            'judul' => 'Selamat bergabung, Pak Relawan',
        ]);
    }

    public function test_tanpa_penyuntingan_surel_memakai_templat_bawaan(): void
    {
        Mail::fake();

        $donatur = $this->akun('user', 'Bu Donatur');
        $donasi = Donasi::create([
            'user_id' => $donatur->id, 'kegiatan_id' => null,
            'jumlah' => 50000, 'metode_pembayaran' => 'QRIS', 'status_pembayaran' => 'pending',
            'tujuan_donasi' => 'Donasi Umum',
        ]);

        Sanctum::actingAs($this->akun('super_admin', 'Super Rekaan'));
        $this->patchJson('/api/v1/admin/donasi/' . $donasi->id . '/status', ['status_pembayaran' => 'berhasil'])
            ->assertOk();

        Mail::assertSent(DonasiDiverifikasi::class, function ($surel) {
            $html = $surel->render();   // render() menjalankan build() sehingga subjek terisi
            return $surel->hasSubject('Terima Kasih atas Donasi Anda')
                && str_contains($html, 'Halo Bu Donatur');
        });
    }
}
