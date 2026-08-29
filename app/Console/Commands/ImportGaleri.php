<?php

namespace App\Console\Commands;

use App\Models\Galeri;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportGaleri extends Command
{
    /**
     * Contoh:
     *   php artisan galeri:import import_galeri --fresh
     *   php artisan galeri:import import_galeri --kategori=kesehatan
     *
     * Struktur folder sumber (tiap subfolder = judul kegiatan):
     *   import_galeri/
     *     Home Visit/foto1.jpg, foto2.jpg
     *     Jambore/a.png ...
     */
    protected $signature = 'galeri:import
        {source : Path folder sumber (relatif ke root project atau absolut)}
        {--fresh : Hapus semua galeri lama + file di storage/galeri sebelum import}
        {--kategori=sosial : Kategori default untuk semua foto}
        {--tanggal= : Tanggal dokumentasi default (YYYY-MM-DD), opsional}';

    protected $description = 'Import banyak foto galeri dari folder (tiap subfolder = judul kegiatan)';

    private array $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private array $allowedKategori = ['sosial', 'pendidikan', 'kesehatan', 'bantuan', 'relawan', 'lainnya'];

    public function handle(): int
    {
        $source = $this->argument('source');
        if (!Str::startsWith($source, ['/', '\\']) && !preg_match('/^[A-Za-z]:/', $source)) {
            $source = base_path($source);
        }

        if (!is_dir($source)) {
            $this->error("Folder sumber tidak ditemukan: {$source}");
            return self::FAILURE;
        }

        $kategori = $this->option('kategori');
        if (!in_array($kategori, $this->allowedKategori, true)) {
            $this->error("Kategori '{$kategori}' tidak valid. Pilih: " . implode(', ', $this->allowedKategori));
            return self::FAILURE;
        }

        $tanggal = $this->option('tanggal') ?: null;

        // Kumpulkan subfolder = judul
        $subDirs = array_filter(glob($source . DIRECTORY_SEPARATOR . '*'), 'is_dir');
        if (empty($subDirs)) {
            $this->error("Tidak ada subfolder di dalam '{$source}'. Buat 1 subfolder per kegiatan lalu isi fotonya.");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $deleted = Galeri::query()->delete();
            Storage::disk('public')->deleteDirectory('galeri');
            $this->warn("--fresh: {$deleted} baris galeri lama dihapus + folder storage/galeri dibersihkan.");
        }

        $totalFoto = 0;
        $totalKegiatan = 0;

        foreach ($subDirs as $dir) {
            $judul = basename($dir);
            $files = array_filter(
                glob($dir . DIRECTORY_SEPARATOR . '*'),
                fn ($f) => is_file($f) && in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $this->allowedExt, true)
            );

            if (empty($files)) {
                $this->line("  <fg=yellow>lewati</> \"{$judul}\" (tidak ada gambar)");
                continue;
            }

            sort($files); // urut nama file biar rapi
            $slug = Str::slug($judul) ?: 'galeri';
            $count = 0;

            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $stored = 'galeri/' . $slug . '/' . $slug . '-' . Str::random(8) . '.' . $ext;

                Storage::disk('public')->put($stored, file_get_contents($file));

                Galeri::create([
                    'kegiatan_id' => null,
                    'judul'       => $judul,
                    'deskripsi'   => null,
                    'gambar_url'  => $stored, // path relatif; frontend menambah base via fileUrl()
                    'kategori'    => $kategori,
                    'tanggal'     => $tanggal,
                    'tipe'        => 'foto',
                ]);
                $count++;
            }

            $totalFoto += $count;
            $totalKegiatan++;
            $this->info("  ✓ \"{$judul}\" — {$count} foto");
        }

        $this->newLine();
        $this->info("Selesai: {$totalFoto} foto dari {$totalKegiatan} kegiatan diimport. Total galeri sekarang: " . Galeri::count());

        return self::SUCCESS;
    }
}
