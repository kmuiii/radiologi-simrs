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
        Schema::create('radiology_order', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('referring_doctor_name')->nullable();
            $table->foreignId('radiologist_id')->constrained('users');
            $table->text('clinical_notes')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_order');
    }
};
