<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampanye', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('judul', 200);
            $table->text('konten'); // Isi artikel kampanye/berita
            $table->text('ringkasan')->nullable();              // excerpt (sinkron frontend)
            $table->string('gambar_url', 255)->nullable();
            $table->string('penulis', 100)->nullable();         // author (sinkron frontend)
            $table->string('penulis_avatar', 255)->nullable();  // authorAvatar
            $table->json('tags')->nullable();                   // tags[] (sinkron frontend)
            $table->string('waktu_baca', 20)->nullable();       // readTime mis. "3 menit"
            $table->enum('kategori', ['sosial', 'pendidikan', 'kesehatan', 'bantuan', 'kegiatan', 'pengumuman', 'lainnya'])->default('sosial'); // sinkron frontend
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('tanggal_publish')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('kategori');
            $table->index('status');
            $table->index('tanggal_publish');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kampanye');
    }
};