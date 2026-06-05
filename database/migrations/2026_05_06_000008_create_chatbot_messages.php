<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('chatbot_messages')) return;

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('device_id');
            $table->string('contact');
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->string('intent', 50)->nullable();
            $table->unsignedBigInteger('matched_reply_id')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['session_id', 'created_at']);
            $table->index(['device_id', 'contact', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
