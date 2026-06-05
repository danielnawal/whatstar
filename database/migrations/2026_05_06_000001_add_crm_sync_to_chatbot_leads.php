<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('chatbot_leads', function (Blueprint $table) {
            $table->tinyInteger('synced_to_crm')->default(0)->after('status');
            $table->string('crm_lead_id', 191)->nullable()->after('synced_to_crm');
            $table->unsignedSmallInteger('crm_sync_attempts')->default(0)->after('crm_lead_id');
            $table->text('crm_last_error')->nullable()->after('crm_sync_attempts');
            $table->timestamp('crm_synced_at')->nullable()->after('crm_last_error');

            $table->index(['synced_to_crm', 'crm_sync_attempts'], 'idx_chatbot_leads_crm_sync');
        });
    }

    public function down()
    {
        Schema::table('chatbot_leads', function (Blueprint $table) {
            $table->dropIndex('idx_chatbot_leads_crm_sync');
            $table->dropColumn([
                'synced_to_crm',
                'crm_lead_id',
                'crm_sync_attempts',
                'crm_last_error',
                'crm_synced_at',
            ]);
        });
    }
};
