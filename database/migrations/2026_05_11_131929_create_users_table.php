<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_lengkap', 100);
            $table->string('email', 100)->unique();
            $table->string('username', 50)->unique()->nullable(); // opsional: frontend login pakai email
            $table->string('password');
            $table->enum('role', ['user', 'admin', 'super_admin'])->default('user');
            $table->string('jenis_kelamin', 20)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->integer('usia')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota', 100)->nullable();      // sinkron profil frontend
            $table->text('tentang')->nullable();          // sinkron profil frontend
            $table->string('asal_paroki', 100)->nullable(); // Field baru dari revisi
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes untuk performance
            $table->index('email');
            $table->index('username');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};