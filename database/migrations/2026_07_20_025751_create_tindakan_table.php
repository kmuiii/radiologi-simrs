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
        Schema::create('tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registrasi_id')->constrained('registrasi')->cascadeOnDelete();
            $table->foreignId('pelayanan_id')->constrained('pelayanan')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('jenis_tindakan_id')->constrained('jenis_tindakan');
            $table->string('referring_doctor_name')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tindakan');
    }
};
