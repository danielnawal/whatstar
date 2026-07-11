<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_industry_templates', function (Blueprint $table) {
            $table->id();
            $table->string('industry_key', 50);
            $table->string('industry_name', 100);
            $table->string('icon', 50)->nullable();
            $table->string('language', 5)->default('es');
            $table->text('description')->nullable();
            $table->json('rules_json');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['industry_key', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_industry_templates');
    }
};
