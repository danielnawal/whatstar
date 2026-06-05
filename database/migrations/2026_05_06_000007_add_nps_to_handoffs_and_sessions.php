<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('chatbot_handoffs', 'nps')) {
            Schema::table('chatbot_handoffs', function (Blueprint $table) {
                $table->tinyInteger('nps')->nullable()->after('full_data');
                $table->text('nps_comment')->nullable()->after('nps');
            });
        }
        if (!Schema::hasColumn('chatbot_sessions', 'nps_pending')) {
            Schema::table('chatbot_sessions', function (Blueprint $table) {
                $table->tinyInteger('nps_pending')->default(0)->after('bant_step');
            });
        }
    }

    public function down()
    {
        Schema::table('chatbot_handoffs', function (Blueprint $table) {
            $table->dropColumn(['nps', 'nps_comment']);
        });
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->dropColumn('nps_pending');
        });
    }
};
