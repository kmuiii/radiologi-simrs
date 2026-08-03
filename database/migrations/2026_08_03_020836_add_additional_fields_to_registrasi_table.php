<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registrasi', function (Blueprint $table) {
            $table->text('alergi')->nullable();
            $table->text('penyakit_khusus')->nullable();
            $table->string('jenis_pasien')->nullable();
            $table->string('kelas_layanan')->nullable();
            $table->string('asal_pasien')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrasi', function (Blueprint $table) {
            $table->dropColumn(['alergi', 'penyakit_khusus', 'jenis_pasien', 'kelas_layanan', 'asal_pasien']);
        });
    }
};
