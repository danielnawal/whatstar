<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpgradeChatbotSystem extends Migration
{
    public function up()
    {
        // ── Nuevas columnas en replies ──────────────────────────────────
        Schema::table('replies', function (Blueprint $table) {
            // Nuevos tipos de coincidencia: starts_with, contains, regex, any
            // (equal y like se conservan para compatibilidad)
            $table->integer('priority')->default(0)->after('match_type');

            // Cooldown: 0 = siempre responde; >0 = minutos de espera entre respuestas al mismo contacto
            $table->integer('cooldown_minutes')->default(0)->after('priority');

            // Solo una vez: nunca vuelve a responder al mismo contacto
            $table->tinyInteger('only_once')->default(0)->after('cooldown_minutes');

            // Horario de atención
            $table->tinyInteger('schedule_enabled')->default(0)->after('only_once');
            $table->time('schedule_start')->nullable()->after('schedule_enabled');
            $table->time('schedule_end')->nullable()->after('schedule_start');
            // Días: "1,2,3,4,5,6,7" (Lun=1 … Dom=7)
            $table->string('schedule_days', 20)->default('1,2,3,4,5,6,7')->after('schedule_end');
            $table->text('out_of_hours_reply')->nullable()->after('schedule_days');

            // Flujo conversacional: si es distinto de null, esta regla solo se evalúa
            // cuando el contacto está "dentro" del flujo iniciado por el padre
            $table->unsignedBigInteger('parent_reply_id')->nullable()->after('out_of_hours_reply');
        });

        // ── Tabla de sesiones de chatbot ────────────────────────────────
        Schema::create('chatbot_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            // Número del contacto (sin formato)
            $table->string('contact', 191);
            // Última regla que se disparó: define en qué "menú" está el contacto
            $table->unsignedBigInteger('current_reply_id')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            // IDs de reglas ya enviadas (para only_once), separados por coma
            $table->text('replied_ids')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'contact'], 'chatbot_sessions_unique');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chatbot_sessions');

        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn([
                'priority', 'cooldown_minutes', 'only_once',
                'schedule_enabled', 'schedule_start', 'schedule_end',
                'schedule_days', 'out_of_hours_reply', 'parent_reply_id',
            ]);
        });
    }
}
