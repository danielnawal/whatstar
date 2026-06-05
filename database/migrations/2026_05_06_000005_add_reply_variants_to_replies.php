<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('replies', 'reply_variants')) {
            Schema::table('replies', function (Blueprint $table) {
                $table->text('reply_variants')->nullable()->after('reply_pt');
            });
        }
    }

    public function down()
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn('reply_variants');
        });
    }
};
