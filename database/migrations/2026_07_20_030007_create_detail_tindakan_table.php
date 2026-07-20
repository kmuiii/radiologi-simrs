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
        Schema::create('detail_tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tindakan_id')->constrained('tindakan')->cascadeOnDelete();
            $table->foreignId('examination_type_id')->constrained();
            $table->foreignId('radiologist_id')->nullable()->constrained('users');
            $table->decimal('price', 12, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_tindakan');
    }
};
