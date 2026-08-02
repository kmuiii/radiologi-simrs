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
        Schema::table('tindakan', function (Blueprint $table) {
            $table->dateTime('tanggal_jam_diterima')->nullable();
            $table->string('penerima')->nullable();
            $table->string('status_diterima')->default('belum_diterima'); // diterima | belum_diterima | konfirmasi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindakan', function (Blueprint $table) {
            $table->dropColumn(['tanggal_jam_diterima', 'penerima', 'status_diterima']);
        });
    }
};
