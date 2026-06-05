<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->timestamp('paused_until')->nullable()->after('is_paused');
            $table->timestamp('human_handoff_at')->nullable()->after('paused_until');
        });
    }

    public function down()
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->dropColumn(['paused_until', 'human_handoff_at']);
        });
    }
};
