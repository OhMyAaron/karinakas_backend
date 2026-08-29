<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galeri', function (Blueprint $table) {
            // Nama tiap foto (berbeda dari `judul` yang merupakan nama kegiatan/album)
            $table->string('nama_foto', 200)->nullable()->after('judul');
        });
    }

    public function down(): void
    {
        Schema::table('galeri', function (Blueprint $table) {
            $table->dropColumn('nama_foto');
        });
    }
};
