<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();      // pelaku aksi (boleh null = sistem)
            $table->string('user_nama')->nullable();   // snapshot nama pelaku
            $table->string('aksi');                    // mis. "Mengubah status donasi"
            $table->string('target')->nullable();      // mis. "Donasi - Budi Santoso"
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
