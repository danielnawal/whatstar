<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('replies', function (Blueprint $t) {
            if (!Schema::hasColumn('replies', 'media_path')) {
                $t->string('media_path', 500)->nullable()->after('reply_pt');
            }
            if (!Schema::hasColumn('replies', 'media_filename')) {
                $t->string('media_filename', 255)->nullable()->after('media_path');
            }
            if (!Schema::hasColumn('replies', 'media_mime')) {
                $t->string('media_mime', 100)->nullable()->after('media_filename');
            }
        });
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $t) {
            foreach (['media_path','media_filename','media_mime'] as $col) {
                if (Schema::hasColumn('replies', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
