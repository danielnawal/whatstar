<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('replies', function (Blueprint $t) {
            if (!Schema::hasColumn('replies', 'reply_en')) {
                $t->text('reply_en')->nullable()->after('reply');
            }
            if (!Schema::hasColumn('replies', 'reply_pt')) {
                $t->text('reply_pt')->nullable()->after('reply_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $t) {
            if (Schema::hasColumn('replies', 'reply_en')) {
                $t->dropColumn('reply_en');
            }
            if (Schema::hasColumn('replies', 'reply_pt')) {
                $t->dropColumn('reply_pt');
            }
        });
    }
};
