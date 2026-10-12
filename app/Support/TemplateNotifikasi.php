<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Templat surel notifikasi yang dapat disunting Admin / Super Admin.
 *
 * Disimpan sebagai satu berkas JSON di storage (tanpa tabel baru), sehingga tidak ikut
 * tertimpa saat deploy lewat git. Bila berkas belum ada atau rusak, templat bawaan dipakai.
 * Pengganti variabel ditulis dengan kurung siku, mis. [nama].
 */
class TemplateNotifikasi
{
    private const BERKAS = 'notifikasi_template.json';

    /** Kunci templat: surel relawan diterima & surel donasi terverifikasi. */
    public const KUNCI = ['relawan', 'donatur'];

    public static function bawaan(): array
    {
        return [
            'relawan' => [
                'judul' => 'Selamat Datang Relawan Karinakas',
                'isi'   => "Halo [nama],\n\nSelamat! Pendaftaran Anda sebagai relawan [jenis] untuk kegiatan [kegiatan] telah diterima oleh tim Karinakas.\n\nTerima kasih atas kesediaan Anda berbagi waktu dan tenaga. Kami akan menghubungi Anda untuk informasi kegiatan selanjutnya. Sampai jumpa dalam pelayanan bersama!\n\nBerkah Dalem,\nTim Yayasan Karinakas",
            ],
            'donatur' => [
                'judul' => 'Terima Kasih atas Donasi Anda',
                'isi'   => "Halo [nama],\n\nDonasi Anda sebesar [nominal] untuk kegiatan [kegiatan] telah diverifikasi oleh tim Karinakas pada [tanggal]. Terima kasih atas kebaikan hati Anda.\n\nDonasi Anda akan digunakan untuk mendukung kegiatan sosial Yayasan Karinakas.\n\nBerkah Dalem,\nTim Yayasan Karinakas",
            ],
        ];
    }

    /** Templat yang berlaku sekarang: isian tersimpan, dilengkapi bawaan bila ada yang kosong. */
    public static function semua(): array
    {
        $hasil = self::bawaan();
        $tersimpan = self::baca();

        foreach (self::KUNCI as $kunci) {
            foreach (['judul', 'isi'] as $bidang) {
                $nilai = $tersimpan[$kunci][$bidang] ?? null;
                if (is_string($nilai) && trim($nilai) !== '') {
                    $hasil[$kunci][$bidang] = $nilai;
                }
            }
        }

        return $hasil;
    }

    public static function simpan(array $data): void
    {
        $bersih = [];
        foreach (self::KUNCI as $kunci) {
            $bersih[$kunci] = [
                'judul' => (string) ($data[$kunci]['judul'] ?? ''),
                'isi'   => (string) ($data[$kunci]['isi'] ?? ''),
            ];
        }

        Storage::disk('local')->put(self::BERKAS, json_encode($bersih, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Isi templat dengan nilai nyata. Variabel yang tak dikenal dibiarkan apa adanya.
     *
     * @return array{judul: string, isi: string}
     */
    public static function render(string $kunci, array $variabel): array
    {
        $templat = self::semua()[$kunci];

        $pengganti = [];
        foreach ($variabel as $nama => $nilai) {
            $pengganti['[' . $nama . ']'] = (string) ($nilai ?? '');
        }

        return [
            'judul' => strtr($templat['judul'], $pengganti),
            'isi'   => strtr($templat['isi'], $pengganti),
        ];
    }

    private static function baca(): array
    {
        try {
            $disk = Storage::disk('local');
            if (!$disk->exists(self::BERKAS)) {
                return [];
            }
            $data = json_decode($disk->get(self::BERKAS), true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
