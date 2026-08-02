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
        Schema::create('ai_prompt_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 'impression_generator' | 'ai_review'
            $table->string('label');
            $table->text('prompt_text');
            $table->json('required_tokens'); // ["{{findings}}", "{{examination_type}}"]
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_settings');
    }
};
