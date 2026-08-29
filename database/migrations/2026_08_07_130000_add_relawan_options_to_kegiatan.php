<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opsi jenis relawan & bidang minat per kegiatan (diisi admin)
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->json('jenis_relawan')->nullable()->after('kuota_relawan');
            $table->json('bidang_minat')->nullable()->after('jenis_relawan');
        });

        // relawan.jenis: dari ENUM('Lansia','Kebencanaan') → string bebas
        // agar bisa memakai jenis kustom yang ditentukan admin per kegiatan
        Schema::table('relawan', function (Blueprint $table) {
            $table->string('jenis', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn(['jenis_relawan', 'bidang_minat']);
        });

        Schema::table('relawan', function (Blueprint $table) {
            $table->enum('jenis', ['Lansia', 'Kebencanaan'])->nullable()->change();
        });
    }
};
