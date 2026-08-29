<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donasi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('kegiatan_id')->nullable(); // Nullable untuk donasi umum
            $table->decimal('jumlah', 15, 2);                       // nominal (tanpa batas)
            $table->string('metode_pembayaran', 50);
            $table->enum('status_pembayaran', ['pending', 'berhasil', 'gagal'])->default('pending');
            $table->text('keterangan')->nullable();                 // pesan dukungan
            $table->text('tujuan_donasi'); // Field baru dari revisi
            // Detail pembayaran — hanya diakses Super Admin (sinkron frontend)
            $table->decimal('biaya_transfer', 15, 2)->default(0);   // fee
            $table->string('kode_transaksi', 100)->nullable();      // kode
            $table->string('rekening_pengirim', 150)->nullable();   // rekening
            $table->boolean('anonim')->default(false);              // tampil anonim
            $table->boolean('notifikasi_terkirim')->default(false); // Field baru dari revisi
            $table->timestamp('tanggal_donasi')->useCurrent();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('kegiatan_id')->references('id')->on('kegiatan')->onDelete('set null');
            
            // Indexes
            $table->index('user_id');
            $table->index('kegiatan_id');
            $table->index('status_pembayaran');
            $table->index('tanggal_donasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donasi');
    }
};