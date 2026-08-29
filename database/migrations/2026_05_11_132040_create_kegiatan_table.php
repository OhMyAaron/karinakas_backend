<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul', 200);
            $table->text('deskripsi');
            $table->text('lokasi');
            $table->enum('kategori', ['bantuan', 'pendidikan', 'kesehatan', 'sosial', 'lainnya'])->default('sosial'); // sinkron frontend
            $table->string('gambar_url', 255)->nullable();      // sinkron frontend (img)
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->boolean('butuh_relawan')->default(false);   // sinkron frontend
            $table->boolean('butuh_donasi')->default(false);    // sinkron frontend
            $table->integer('kuota_relawan')->default(0);
            $table->integer('jumlah_relawan_terdaftar')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('kategori');
            $table->index('tanggal_mulai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};