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
        Schema::create('patients_explanations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_report_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('ai_generated_text');
            $table->text('edited_text')->nullable();
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients_explanations');
    }
};
