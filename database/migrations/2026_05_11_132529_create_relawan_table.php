<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relawan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('kegiatan_id')->nullable(); // nullable: pendaftaran relawan umum (tidak terikat kegiatan)
            $table->enum('jenis', ['Lansia', 'Kebencanaan'])->nullable();  // jenis relawan (sinkron frontend)
            $table->string('minat', 100)->nullable();                      // bidang minat
            $table->text('kemampuan_diri')->nullable();                    // skill untuk divisi (sinkron frontend)
            $table->text('motivasi')->nullable();
            $table->string('nomor_ktp', 20)->nullable(); // Field baru dari revisi (16 digit)
            $table->text('kebutuhan_khusus')->nullable(); // keterbatasan diri / accessibility
            $table->enum('status', ['pending', 'diterima', 'ditolak'])->default('pending');
            $table->timestamp('tanggal_daftar')->useCurrent();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('kegiatan_id')->references('id')->on('kegiatan')->onDelete('cascade');
            
            // Indexes
            $table->index('user_id');
            $table->index('kegiatan_id');
            $table->index('status');
            $table->index('tanggal_daftar');
            
            // Unique constraint: user tidak bisa daftar 2x di kegiatan yang sama
            $table->unique(['user_id', 'kegiatan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relawan');
    }
};