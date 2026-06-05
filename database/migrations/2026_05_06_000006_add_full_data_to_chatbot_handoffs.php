<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('chatbot_handoffs', 'full_data')) {
            Schema::table('chatbot_handoffs', function (Blueprint $table) {
                $table->text('full_data')->nullable()->after('last_message');
            });
        }
    }

    public function down()
    {
        Schema::table('chatbot_handoffs', function (Blueprint $table) {
            $table->dropColumn('full_data');
        });
    }
};
