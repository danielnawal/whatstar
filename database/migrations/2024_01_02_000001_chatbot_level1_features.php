<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChatbotLevel1Features extends Migration
{
    public function up()
    {
        // ── Nuevas columnas en replies ──────────────────────────────────
        Schema::table('replies', function (Blueprint $table) {
            // Tipo de mensaje interactivo: null=texto, 'buttons'=botones, 'list'=lista
            $table->string('interactive_type', 20)->nullable()->after('reply_type');
            // JSON con opciones de botones o filas de lista
            $table->text('interactive_options')->nullable()->after('interactive_type');
            // Captura de dato: si tiene valor, el bot guarda la PRÓXIMA respuesta
            // del contacto con este nombre de campo (ej: 'nombre', 'email', 'telefono')
            $table->string('capture_field', 60)->nullable()->after('interactive_options');
            // Si es true, esta regla dispara la transferencia a agente humano
            $table->tinyInteger('trigger_handoff')->default(0)->after('capture_field');
        });

        // ── Nuevas columnas en chatbot_sessions ────────────────────────
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            // Bot pausado por handoff activo
            $table->tinyInteger('is_paused')->default(0)->after('current_reply_id');
            // JSON con datos capturados del lead (nombre, email, etc.)
            $table->text('lead_data')->nullable()->after('is_paused');
            // Campo que se está esperando capturar en este momento
            $table->string('capturing_field', 60)->nullable()->after('lead_data');
            // Es la primera vez que este contacto escribe a este dispositivo
            $table->tinyInteger('is_new_contact')->default(1)->after('capturing_field');
        });

        // ── Tabla de leads capturados ──────────────────────────────────
        Schema::create('chatbot_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->string('contact', 191);        // número del prospecto
            $table->string('contact_name', 191)->nullable();
            $table->string('contact_email', 191)->nullable();
            $table->string('contact_extra', 191)->nullable(); // campo libre adicional
            $table->string('interest', 191)->nullable();      // producto/servicio de interés
            $table->text('full_data')->nullable();            // JSON con todos los campos capturados
            $table->string('status', 30)->default('new');     // new, contacted, qualified, closed
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['device_id', 'contact']);
        });

        // ── Tabla de handoffs (transferencia a agente) ─────────────────
        Schema::create('chatbot_handoffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->string('contact', 191);
            $table->string('contact_name', 191)->nullable();
            $table->string('agent_number', 191)->nullable();  // número WhatsApp del agente
            $table->text('last_message')->nullable();          // último mensaje del cliente
            $table->string('status', 20)->default('pending'); // pending, active, resolved
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['device_id', 'contact', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chatbot_handoffs');
        Schema::dropIfExists('chatbot_leads');

        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_paused', 'lead_data', 'capturing_field', 'is_new_contact']);
        });

        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn(['interactive_type', 'interactive_options', 'capture_field', 'trigger_handoff']);
        });
    }
}
