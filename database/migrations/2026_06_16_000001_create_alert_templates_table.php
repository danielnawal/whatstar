<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('app_id')->nullable();
            $table->string('event_type', 50)->default('default');
            $table->string('channel', 20)->default('whatsapp');
            $table->string('language', 5)->default('es');
            $table->text('template_text');
            $table->boolean('is_active')->default(true);
            $table->json('extra_destinations')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_type', 'channel', 'language']);
            $table->index(['app_id', 'event_type']);
        });

        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('app_id')->nullable();
            $table->string('event_type', 50)->nullable();
            $table->string('unit_pattern', 100)->nullable();
            $table->time('mute_from')->nullable();
            $table->time('mute_to')->nullable();
            $table->unsignedSmallInteger('dedupe_seconds')->default(0);
            $table->unsignedSmallInteger('min_speed')->nullable();
            $table->boolean('block')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('alert_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('app_id')->nullable();
            $table->string('event_type', 50);
            $table->string('unit', 100)->nullable();
            $table->string('channel', 20);
            $table->string('destination', 100);
            $table->string('status', 20);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['app_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_logs');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('alert_templates');
    }
};
