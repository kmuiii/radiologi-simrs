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
        Schema::create('radilogy_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_order_id')->unique()->constrained('radiology_order')->cascadeOnDelete();
            $table->foreignId('report_template_id')->nullable()->constrained();   
            $table->json('template_snapshot')->nullable();
            $table->text('findings');
            $table->text('impression');
            $table->enum('impression_source', ['manual', 'ai_generated', 'ai_edited'])->default('manual');        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radilogy_reports');
    }
};
