<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galeri', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kegiatan_id')->nullable(); // nullable: foto galeri bisa berdiri sendiri (sinkron frontend)
            $table->string('judul', 200);
            $table->text('deskripsi')->nullable();
            $table->string('gambar_url', 255);
            $table->enum('kategori', ['sosial', 'pendidikan', 'kesehatan', 'bantuan', 'relawan', 'lainnya'])->default('sosial'); // sinkron frontend
            $table->date('tanggal')->nullable();     // tanggal dokumentasi (sinkron frontend)
            $table->enum('tipe', ['foto', 'video'])->default('foto');
            $table->timestamps();

            // Foreign key
            $table->foreign('kegiatan_id')->references('id')->on('kegiatan')->onDelete('set null');

            // Indexes
            $table->index('kegiatan_id');
            $table->index('kategori');
            $table->index('tipe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeri');
    }
};