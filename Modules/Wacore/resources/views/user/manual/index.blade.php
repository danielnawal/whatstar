@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Guía de uso WhatStar')])
@endsection
@push('css')
<style>
.manual-step-badge {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 1rem;
    flex-shrink: 0;
}
.manual-section-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1a4b8c;
    border-left: 4px solid #1a4b8c;
    padding-left: 12px;
    margin-bottom: 1.2rem;
}
.tool-card {
    border-left: 3px solid #e8ecf0;
    transition: border-color .2s;
}
.tool-card:hover { border-left-color: #5e72e4; }
.step-connector {
    width: 2px; height: 28px;
    background: #e8ecf0;
    margin: 0 auto;
}
.highlight-box {
    background: #f4f7ff;
    border: 1px solid #c5d3f5;
    border-radius: 8px;
    padding: 1rem 1.2rem;
}
code.key-code {
    background: #eef2ff;
    color: #3d5af1;
    padding: 2px 7px;
    border-radius: 4px;
    font-size: .82rem;
}
</style>
@endpush
@section('content')

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- INTRO BANNER                                                               --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card" style="background: linear-gradient(135deg,#1a4b8c 0%,#25d366 100%); border:none;">
            <div class="card-body py-4 d-flex align-items-center">
                <div class="mr-4">
                    <img src="{{ asset('public/images/whatstar-icon.svg') }}" width="60" alt="WhatStar"
                         onerror="this.style.display='none'">
                </div>
                <div>
                    <h2 class="text-white font-weight-bold mb-1">{{ __('Guía de uso — WhatStar') }}</h2>
                    <p class="text-white mb-0 opacity-8">
                        {{ __('Conecta tu WhatsApp, crea tu app, configura el número destino y empieza a recibir alertas GPS en segundos.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECCIÓN 1: PRIMEROS PASOS (3 pasos secuenciales)                          --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="row">
    <div class="col-12">
        <p class="manual-section-title"><i class="fas fa-rocket mr-2"></i>{{ __('Primeros pasos — configura WhatStar en 3 pasos') }}</p>
    </div>
</div>

{{-- ── PASO 1: Conectar el teléfono ──────────────────────────────────────── --}}
<div class="row mb-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <span class="manual-step-badge bg-gradient-primary text-white mr-3">1</span>
                <div>
                    <h4 class="mb-0 font-weight-bold">{{ __('Conectar tu teléfono WhatsApp') }}</h4>
                    <small class="text-muted">{{ __('Enlaza tu número de WhatsApp escaneando un código QR') }}</small>
                </div>
                <a href="{{ route('user.device.create') }}" class="btn btn-primary btn-sm ml-auto">
                    <i class="fas fa-plus mr-1"></i>{{ __('Crear dispositivo') }}
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Columna izquierda: pasos textuales --}}
                    <div class="col-md-7">
                        <div class="activities">

                            <div class="activity">
                                <div class="activity-icon bg-primary text-white shadow-primary">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-primary">{{ __('Paso 1.1') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('En el menú lateral haz clic en') }} <strong>{{ __('My Devices') }}</strong>
                                        {{ __('y luego en') }} <strong>{{ __('+ Create Device') }}</strong>.
                                    </p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-primary text-white shadow-primary">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-primary">{{ __('Paso 1.2') }}</span></div>
                                    <p class="mb-0">{{ __('Asigna un nombre descriptivo al dispositivo (ej. "Mi WhatsApp Principal") y guarda.') }}</p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-primary text-white shadow-primary">
                                    <i class="fas fa-qrcode"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-primary">{{ __('Paso 1.3') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('Haz clic en') }} <strong>{{ __('Scan') }}</strong> {{ __('en el dispositivo creado. Aparece un código QR en pantalla.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-success text-white">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-success">{{ __('Paso 1.4 — Escanear QR') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('Abre WhatsApp en tu celular →') }}
                                        <strong>{{ __('Menú (⋮) → Dispositivos vinculados → Vincular dispositivo') }}</strong>
                                        {{ __('→ apunta la cámara al QR de la pantalla.') }}
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="alert alert-success mt-3 mb-0 d-flex align-items-center">
                            <i class="fas fa-check-circle fa-lg mr-3"></i>
                            <div>
                                <strong>{{ __('¡Listo!') }}</strong>
                                {{ __('En cuanto el QR se escanee, el estado del dispositivo cambia a') }}
                                <span class="badge badge-success">Active</span>.
                                {{ __('Tu número ya puede enviar y recibir mensajes desde WhatStar.') }}
                            </div>
                        </div>
                    </div>

                    {{-- Columna derecha: imagen/gif animado --}}
                    <div class="col-md-5 d-flex align-items-center justify-content-center mt-3 mt-md-0">
                        <div class="text-center">
                            <img src="{{ asset('uploads/scan-demo.gif') }}" class="rounded shadow" style="max-width:220px; width:100%;"
                                 alt="{{ __('Demo escaneo QR') }}"
                                 onerror="this.replaceWith(document.createTextNode(''))">
                            <p class="text-muted small mt-2">{{ __('Apunta el celular al código QR') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="step-connector mb-2"></div>

{{-- ── PASO 2: Crear la App ────────────────────────────────────────────────── --}}
<div class="row mb-2">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <span class="manual-step-badge bg-gradient-info text-white mr-3">2</span>
                <div>
                    <h4 class="mb-0 font-weight-bold">{{ __('Crear una App') }}</h4>
                    <small class="text-muted">{{ __('La App genera las claves (appkey) que identifican tu integración') }}</small>
                </div>
                <a href="{{ route('user.apps.index') }}" class="btn btn-info btn-sm ml-auto text-white">
                    <i class="fas fa-cube mr-1"></i>{{ __('Mis Apps') }}
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <div class="activities">

                            <div class="activity">
                                <div class="activity-icon bg-info text-white">
                                    <i class="fas fa-cube"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-info">{{ __('Paso 2.1') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('Ve a') }} <strong>{{ __('My Apps') }}</strong> {{ __('en el menú lateral y haz clic en') }}
                                        <strong>{{ __('+ Create App') }}</strong>.
                                    </p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-info text-white">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-info">{{ __('Paso 2.2') }}</span></div>
                                    <p class="mb-0">
                                        <strong>{{ __('Select Number') }}</strong>: {{ __('elige el dispositivo WhatsApp que conectaste en el Paso 1.') }}
                                        {{ __('Los mensajes saldrán desde ese número.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-info text-white">
                                    <i class="fas fa-pen"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-info">{{ __('Paso 2.3') }}</span></div>
                                    <p class="mb-0">
                                        <strong>{{ __('App Name') }}</strong>: {{ __('ponle un nombre (ej. "Alertas GPS").') }}<br>
                                        <strong>{{ __('Website Link') }}</strong>: {{ __('ingresa la URL de tu sistema GPS (ej.') }}
                                        <code class="key-code">https://gpssoftwarenumberone.com</code>).
                                    </p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-success text-white">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-success">{{ __('Paso 2.4 — Obtener las claves') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('Una vez creada la App, haz clic en') }} <strong>⋮ → REST API</strong>.
                                        {{ __('Ahí encontrarás:') }}<br>
                                        • <code class="key-code">appkey</code> — {{ __('identifica tu App') }}<br>
                                        • <code class="key-code">authkey</code> — {{ __('identifica tu cuenta de usuario') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-md-5 mt-3 mt-md-0">
                        <div class="highlight-box">
                            <p class="font-weight-bold mb-2"><i class="fas fa-info-circle text-info mr-2"></i>{{ __('¿Para qué sirve la App?') }}</p>
                            <p class="mb-2 small">
                                {{ __('Cada App está ligada a un número WhatsApp y tiene sus propias claves API. Puedes crear una App por cada integración o cliente.') }}
                            </p>
                            <p class="mb-0 small">
                                {{ __('Si usas WhatStar con tu software GPS, la App es el puente que conecta las alertas con tu WhatsApp.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="step-connector mb-2"></div>

{{-- ── PASO 3: Configurar número destino de alertas ──────────────────────── --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <span class="manual-step-badge bg-gradient-success text-white mr-3">3</span>
                <div>
                    <h4 class="mb-0 font-weight-bold">{{ __('Configurar el número de WhatsApp destino') }}</h4>
                    <small class="text-muted">{{ __('Define a qué número(s) llegan las alertas de tu software GPS') }}</small>
                </div>
            </div>
            <div class="card-body">

                {{-- Mensaje destacado para usuarios del software GPS --}}
                <div class="alert alert-success d-flex align-items-start mb-4">
                    <i class="fas fa-bolt fa-lg mr-3 mt-1"></i>
                    <div>
                        <strong>{{ __('¡Buenas noticias si usas nuestro software GPS!') }}</strong>
                        {{ __('El sistema ya tiene tu cuenta vinculada y configura todo automáticamente. Solo genera el token y listo — sin copiar ni pegar nada.') }}
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-7">
                        <div class="activities">

                            <div class="activity">
                                <div class="activity-icon bg-success text-white">
                                    <i class="fas fa-broadcast-tower"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-success">{{ __('Paso 3.1') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('En') }} <strong>{{ __('My Apps') }}</strong>, {{ __('haz clic en') }}
                                        <strong>⋮ → Conectar software GPS</strong>.
                                        {{ __('Se abre la página de configuración del enlace con tu software GPS.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-success text-white">
                                    <i class="fas fa-magic"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-success">{{ __('Paso 3.2 — Generar token') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('Haz clic en') }} <strong>{{ __('Generar token') }}</strong>.
                                        {{ __('Con eso es suficiente.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-success text-white">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-success">{{ __('¿Y las claves? — Se configuran solas') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('Si accediste a WhatStar desde el botón de tu software GPS, el sistema ya tiene tu cuenta vinculada. Al generar el token,') }}
                                        <strong>{{ __('WhatStar envía automáticamente todas las credenciales al software GPS') }}</strong>
                                        {{ __('— no tienes que copiar ni pegar nada.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="activity">
                                <div class="activity-icon bg-success text-white">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="activity-detail">
                                    <div class="mb-1"><span class="text-job text-success">{{ __('Paso 3.3 — Solo elige a quién llega la alerta') }}</span></div>
                                    <p class="mb-0">
                                        {{ __('Lo único que configuras en el software GPS es') }}
                                        <strong>{{ __('el número de WhatsApp que recibirá cada alerta') }}</strong>.
                                        {{ __('Formato: código de país + número, sin espacios ni guiones.') }}
                                    </p>
                                </div>
                            </div>

                        </div>

                        {{-- Nota para clientes externos --}}
                        <div class="alert alert-light border mt-3 mb-0">
                            <p class="mb-0 small">
                                <i class="fas fa-info-circle text-muted mr-1"></i>
                                <strong>{{ __('¿Usas un software GPS diferente?') }}</strong>
                                {{ __('En ese caso sí necesitas copiar manualmente la') }}
                                <strong>{{ __('URL del webhook') }}</strong> {{ __('y el') }} <strong>{{ __('token (outkey)') }}</strong>
                                {{ __('que aparecen en la página "Conectar software GPS" y pegarlos en la sección de alertas de tu sistema.') }}
                            </p>
                        </div>

                    </div>

                    <div class="col-md-5 mt-3 mt-md-0">
                        <div class="highlight-box mb-3">
                            <p class="font-weight-bold mb-1 small"><i class="fas fa-phone-alt text-success mr-1"></i>{{ __('Formato del número') }}</p>
                            <p class="mb-1 small">{{ __('Incluye el código de país sin el signo +.') }}</p>
                            <div class="bg-white rounded p-2 small" style="font-family:monospace;">
                                ✅ <code>573001234567</code> &nbsp;<span class="text-muted">(Colombia)</span><br>
                                ✅ <code>521234567890</code> &nbsp;<span class="text-muted">(México)</span><br>
                                ✅ <code>5491123456789</code> &nbsp;<span class="text-muted">(Argentina)</span><br>
                                ❌ <code>+57 300 123 4567</code>
                            </div>
                        </div>
                        <div class="highlight-box">
                            <p class="font-weight-bold mb-1 small"><i class="fas fa-users text-primary mr-1"></i>{{ __('Múltiples destinatarios') }}</p>
                            <p class="mb-0 small">
                                {{ __('Puedes enviar la misma alerta a varios números separando con coma:') }}<br>
                                <code class="key-code">573001234567,573009876543</code>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Ejemplo de alerta recibida --}}
                <div class="mt-4">
                    <p class="font-weight-bold mb-2"><i class="fab fa-whatsapp text-success mr-2"></i>{{ __('Así llega la alerta al WhatsApp del destinatario:') }}</p>
                    <p class="text-muted small mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ __('El encabezado del mensaje muestra el nombre que le diste a tu App al crearla — así tus clientes siempre ven el nombre de tu empresa, no el de ninguna plataforma.') }}
                    </p>
                    <div class="bg-light p-3 rounded d-inline-block" style="font-family:monospace; font-size:.85rem; line-height:1.7; max-width:420px;">
                        🚨 <strong>{{ __('Transportes Mi Empresa S.A.') }}</strong><br>
                        📦 <strong>{{ __('Unidad:') }}</strong> {{ __('Camión 01') }}<br>
                        ⚡ <strong>{{ __('Evento:') }}</strong> {{ __('Exceso de velocidad') }}<br>
                        🚗 <strong>{{ __('Velocidad:') }}</strong> 120 km/h<br>
                        📍 <strong>{{ __('Dirección:') }}</strong> {{ __('Calle 80 #45-23, Bogotá') }}<br>
                        🗺 <strong>{{ __('Mapa:') }}</strong> https://maps.google.com/?q=4.61,-74.08<br>
                        🕐 <strong>{{ __('Hora:') }}</strong> {{ now()->format('Y-m-d H:i:s') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECCIÓN 2: TODAS LAS HERRAMIENTAS                                         --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="row mt-2">
    <div class="col-12">
        <p class="manual-section-title"><i class="fas fa-tools mr-2"></i>{{ __('Herramientas de WhatStar') }}</p>
    </div>
</div>

<div class="row">

    {{-- Dispositivos --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="fi-rs-sensor-on mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('My Devices — Mis dispositivos') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Gestiona los teléfonos WhatsApp vinculados. Cada dispositivo es un número desde el que puedes enviar mensajes. Puedes tener varios según tu plan.') }}
                        </p>
                        <a href="{{ route('user.device.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ir a Dispositivos') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Apps --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="fas fa-cube mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('My Apps — API de mensajes') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Cada App tiene una') }} <code class="key-code">appkey</code> {{ __('propia. Con ella tu sistema externo (GPS, CRM, etc.) puede enviar mensajes vía REST API. También es el punto de configuración para alertas de tu software GPS.') }}
                        </p>
                        <a href="{{ route('user.apps.index') }}" class="btn btn-info btn-sm text-white">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ir a Apps') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Templates --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="fi fi-rs-template-alt mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('Templates — Plantillas de mensaje') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Crea mensajes reutilizables con variables dinámicas (ej.') }} <code class="key-code">{nombre}</code>, <code class="key-code">{placa}</code>).
                            {{ __('Úsalas en envíos masivos o API para personalizar cada mensaje automáticamente.') }}
                        </p>
                        <a href="{{ url('/user/template') }}" class="btn btn-warning btn-sm text-white">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ir a Templates') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single Send --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="fi fi-rs-paper-plane mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('Single Send — Envío individual') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Envía un mensaje de texto (o con archivo adjunto) a un número específico de forma inmediata. Ideal para pruebas o mensajes puntuales sin pasar por la API.') }}
                        </p>
                        <a href="{{ url('/user/sent-text-message') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Enviar mensaje') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chatbot --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="fas fa-robot mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('Chatbot — Respuestas automáticas') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Define reglas de respuesta automática basadas en palabras clave. Cuando alguien te escribe con una frase reconocida, el bot responde al instante — ideal para FAQs, menús o captación de leads.') }}
                        </p>
                        <a href="{{ route('user.chatbot.index') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ir a Chatbot') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Contact Book --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-default text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="fi fi-rs-address-book mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('Contact Book — Libreta de contactos') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Importa tu lista de contactos (CSV/Excel) y organízalos en grupos. Los grupos se usan en los envíos masivos para segmentar tu audiencia.') }}
                        </p>
                        <a href="{{ url('/user/contact') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ir a Contactos') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Message --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-danger text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="fi fi-rs-rocket-lunch mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('Send Bulk Message — Envío masivo') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Envía un mismo mensaje a todos los contactos de un grupo. Puedes adjuntar imágenes, PDFs o documentos. Incluye delay inteligente entre mensajes para evitar bloqueos.') }}
                        </p>
                        <a href="{{ url('/user/bulk-message') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ir a Masivos') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scheduled Message --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="ni ni-calendar-grid-58 mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('Scheduled Message — Mensajes programados') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Programa mensajes para que se envíen en una fecha y hora específica. Útil para recordatorios, campañas o notificaciones recurrentes.') }}
                        </p>
                        <a href="{{ url('/user/schedule-message') }}" class="btn btn-info btn-sm text-white">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ir a Programados') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Message Log --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="ni ni-ui-04 mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('Message Log — Registro de mensajes') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Historial completo de todos los mensajes enviados: fecha, número destino, estado (enviado/fallido) y contenido. Permite depurar problemas de entrega.') }}
                        </p>
                        <a href="{{ url('/user/logs') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ver Logs') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Webhook Logs --}}
    <div class="col-md-6 mb-3">
        <div class="card tool-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow mr-3" style="min-width:46px;height:46px;">
                        <i class="fi fi-rs-chart-connected mt-2"></i>
                    </div>
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ __('Webhook Logs — Registro de webhooks') }}</h5>
                        <p class="text-muted small mb-2">
                            {{ __('Registro de todas las llamadas recibidas por la API (alertas GPS, integraciones externas). Muestra el payload recibido y el resultado del procesamiento.') }}
                        </p>
                        <a href="{{ url('/user/webhooks') }}" class="btn btn-warning btn-sm text-white">
                            <i class="fas fa-arrow-right mr-1"></i>{{ __('Ver Webhooks') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- CTA: ¿Dudas? Soporte                                                      --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="row mt-2">
    <div class="col-12">
        <div class="card bg-gradient-primary text-white">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h4 class="text-white font-weight-bold mb-1">{{ __('¿Necesitas ayuda?') }}</h4>
                    <p class="mb-0 opacity-8">{{ __('Abre un ticket de soporte y te respondemos a la brevedad.') }}</p>
                </div>
                <a href="{{ route('user.support.create') }}" class="btn btn-white btn-sm mt-2 mt-md-0">
                    <i class="fas fa-headset mr-1"></i>{{ __('Abrir ticket de soporte') }}
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
