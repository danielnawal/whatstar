<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_ai_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id')->unique();
            $table->string('business_name', 100)->nullable();
            $table->string('industry', 50)->nullable();
            $table->text('business_context')->nullable();
            $table->text('custom_system_prompt')->nullable();
            $table->boolean('ai_enabled')->default(true);
            $table->boolean('use_default_prompt')->default(true);
            $table->json('banned_terms')->nullable();
            $table->json('allowed_intents')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_ai_configs');
    }
};
