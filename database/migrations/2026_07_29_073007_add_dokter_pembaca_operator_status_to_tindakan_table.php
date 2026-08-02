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
            $table->foreignId('dokter_pembaca_id')->nullable()->after('jenis_tindakan_id')->constrained('users')->nullOnDelete();
            $table->foreignId('operator_1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('operator_2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('operator_3_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('operator_4_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('operator_5_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('order');
            $table->dateTime('tgl_datang')->nullable();
            $table->dateTime('tgl_valid')->nullable();
            $table->dateTime('tgl_periksa')->nullable();
            $table->dateTime('tgl_selesai')->nullable();
            $table->dateTime('tgl_serah')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindakan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dokter_pembaca_id');
            $table->dropConstrainedForeignId('operator_1_id');
            $table->dropConstrainedForeignId('operator_2_id');
            $table->dropConstrainedForeignId('operator_3_id');
            $table->dropConstrainedForeignId('operator_4_id');
            $table->dropConstrainedForeignId('operator_5_id');
            $table->dropColumn(['status', 'tgl_datang', 'tgl_valid', 'tgl_periksa', 'tgl_selesai', 'tgl_serah']);
        });
    }
};
