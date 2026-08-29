<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Kegiatan;
use App\Models\Kampanye;
use App\Models\Donasi;
use App\Models\Relawan;
use App\Models\Galeri;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'nama_lengkap' => 'Super Admin Karinakas',
            'email' => 'superadmin@karinakas.org',
            'username' => 'superadmin',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'jenis_kelamin' => 'Laki-laki',
            'tanggal_lahir' => '1980-01-01',
            'usia' => 44,
            'no_telepon' => '081234567890',
            'alamat' => 'Semarang, Jawa Tengah',
            'asal_paroki' => 'Paroki Katedral',
        ]);

        // Create Admin
        $admin = User::create([
            'nama_lengkap' => 'Admin Karinakas',
            'email' => 'admin@karinakas.org',
            'username' => 'admin',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'jenis_kelamin' => 'Perempuan',
            'tanggal_lahir' => '1990-05-15',
            'usia' => 34,
            'no_telepon' => '081234567891',
            'alamat' => 'Semarang, Jawa Tengah',
            'asal_paroki' => 'Paroki Santo Yusuf',
        ]);

        // Create Regular Users
        $users = [];
        for ($i = 1; $i <= 10; $i++) {
            $users[] = User::create([
                'nama_lengkap' => "Pengguna Test {$i}",
                'email' => "user{$i}@test.com",
                'username' => "user{$i}",
                'password' => Hash::make('password123'),
                'role' => 'user',
                'jenis_kelamin' => $i % 2 == 0 ? 'Laki-laki' : 'Perempuan',
                'tanggal_lahir' => '1995-01-01',
                'usia' => 29,
                'no_telepon' => '08123456789' . $i,
                'alamat' => 'Semarang, Jawa Tengah',
                'asal_paroki' => $i % 3 == 0 ? 'Paroki Santo Petrus' : null,
            ]);
        }

        // Create Kegiatan
        $kegiatan1 = Kegiatan::create([
            'judul' => 'Bimbingan Belajar Anak Daerah Terpencil',
            'deskripsi' => 'Memberikan bimbingan belajar gratis untuk anak-anak kurang mampu di daerah terpencil dengan pendampingan intensif dari relawan.',
            'lokasi' => 'Ungaran, Jawa Tengah',
            'kategori' => 'pendidikan',
            'gambar_url' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=500&h=280&fit=crop',
            'tanggal_mulai' => now()->addDays(7),
            'tanggal_selesai' => now()->addDays(10),
            'status' => 'aktif',
            'butuh_relawan' => true,
            'butuh_donasi' => false,
            'kuota_relawan' => 20,
            'jumlah_relawan_terdaftar' => 0,
        ]);

        $kegiatan2 = Kegiatan::create([
            'judul' => 'Bakti Sosial di Panti Asuhan Kasih Harapan',
            'deskripsi' => 'Kegiatan bakti sosial memberikan bantuan sembako dan mengadakan aktivitas bersama anak-anak panti asuhan dengan penuh kasih.',
            'lokasi' => 'Salatiga, Jawa Tengah',
            'kategori' => 'sosial',
            'gambar_url' => 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?w=500&h=280&fit=crop',
            'tanggal_mulai' => now()->addDays(14),
            'tanggal_selesai' => now()->addDays(14),
            'status' => 'aktif',
            'butuh_relawan' => true,
            'butuh_donasi' => true,
            'kuota_relawan' => 25,
            'jumlah_relawan_terdaftar' => 0,
        ]);

        $kegiatan3 = Kegiatan::create([
            'judul' => 'Pemeriksaan Kesehatan Gratis untuk Masyarakat',
            'deskripsi' => 'Memberikan pemeriksaan kesehatan gratis untuk masyarakat kurang mampu dengan didampingi tenaga medis profesional.',
            'lokasi' => 'Semarang, Jawa Tengah',
            'kategori' => 'kesehatan',
            'gambar_url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=500&h=280&fit=crop',
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_selesai' => now()->subDays(10),
            'status' => 'selesai',
            'butuh_relawan' => false,
            'butuh_donasi' => true,
            'kuota_relawan' => 15,
            'jumlah_relawan_terdaftar' => 15,
        ]);

        // Create Kampanye / Berita
        Kampanye::create([
            'judul' => 'Pentingnya Pendidikan untuk Anak Bangsa',
            'konten' => "Pendidikan adalah hak setiap anak.\n\nMari bersama-sama kita wujudkan pendidikan yang merata untuk seluruh anak Indonesia.",
            'ringkasan' => 'Pendidikan adalah hak setiap anak. Mari wujudkan pendidikan merata.',
            'gambar_url' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=700&h=420&fit=crop',
            'penulis' => 'Divisi Pendidikan',
            'penulis_avatar' => 'https://randomuser.me/api/portraits/women/44.jpg',
            'tags' => ['pendidikan', 'anak', 'beasiswa'],
            'waktu_baca' => '3 menit',
            'kategori' => 'pendidikan',
            'status' => 'published',
            'tanggal_publish' => now()->subDays(5),
        ]);

        Kampanye::create([
            'judul' => 'Gerakan Peduli Kesehatan Masyarakat',
            'konten' => "Kesehatan adalah investasi.\n\nMari jaga kesehatan bersama dengan pola hidup sehat dan pemeriksaan rutin.",
            'ringkasan' => 'Kesehatan adalah investasi. Jaga kesehatan dengan pola hidup sehat.',
            'gambar_url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=700&h=420&fit=crop',
            'penulis' => 'Tim Kesehatan',
            'penulis_avatar' => 'https://randomuser.me/api/portraits/men/32.jpg',
            'tags' => ['kesehatan', 'masyarakat'],
            'waktu_baca' => '4 menit',
            'kategori' => 'kesehatan',
            'status' => 'published',
            'tanggal_publish' => now()->subDays(3),
        ]);

        // Create Relawan
        $jenisList = ['Lansia', 'Kebencanaan'];
        $minatList = ['Kegiatan Sosial', 'Layanan Kesehatan', 'Pendidikan & Pelatihan'];
        foreach (array_slice($users, 0, 5) as $index => $user) {
            Relawan::create([
                'user_id' => $user->id,
                'kegiatan_id' => $kegiatan1->id,
                'jenis' => $jenisList[$index % 2],
                'minat' => $minatList[$index % 3],
                'kemampuan_diri' => 'Mengajar, pendampingan anak',
                'motivasi' => 'Saya ingin berkontribusi untuk pendidikan anak-anak Indonesia.',
                'nomor_ktp' => '33740123456789' . $index,
                'kebutuhan_khusus' => $index == 2 ? 'Memerlukan bantuan pendengaran (alat bantu dengar)' : null,
                'status' => $index < 2 ? 'pending' : 'diterima',
            ]);
        }

        // Create Donasi
        foreach (array_slice($users, 5, 5) as $index => $user) {
            Donasi::create([
                'user_id' => $user->id,
                'kegiatan_id' => $index % 2 == 0 ? $kegiatan2->id : null,
                'jumlah' => rand(50000, 500000),
                'metode_pembayaran' => ['Transfer BCA', 'QRIS', 'Virtual Account'][rand(0, 2)],
                'status_pembayaran' => 'berhasil',
                'tujuan_donasi' => $index % 2 == 0 ? 'Bakti Sosial di Panti Asuhan Kasih Harapan' : 'Donasi Umum',
                'keterangan' => 'Semoga bermanfaat',
                'biaya_transfer' => [0, 700, 4000][rand(0, 2)],
                'kode_transaksi' => 'TRX-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'rekening_pengirim' => '1234****' . rand(100, 999),
                'anonim' => $index == 3,
                'notifikasi_terkirim' => true,
                'tanggal_donasi' => now()->subDays(rand(1, 30)),
            ]);
        }

        // Create Galeri
        Galeri::create([
            'kegiatan_id' => $kegiatan3->id,
            'judul' => 'Pemeriksaan Kesehatan di Kampung Jawa',
            'deskripsi' => 'Dokumentasi kegiatan pemeriksaan kesehatan gratis',
            'gambar_url' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&h=260&fit=crop',
            'kategori' => 'kesehatan',
            'tanggal' => now()->subDays(10),
            'tipe' => 'foto',
        ]);

        Galeri::create([
            'kegiatan_id' => $kegiatan3->id,
            'judul' => 'Relawan Memberikan Edukasi Kesehatan',
            'deskripsi' => 'Relawan menjelaskan pentingnya pola hidup sehat',
            'gambar_url' => 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?w=400&h=260&fit=crop',
            'kategori' => 'relawan',
            'tanggal' => now()->subDays(10),
            'tipe' => 'foto',
        ]);
    }
}