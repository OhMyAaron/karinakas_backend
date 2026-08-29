<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();        // penerima terdaftar (boleh null)
            $table->string('tipe')->default('email');    // email | whatsapp | system
            $table->string('penerima');                  // email / nomor HP
            $table->string('judul');
            $table->text('isi')->nullable();
            $table->string('trigger')->nullable();       // donasi_verified | relawan_approved | ...
            $table->string('status')->default('terkirim');
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_logs');
    }
};
