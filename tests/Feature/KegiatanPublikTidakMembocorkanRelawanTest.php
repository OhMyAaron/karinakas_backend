<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rute publik /kegiatan dan /kegiatan/{id} tidak boleh mengirim data pribadi relawan
 * (nomor KTP, telepon, surel, alamat, motivasi, dst.) kepada pengunjung tanpa login.
 * Data di bawah ini sepenuhnya rekaan; basis data pengujian = SQLite di memori (phpunit.xml).
 */
class KegiatanPublikTidakMembocorkanRelawanTest extends TestCase
{
    use RefreshDatabase;

    private const KTP     = '9999000011112222';
    private const TELEPON = '08990001111';
    private const SUREL   = 'relawan.rekaan@contoh.test';
    private const MOTIVASI = 'motivasi-rahasia-rekaan';

    private function siapkanData(): Kegiatan
    {
        $kegiatan = Kegiatan::create([
            'judul' => 'Bakti Sosial Uji',
            'deskripsi' => 'Kegiatan uji',
            'lokasi' => 'Sleman',
            'kategori' => 'sosial',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-01',
            'status' => 'aktif',
            'butuh_relawan' => true,
            'kuota_relawan' => 10,
        ]);

        $user = User::create([
            'nama_lengkap' => 'Relawan Rekaan',
            'email' => self::SUREL,
            'username' => 'relawan_rekaan',
            'password' => 'rahasia-rekaan-123',
            'role' => 'user',
            'no_telepon' => self::TELEPON,
            'alamat' => 'Jalan Rekaan 1',
            'asal_paroki' => 'Paroki Rekaan',
        ]);

        Relawan::create([
            'user_id' => $user->id,
            'kegiatan_id' => $kegiatan->id,
            'jenis' => 'Lansia',
            'nomor_ktp' => self::KTP,
            'motivasi' => self::MOTIVASI,
            'kebutuhan_khusus' => 'tidak ada',
            'status' => 'diterima',
        ]);

        return $kegiatan;
    }

    private function pastikanTidakBocor(string $isiRespons): void
    {
        foreach ([self::KTP, self::TELEPON, self::SUREL, self::MOTIVASI, 'Jalan Rekaan 1', 'Paroki Rekaan'] as $rahasia) {
            $this->assertStringNotContainsString($rahasia, $isiRespons, "Data pribadi relawan bocor pada respons publik: {$rahasia}");
        }
    }

    public function test_daftar_kegiatan_publik_tidak_memuat_data_relawan(): void
    {
        $this->siapkanData();

        $respons = $this->getJson('/api/v1/kegiatan?per_page=100');

        $respons->assertOk();
        $respons->assertJsonPath('data.data.0.judul', 'Bakti Sosial Uji');
        $this->assertArrayNotHasKey('relawan', $respons->json('data.data.0'));
        $this->pastikanTidakBocor($respons->getContent());
    }

    public function test_detail_kegiatan_publik_tidak_memuat_data_relawan(): void
    {
        $kegiatan = $this->siapkanData();

        $respons = $this->getJson('/api/v1/kegiatan/' . $kegiatan->id);

        $respons->assertOk();
        $respons->assertJsonPath('data.judul', 'Bakti Sosial Uji');
        $this->assertArrayNotHasKey('relawan', $respons->json('data'));
        $this->pastikanTidakBocor($respons->getContent());
    }

    public function test_penghitung_relawan_terdaftar_tetap_tersedia_untuk_publik(): void
    {
        $this->siapkanData();

        $respons = $this->getJson('/api/v1/kegiatan');

        $respons->assertOk();
        // Frontend memakai kolom ini untuk "terisi/kuota"; harus tetap ada setelah relasi relawan dihapus.
        $this->assertArrayHasKey('jumlah_relawan_terdaftar', $respons->json('data.data.0'));
        $this->assertSame(1, (int) $respons->json('data.data.0.jumlah_relawan_terdaftar'));
    }

    public function test_kegiatan_tersedia_untuk_relawan_tidak_memuat_data_relawan(): void
    {
        $this->siapkanData();

        $respons = $this->getJson('/api/v1/kegiatan/available/volunteer');

        $respons->assertOk();
        $this->pastikanTidakBocor($respons->getContent());
    }
}
