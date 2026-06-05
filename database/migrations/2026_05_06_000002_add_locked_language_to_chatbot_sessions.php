<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->string('locked_language', 5)->nullable()->after('replied_ids');
        });
    }

    public function down()
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->dropColumn('locked_language');
        });
    }
};
