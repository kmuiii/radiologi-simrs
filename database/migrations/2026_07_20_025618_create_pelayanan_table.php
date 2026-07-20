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
        Schema::create('pelayanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registrasi_id')->constrained('registrasi')->cascadeOnDelete();
            $table->foreignId('master_poli_id')->constrained('master_poli');
            $table->foreignId('rujukan_dari_pelayanan_id')->nullable()
                ->constrained('pelayanan')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelayanan');
    }
};
