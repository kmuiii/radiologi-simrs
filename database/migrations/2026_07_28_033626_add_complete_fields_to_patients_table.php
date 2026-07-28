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
        $prefix = config('laravolt.indonesia.table_prefix');
        
        Schema::table('patients', function (Blueprint $table) use ($prefix) {

            $table->string('tempat_lahir')->nullable()->after('name');
            $table->string('agama')->nullable();
            $table->string('suku')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('bahasa')->nullable()->default('Indonesia');
            $table->string('status_pernikahan')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('nama_ibu_kandung')->nullable();

            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('negara')->nullable()->default('Indonesia');

            $table->string('province_code')->nullable();
            $table->string('city_code')->nullable();
            $table->string('district_code')->nullable();
            $table->string('village_code')->nullable();

            $table->foreign('province_code')->references('code')->on($prefix.'provinces')->nullOnDelete();
            $table->foreign('city_code')->references('code')->on($prefix.'cities')->nullOnDelete();
            $table->foreign('district_code')->references('code')->on($prefix.'districts')->nullOnDelete();
            $table->foreign('village_code')->references('code')->on($prefix.'villages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['province_code', 'city_code', 'district_code', 'village_code']);
            $table->dropColumn([
                'tempat_lahir', 'agama', 'suku', 'pekerjaan', 'bahasa', 'status_pernikahan',
                'pendidikan', 'nama_ibu_kandung', 'rt', 'rw', 'kode_pos', 'negara',
                'province_code', 'city_code', 'district_code', 'village_code',
            ]);
        });
    }
};
