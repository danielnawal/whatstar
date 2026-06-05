<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->string('bant_step', 30)->nullable()->after('capturing_field');
        });
    }

    public function down()
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->dropColumn('bant_step');
        });
    }
};
