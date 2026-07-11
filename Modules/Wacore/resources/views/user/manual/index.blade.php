@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Manual')])
@endsection
@push('css')
<style>
/* ══════════════════════════════════════════════════════════════════════════
   MANUAL — Sistema de diseño premium.
   Este bloque solo se carga en la página del Manual, así que estas reglas
   no afectan al resto del sistema. Paleta sobria de marca: un azul navy como
   color estructural y un verde (WhatsApp) usado con moderación para "listo".
   ══════════════════════════════════════════════════════════════════════════ */
:root{
  --ws-navy:#1a4b8c;
  --ws-navy-d:#12365f;
  --ws-green:#1f9d57;
  --ws-ink:#1f2a37;
  --ws-muted:#5b6673;
  --ws-bg:#f5f8fc;
  --ws-line:#e6ecf4;
  --ws-chip:#eaf1fb;
  --ws-shadow:0 4px 20px rgba(20,45,80,.06);
  --ws-shadow-h:0 10px 34px rgba(20,45,80,.12);
}

/* Tipografía legible para usuarios no técnicos */
.manual-page{ color:var(--ws-ink); font-size:1.12rem; }
.manual-page p, .manual-page li{ font-size:1.12rem !important; line-height:1.8; }
.manual-page .small, .manual-page small,
.manual-page [style*="font-size:.78rem"],
.manual-page [style*="font-size:.82rem"],
.manual-page [style*="font-size:.85rem"]{ font-size:1.06rem !important; line-height:1.75; }
.manual-page .text-muted{ color:var(--ws-muted) !important; }
.manual-page h2{ font-size:1.9rem; }
.manual-page h4{ font-size:1.5rem; }
.manual-page h5{ font-size:1.25rem; }
.manual-page h6{ font-size:1.14rem; }

/* ── Tarjetas base: sombra suave, bordes redondeados, sin arcoíris ────────── */
.manual-page .ws-card{
  background:#fff; border:1px solid var(--ws-line); border-radius:16px;
  box-shadow:var(--ws-shadow); overflow:hidden;
}
.manual-page .ws-card-body{ padding:1.75rem 1.9rem; }

/* ── Encabezado principal (hero) ──────────────────────────────────────────── */
.ws-hero{
  background:linear-gradient(120deg,var(--ws-navy-d) 0%,var(--ws-navy) 60%,#2660b0 100%);
  border-radius:18px; color:#fff; padding:2.4rem 2.2rem;
  box-shadow:0 14px 40px rgba(18,54,95,.28);
}
.ws-hero h2{ color:#fff; font-weight:700; letter-spacing:.2px; margin-bottom:.4rem; }
.ws-hero p{ color:#eaf1fb; margin-bottom:0; max-width:760px; }
.ws-hero-ico{
  width:66px; height:66px; border-radius:16px; flex-shrink:0;
  background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.25);
  display:flex; align-items:center; justify-content:center;
}
.ws-hero-ico i{ font-size:1.8rem; color:#fff; }

/* ── Título de sección: un acento fino, sin barra pesada de color ─────────── */
.ws-section{ display:flex; align-items:center; gap:.85rem; margin:.4rem 0 1.4rem; }
.ws-section-ico{
  width:42px; height:42px; border-radius:12px; flex-shrink:0;
  background:var(--ws-chip); color:var(--ws-navy);
  display:flex; align-items:center; justify-content:center; font-size:1.15rem;
}
.ws-section h3{ font-size:1.5rem; font-weight:700; color:var(--ws-navy-d); margin:0; }
.ws-section small{ display:block; color:var(--ws-muted); font-size:1rem; font-weight:400; }

/* ── Pasos numerados ──────────────────────────────────────────────────────── */
.ws-step-head{
  display:flex; align-items:center; gap:1rem;
  padding:1.35rem 1.9rem; border-bottom:1px solid var(--ws-line);
  background:linear-gradient(90deg,#fafcff,#fff);
}
.ws-step-num{
  width:52px; height:52px; border-radius:14px; flex-shrink:0;
  background:var(--ws-navy); color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-size:1.5rem; font-weight:700;
  box-shadow:0 6px 16px rgba(26,75,140,.28);
}
.ws-step-head h4{ margin:0; font-weight:700; color:var(--ws-navy-d); }
.ws-step-head small{ color:var(--ws-muted); }

/* ── Sub-pasos: mismo estilo para todos (sin colores distintos por tarjeta).
      El icono va en un chip con AIRE alrededor, no pegado a ninguna barra. ── */
.ws-sub{
  display:flex; gap:1.1rem; align-items:flex-start;
  background:#fff; border:1px solid var(--ws-line); border-radius:14px;
  padding:1.15rem 1.25rem; margin-bottom:1rem; transition:box-shadow .18s,transform .18s;
}
.ws-sub:hover{ box-shadow:var(--ws-shadow); transform:translateY(-1px); }
.ws-sub-ico{
  width:46px; height:46px; border-radius:12px; flex-shrink:0;
  background:var(--ws-chip); color:var(--ws-navy);
  display:flex; align-items:center; justify-content:center; font-size:1.2rem;
}
.ws-sub-ico.is-done{ background:#e7f6ee; color:var(--ws-green); }
.ws-sub-body h6{ margin:0 0 .3rem; font-weight:700; color:var(--ws-ink); }
.ws-sub-body p{ margin:0; }
.ws-sub-body p + p{ margin-top:.35rem; }

/* ── Caja de nota / recuadro lateral ──────────────────────────────────────── */
.ws-note{
  background:var(--ws-bg); border:1px solid var(--ws-line);
  border-radius:14px; padding:1.25rem 1.35rem;
}
.ws-note h6{ font-weight:700; color:var(--ws-navy-d); margin-bottom:.6rem; }
.ws-note.is-tip{ background:#eef6ff; border-color:#d3e5fb; }
.ws-note.is-warn{ background:#fff6ec; border-color:#f6e2c6; }

/* ── Avisos destacados ────────────────────────────────────────────────────── */
.ws-callout{
  display:flex; gap:1rem; align-items:flex-start;
  border-radius:14px; padding:1.15rem 1.3rem; font-size:1.06rem; line-height:1.7;
}
.ws-callout i{ font-size:1.4rem; margin-top:.1rem; flex-shrink:0; }
.ws-callout.is-info{ background:#eef6ff; border:1px solid #d3e5fb; color:#183b63; }
.ws-callout.is-good{ background:#e9f7ef; border:1px solid #c9ecd7; color:#14663b; }
.ws-callout.is-warn{ background:#fff6ec; border:1px solid #f6e2c6; color:#7a5218; }

/* ── Tarjetas de herramientas: TODAS iguales, icono uniforme y con aire ───── */
.ws-tool{
  height:100%; background:#fff; border:1px solid var(--ws-line);
  border-radius:16px; padding:1.5rem; box-shadow:var(--ws-shadow);
  transition:box-shadow .2s, transform .2s, border-color .2s;
}
.ws-tool:hover{ box-shadow:var(--ws-shadow-h); transform:translateY(-3px); border-color:#d7e3f4; }
.ws-tool-ico{
  width:54px; height:54px; border-radius:14px; flex-shrink:0; margin-bottom:1rem;
  background:var(--ws-chip); color:var(--ws-navy);
  display:flex; align-items:center; justify-content:center; font-size:1.4rem;
}
.ws-tool h5{ font-weight:700; color:var(--ws-navy-d); margin-bottom:.5rem; }
.ws-tool p{ color:var(--ws-muted); margin-bottom:1.1rem; }
.ws-badge-new{
  display:inline-block; vertical-align:middle; margin-left:.5rem;
  background:#e7f6ee; color:var(--ws-green); font-weight:700;
  font-size:.72rem; letter-spacing:.4px; padding:.2rem .55rem; border-radius:20px;
}

/* ── Botones ──────────────────────────────────────────────────────────────── */
.manual-page .ws-btn{
  display:inline-flex; align-items:center; gap:.5rem;
  background:var(--ws-navy); color:#fff; font-weight:600; font-size:1rem;
  border:none; border-radius:10px; padding:.55rem 1.05rem; text-decoration:none;
  transition:background .15s, box-shadow .15s;
}
.manual-page .ws-btn:hover{ background:var(--ws-navy-d); box-shadow:0 6px 16px rgba(26,75,140,.25); color:#fff; }
.manual-page .ws-btn-ghost{
  background:#fff; color:var(--ws-navy); border:1px solid #cfe0f4;
}
.manual-page .ws-btn-ghost:hover{ background:var(--ws-chip); color:var(--ws-navy-d); }
.manual-page .ws-btn-light{ background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.35); color:#fff; }
.manual-page .ws-btn-light:hover{ background:rgba(255,255,255,.28); color:#fff; }

/* ── Código en línea y ejemplos ───────────────────────────────────────────── */
.manual-page code.key-code{
  background:var(--ws-chip); color:var(--ws-navy); font-size:1rem;
  padding:2px 8px; border-radius:6px;
}
.manual-page .ws-tag{ font-weight:700; color:var(--ws-navy); }

/* ── Tabla de parámetros ──────────────────────────────────────────────────── */
.manual-page .ws-table{ width:100%; border-collapse:separate; border-spacing:0; }
.manual-page .ws-table th{
  text-align:left; font-size:.95rem; text-transform:uppercase; letter-spacing:.4px;
  color:var(--ws-muted); padding:.6rem .8rem; border-bottom:2px solid var(--ws-line);
}
.manual-page .ws-table td{ padding:.7rem .8rem; border-bottom:1px solid var(--ws-line); font-size:1.04rem; }

/* ── Conector vertical entre pasos ────────────────────────────────────────── */
.ws-connector{ width:2px; height:26px; background:var(--ws-line); margin:0 auto; border-radius:2px; }

/* ── Ejemplo de mensaje (chat) ────────────────────────────────────────────── */
.ws-chat{
  background:#f3f7fc; border:1px solid var(--ws-line); border-radius:14px;
  padding:1.15rem 1.3rem; font-family:'Segoe UI',system-ui,sans-serif;
  font-size:1.02rem; line-height:1.75; max-width:440px;
}
.ws-num-list li{ margin-bottom:.35rem; }

@media (max-width:576px){
  .ws-hero{ padding:1.8rem 1.4rem; }
  .ws-card-body{ padding:1.3rem 1.2rem; }
}
</style>
@endpush
@section('content')
<div class="manual-page">

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- HERO --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="row mb-4">
  <div class="col-12">
    <div class="ws-hero d-flex align-items-center flex-wrap" style="gap:1.4rem;">
      <div class="ws-hero-ico">
        <img src="{{ asset('public/images/whatstar-icon.svg') }}" width="38" alt="WhatStar"
             onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fas fa-book-open'}))">
      </div>
      <div style="flex:1; min-width:240px;">
        <h2>{{ __('Manual') }}</h2>
        <p>{{ __('Conecta tu WhatsApp, crea tu app, define el número destino y empieza a recibir alertas GPS. Todo explicado paso a paso, sin tecnicismos.') }}</p>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECCIÓN 1 — PRIMEROS PASOS --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="ws-section">
  <div class="ws-section-ico"><i class="fas fa-rocket"></i></div>
  <div>
    <h3>{{ __('Primeros pasos') }}</h3>
    <small>{{ __('Configura WhatStar en 3 pasos') }}</small>
  </div>
</div>

{{-- ── PASO 1 ───────────────────────────────────────────────────────────────── --}}
<div class="ws-card mb-2">
  <div class="ws-step-head">
    <div class="ws-step-num">1</div>
    <div style="flex:1;">
      <h4>{{ __('Activar tu teléfono WhatsApp') }}</h4>
      <small>{{ __('Sigue esta guía exacta — toma 2 minutos') }}</small>
    </div>
    <a href="{{ route('user.device.create') }}" class="ws-btn">
      <i class="fas fa-plus"></i>{{ __('Crear dispositivo') }}
    </a>
  </div>
  <div class="ws-card-body">

    <div class="ws-callout is-warn mb-4">
      <i class="fas fa-exclamation-triangle"></i>
      <div>
        <strong>{{ __('Antes de empezar:') }}</strong>
        <ul class="mb-0 mt-2">
          <li>{{ __('Tu teléfono debe tener WhatsApp instalado y funcionando.') }}</li>
          <li>{{ __('Necesitas internet en el celular para escanear el QR.') }}</li>
          <li>{{ __('No uses WhatsApp Business y WhatsApp normal con el mismo número.') }}</li>
          <li>{{ __('Tu teléfono no se desconectará — sigue funcionando normal.') }}</li>
        </ul>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-7">

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-plus-circle"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('1A. Crear dispositivo en WhatStar') }}</h6>
            <p>{{ __('En el menú izquierdo entra a') }} <span class="ws-tag">{{ __('My Devices') }}</span> → <span class="ws-tag">+ Create Device</span>.</p>
            <p class="text-muted">{{ __('Pon un nombre descriptivo (ej. "WhatsApp Ventas") y guarda.') }}</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-qrcode"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('1B. Generar código QR') }}</h6>
            <p>{{ __('En la lista de dispositivos, haz clic en el ícono de') }} <span class="ws-tag">{{ __('QR / Escanear') }}</span> {{ __('del dispositivo que acabas de crear.') }}</p>
            <p class="text-muted">{{ __('Aparece un código QR grande en pantalla. Déjalo abierto.') }}</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fab fa-whatsapp"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('1C. Abrir WhatsApp en tu celular') }}</h6>
            <p>{{ __('Toma el celular donde tienes el número que quieres conectar y abre la app de WhatsApp.') }}</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-link"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('1D. Entrar a "Dispositivos vinculados"') }}</h6>
            <p><strong>{{ __('En Android:') }}</strong> {{ __('toca los') }} <strong>{{ __('3 puntos') }}</strong> {{ __('arriba a la derecha →') }} <span class="ws-tag">{{ __('Dispositivos vinculados') }}</span></p>
            <p><strong>{{ __('En iPhone:') }}</strong> {{ __('toca') }} <strong>{{ __('Configuración') }}</strong> {{ __('(abajo) →') }} <span class="ws-tag">{{ __('Dispositivos vinculados') }}</span></p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-camera"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('1E. Vincular dispositivo y escanear') }}</h6>
            <p>{{ __('Toca') }} <span class="ws-tag">{{ __('Vincular un dispositivo') }}</span>. {{ __('Si pide huella o cara, autentícate.') }}</p>
            <p class="text-muted">{{ __('Apunta la cámara del celular al código QR de la pantalla de tu computadora.') }}</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico is-done"><i class="fas fa-check"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('1F. Listo en 3 segundos') }}</h6>
            <p>{{ __('El estado pasa a') }} <span class="ws-tag">Active</span>. {{ __('Tu WhatsApp ya está conectado. Puedes cerrar el QR.') }}</p>
            <p class="text-muted">{{ __('Si no carga: refresca la página y vuelve a hacer clic en el ícono QR.') }}</p>
          </div>
        </div>

      </div>

      <div class="col-lg-5 mt-3 mt-lg-0">
        <div class="text-center mb-3">
          <img src="{{ asset('uploads/scan-demo.gif') }}" class="rounded shadow-sm" style="max-width:240px; width:100%;"
               alt="{{ __('Demo escaneo QR') }}"
               onerror="this.replaceWith(document.createTextNode(''))">
        </div>

        <div class="ws-note is-warn mb-3">
          <h6><i class="fas fa-wrench mr-1"></i>{{ __('¿Algo no funciona?') }}</h6>
          <ul class="mb-0" style="padding-left:1.1rem;">
            <li><strong>{{ __('QR no aparece:') }}</strong> {{ __('refresca la página y haz clic de nuevo en el ícono.') }}</li>
            <li><strong>{{ __('Dice "expirado":') }}</strong> {{ __('genera uno nuevo, tienes 30 seg para escanear.') }}</li>
            <li><strong>{{ __('No aparece "Dispositivos vinculados":') }}</strong> {{ __('actualiza tu WhatsApp a la última versión.') }}</li>
            <li><strong>{{ __('Pide huella/cara y no funciona:') }}</strong> {{ __('configura el bloqueo de WhatsApp en Configuración → Privacidad.') }}</li>
            <li><strong>{{ __('Se desconecta solo:') }}</strong> {{ __('mantén el celular con internet y no cierres WhatsApp ahí.') }}</li>
          </ul>
        </div>

        <div class="ws-note is-tip">
          <h6><i class="fas fa-lightbulb mr-1"></i>{{ __('Consejo') }}</h6>
          <p class="mb-0">{{ __('Si tu bot se desconecta, ve a "Salud de bots" en el menú lateral — desde ahí puedes re-escanear el QR en 1 clic sin perder reglas ni contactos.') }}</p>
        </div>
      </div>
    </div>

  </div>
</div>

<div class="ws-connector mb-2"></div>

{{-- ── PASO 2 ───────────────────────────────────────────────────────────────── --}}
<div class="ws-card mb-2">
  <div class="ws-step-head">
    <div class="ws-step-num">2</div>
    <div style="flex:1;">
      <h4>{{ __('Crear una App') }}</h4>
      <small>{{ __('La App genera las claves (appkey) que identifican tu integración') }}</small>
    </div>
    <a href="{{ route('user.apps.index') }}" class="ws-btn">
      <i class="fas fa-cube"></i>{{ __('Mis Apps') }}
    </a>
  </div>
  <div class="ws-card-body">
    <div class="row">
      <div class="col-lg-7">

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-cube"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('Paso 2.1') }}</h6>
            <p>{{ __('Ve a') }} <span class="ws-tag">{{ __('My Apps') }}</span> {{ __('en el menú lateral y haz clic en') }} <span class="ws-tag">+ Create App</span>.</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-mobile-alt"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('Paso 2.2') }}</h6>
            <p><span class="ws-tag">{{ __('Select Number') }}</span>: {{ __('elige el dispositivo WhatsApp que conectaste en el Paso 1. Los mensajes saldrán desde ese número.') }}</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-pen"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('Paso 2.3') }}</h6>
            <p><span class="ws-tag">{{ __('App Name') }}</span>: {{ __('ponle un nombre (ej. "Alertas GPS").') }}</p>
            <p><span class="ws-tag">{{ __('Website Link') }}</span>: {{ __('ingresa la URL de tu sistema GPS (ej.') }} <code class="key-code">https://gpssoftwarenumberone.com</code>).</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico is-done"><i class="fas fa-key"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('Paso 2.4 — Obtener las claves') }}</h6>
            <p>{{ __('Una vez creada la App, haz clic en') }} <span class="ws-tag">⋮ → REST API</span>. {{ __('Ahí encontrarás:') }}</p>
            <p><code class="key-code">appkey</code> — {{ __('identifica tu App') }}<br>
               <code class="key-code">authkey</code> — {{ __('identifica tu cuenta de usuario') }}</p>
          </div>
        </div>

      </div>

      <div class="col-lg-5 mt-3 mt-lg-0">
        <div class="ws-note">
          <h6><i class="fas fa-info-circle mr-1"></i>{{ __('¿Para qué sirve la App?') }}</h6>
          <p>{{ __('Cada App está ligada a un número WhatsApp y tiene sus propias claves API. Puedes crear una App por cada integración o cliente.') }}</p>
          <p class="mb-0">{{ __('Si usas WhatStar con tu software GPS, la App es el puente que conecta las alertas con tu WhatsApp.') }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="ws-connector mb-2"></div>

{{-- ── PASO 3 ───────────────────────────────────────────────────────────────── --}}
<div class="ws-card mb-4">
  <div class="ws-step-head">
    <div class="ws-step-num">3</div>
    <div style="flex:1;">
      <h4>{{ __('Configurar el número de WhatsApp destino') }}</h4>
      <small>{{ __('Define a qué número(s) llegan las alertas de tu software GPS') }}</small>
    </div>
  </div>
  <div class="ws-card-body">

    <div class="ws-callout is-good mb-4">
      <i class="fas fa-bolt"></i>
      <div>
        <strong>{{ __('Buenas noticias si usas nuestro software GPS.') }}</strong>
        {{ __('El sistema ya tiene tu cuenta vinculada y configura todo automáticamente. Solo genera el token y listo — sin copiar ni pegar nada.') }}
      </div>
    </div>

    <div class="row">
      <div class="col-lg-7">

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-broadcast-tower"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('Paso 3.1') }}</h6>
            <p>{{ __('En') }} <span class="ws-tag">{{ __('My Apps') }}</span> {{ __('haz clic en') }} <span class="ws-tag">⋮ → Conectar software GPS</span>. {{ __('Se abre la página de configuración del enlace con tu software GPS.') }}</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-magic"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('Paso 3.2 — Generar token') }}</h6>
            <p>{{ __('Haz clic en') }} <span class="ws-tag">{{ __('Generar token') }}</span>. {{ __('Con eso es suficiente.') }}</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico is-done"><i class="fas fa-check-double"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('¿Y las claves? Se configuran solas') }}</h6>
            <p>{{ __('Si accediste a WhatStar desde el botón de tu software GPS, el sistema ya tiene tu cuenta vinculada. Al generar el token,') }} <strong>{{ __('WhatStar envía automáticamente todas las credenciales al software GPS') }}</strong> {{ __('— no tienes que copiar ni pegar nada.') }}</p>
          </div>
        </div>

        <div class="ws-sub">
          <div class="ws-sub-ico"><i class="fas fa-phone"></i></div>
          <div class="ws-sub-body">
            <h6>{{ __('Paso 3.3 — Solo elige a quién llega la alerta') }}</h6>
            <p>{{ __('Lo único que configuras en el software GPS es') }} <strong>{{ __('el número de WhatsApp que recibirá cada alerta') }}</strong>. {{ __('Formato: código de país + número, sin espacios ni guiones.') }}</p>
          </div>
        </div>

        <div class="ws-callout is-info mt-3">
          <i class="fas fa-info-circle"></i>
          <div>
            <strong>{{ __('¿Usas un software GPS diferente?') }}</strong>
            {{ __('En ese caso sí necesitas copiar manualmente la') }} <strong>{{ __('URL del webhook') }}</strong> {{ __('y el') }} <strong>{{ __('token (outkey)') }}</strong> {{ __('que aparecen en la página "Conectar software GPS" y pegarlos en la sección de alertas de tu sistema.') }}
          </div>
        </div>

      </div>

      <div class="col-lg-5 mt-3 mt-lg-0">
        <div class="ws-note mb-3">
          <h6><i class="fas fa-phone-alt mr-1"></i>{{ __('Formato del número') }}</h6>
          <p>{{ __('Incluye el código de país sin el signo +.') }}</p>
          <div class="bg-white rounded p-3" style="font-family:monospace; border:1px solid var(--ws-line);">
            <code>573001234567</code> &nbsp;<span class="text-muted">(Colombia)</span><br>
            <code>521234567890</code> &nbsp;<span class="text-muted">(México)</span><br>
            <code>5491123456789</code> &nbsp;<span class="text-muted">(Argentina)</span>
          </div>
        </div>
        <div class="ws-note">
          <h6><i class="fas fa-users mr-1"></i>{{ __('Múltiples destinatarios') }}</h6>
          <p class="mb-0">{{ __('Envía la misma alerta a varios números separando con coma:') }}<br>
             <code class="key-code">573001234567,573009876543</code></p>
        </div>
      </div>
    </div>

    {{-- Ejemplo de alerta recibida --}}
    <div class="mt-4">
      <h5 class="font-weight-bold mb-2"><i class="fab fa-whatsapp mr-2" style="color:var(--ws-green);"></i>{{ __('Así llega la alerta al WhatsApp del destinatario') }}</h5>
      <p class="text-muted mb-3">{{ __('El encabezado del mensaje muestra el nombre que le diste a tu App al crearla — así tus clientes siempre ven el nombre de tu empresa, no el de ninguna plataforma.') }}</p>
      <div class="ws-chat">
        <strong>{{ __('Transportes Mi Empresa S.A.') }}</strong><br>
        <strong>{{ __('Unidad:') }}</strong> {{ __('Camión 01') }}<br>
        <strong>{{ __('Evento:') }}</strong> {{ __('Exceso de velocidad') }}<br>
        <strong>{{ __('Velocidad:') }}</strong> 120 km/h<br>
        <strong>{{ __('Dirección:') }}</strong> {{ __('Calle 80 #45-23, Bogotá') }}<br>
        <strong>{{ __('Mapa:') }}</strong> https://maps.google.com/?q=4.61,-74.08<br>
        <strong>{{ __('Hora:') }}</strong> {{ now()->format('Y-m-d H:i:s') }}
      </div>
    </div>

  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECCIÓN 2 — HERRAMIENTAS --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="ws-section mt-2">
  <div class="ws-section-ico"><i class="fas fa-th-large"></i></div>
  <div>
    <h3>{{ __('Herramientas de WhatStar') }}</h3>
    <small>{{ __('Todo lo que puedes hacer desde tu panel') }}</small>
  </div>
</div>

<div class="row">
  @php
    $tools = [
      ['icon'=>'fas fa-mobile-alt','title'=>__('My Devices — Mis dispositivos'),'desc'=>__('Gestiona los teléfonos WhatsApp vinculados. Cada dispositivo es un número desde el que puedes enviar mensajes. Puedes tener varios según tu plan.'),'url'=>route('user.device.index'),'cta'=>__('Ir a Dispositivos')],
      ['icon'=>'fas fa-cube','title'=>__('My Apps — API de mensajes'),'desc'=>__('Cada App tiene una appkey propia. Con ella tu sistema externo (GPS, CRM, etc.) puede enviar mensajes vía REST API. También es el punto de configuración para alertas de tu software GPS.'),'url'=>route('user.apps.index'),'cta'=>__('Ir a Apps')],
      ['icon'=>'fas fa-file-alt','title'=>__('Templates — Plantillas de mensaje'),'desc'=>__('Crea mensajes reutilizables con variables dinámicas (ej. {nombre}, {placa}). Úsalas en envíos masivos o API para personalizar cada mensaje automáticamente.'),'url'=>url('/user/template'),'cta'=>__('Ir a Templates')],
      ['icon'=>'fas fa-paper-plane','title'=>__('Single Send — Envío individual'),'desc'=>__('Envía un mensaje de texto (o con archivo adjunto) a un número específico de forma inmediata. Ideal para pruebas o mensajes puntuales sin pasar por la API.'),'url'=>url('/user/sent-text-message'),'cta'=>__('Enviar mensaje')],
      ['icon'=>'fas fa-robot','title'=>__('Chatbot — Respuestas automáticas'),'desc'=>__('Define reglas de respuesta automática basadas en palabras clave. Cuando alguien te escribe con una frase reconocida, el bot responde al instante — ideal para FAQs, menús o captación de leads.'),'url'=>route('user.chatbot.index'),'cta'=>__('Ir a Chatbot')],
      ['icon'=>'fas fa-address-book','title'=>__('Contact Book — Libreta de contactos'),'desc'=>__('Importa tu lista de contactos (CSV/Excel) y organízalos en grupos. Los grupos se usan en los envíos masivos para segmentar tu audiencia.'),'url'=>url('/user/contact'),'cta'=>__('Ir a Contactos')],
      ['icon'=>'fas fa-rocket','title'=>__('Send Bulk Message — Envío masivo'),'desc'=>__('Envía un mismo mensaje a todos los contactos de un grupo. Puedes adjuntar imágenes, PDFs o documentos. Incluye delay inteligente entre mensajes para evitar bloqueos.'),'url'=>url('/user/bulk-message'),'cta'=>__('Ir a Masivos')],
      ['icon'=>'fas fa-calendar-alt','title'=>__('Scheduled Message — Mensajes programados'),'desc'=>__('Programa mensajes para que se envíen en una fecha y hora específica. Útil para recordatorios, campañas o notificaciones recurrentes.'),'url'=>url('/user/schedule-message'),'cta'=>__('Ir a Programados')],
      ['icon'=>'fas fa-list-alt','title'=>__('Message Log — Registro de mensajes'),'desc'=>__('Historial completo de todos los mensajes enviados: fecha, número destino, estado (enviado/fallido) y contenido. Permite depurar problemas de entrega.'),'url'=>url('/user/logs'),'cta'=>__('Ver Logs')],
      ['icon'=>'fas fa-project-diagram','title'=>__('Webhook Logs — Registro de webhooks'),'desc'=>__('Registro de todas las llamadas recibidas por la API (alertas GPS, integraciones externas). Muestra el payload recibido y el resultado del procesamiento.'),'url'=>url('/user/webhooks'),'cta'=>__('Ver Webhooks')],
    ];
  @endphp

  @foreach($tools as $t)
  <div class="col-lg-6 mb-4">
    <div class="ws-tool d-flex flex-column">
      <div class="ws-tool-ico"><i class="{{ $t['icon'] }}"></i></div>
      <h5>{{ $t['title'] }}</h5>
      <p style="flex:1;">{{ $t['desc'] }}</p>
      <a href="{{ $t['url'] }}" class="ws-btn ws-btn-ghost align-self-start">
        <i class="fas fa-arrow-right"></i>{{ $t['cta'] }}
      </a>
    </div>
  </div>
  @endforeach
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SECCIÓN 3 — HERRAMIENTAS AVANZADAS --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="ws-section mt-2">
  <div class="ws-section-ico"><i class="fas fa-star"></i></div>
  <div>
    <h3>{{ __('Herramientas avanzadas') }}</h3>
    <small>{{ __('Funciones premium para llevar tu operación al siguiente nivel') }}</small>
  </div>
</div>

<div class="row">
  @php
    $advanced = [
      ['icon'=>'fas fa-magic','title'=>__('Plantillas por industria'),'desc'=>__('Elige tu industria (óptica, farmacia, restaurante, taller, hotel, etc.) y carga 7-10 reglas pre-armadas en 1 clic. Empiezas a vender en 5 min.'),'url'=>url('/user/chatbot-industries'),'cta'=>__('Ver plantillas')],
      ['icon'=>'fas fa-bell','title'=>__('Alertas multi-canal'),'desc'=>__('Personaliza el texto de las alertas (exceso velocidad, SOS, batería baja, etc.) por tipo de evento, idioma y canal. WhatsApp + Telegram + SMS + Email.'),'url'=>url('/user/alerts'),'cta'=>__('Configurar alertas')],
      ['icon'=>'fas fa-heartbeat','title'=>__('Salud de bots'),'desc'=>__('Monitorea en tiempo real el estado de conexión de tus bots. Si se desconecta uno, reconéctalo con el botón (te lleva a la página segura de QR, sin perder reglas ni contactos).'),'url'=>url('/user/health'),'cta'=>__('Ver estado')],
      ['icon'=>'fas fa-bell','title'=>__('Aviso de desconexión'),'desc'=>__('En "Salud de bots" puedes dejar un número personal. Si un bot pierde la conexión, te avisamos automáticamente por WhatsApp (y por correo) para que lo reconectes enseguida.'),'url'=>url('/user/health'),'cta'=>__('Configurar aviso')],
      ['icon'=>'fas fa-shield-alt','title'=>__('Evitar desconexión / Plan B'),'desc'=>__('Usa el botón verde "Evitar desconexión" en la barra superior: verás consejos para que tu teléfono no se caiga y la opción del Plan B (número oficial de Meta que nunca se desconecta).'),'url'=>url('/user/health'),'cta'=>__('Ver consejos')],
      ['icon'=>'fas fa-brain','title'=>__('Cerebro IA del bot'),'desc'=>__('Personaliza la IA por bot: nombre del negocio, contexto, prompt avanzado. Cada bot responde como si fuera de TU negocio, no de uno genérico.'),'url'=>url('/user/ai-config'),'cta'=>__('Configurar IA')],
      ['icon'=>'fas fa-sitemap','title'=>__('Constructor visual'),'desc'=>__('Vista gráfica tipo flowchart de todas tus reglas de chatbot. Útil para entender flujos largos.'),'url'=>url('/user/chatbot-flow'),'cta'=>__('Ver flujo')],
    ];
  @endphp

  @foreach($advanced as $t)
  <div class="col-lg-6 mb-4">
    <div class="ws-tool d-flex flex-column">
      <div class="ws-tool-ico"><i class="{{ $t['icon'] }}"></i></div>
      <h5>{{ $t['title'] }}<span class="ws-badge-new">{{ __('NUEVO') }}</span></h5>
      <p style="flex:1;">{{ $t['desc'] }}</p>
      <a href="{{ $t['url'] }}" class="ws-btn ws-btn-ghost align-self-start">
        <i class="fas fa-arrow-right"></i>{{ $t['cta'] }}
      </a>
    </div>
  </div>
  @endforeach
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- CTA SOPORTE --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="row mt-2">
  <div class="col-12">
    <div class="ws-hero d-flex align-items-center justify-content-between flex-wrap" style="gap:1rem;">
      <div>
        <h2 style="font-size:1.5rem; margin-bottom:.3rem;">{{ __('¿Necesitas ayuda?') }}</h2>
        <p>{{ __('Abre un ticket de soporte y te respondemos a la brevedad.') }}</p>
      </div>
      <a href="{{ route('user.support.create') }}" class="ws-btn ws-btn-light">
        <i class="fas fa-headset"></i>{{ __('Abrir ticket de soporte') }}
      </a>
    </div>
  </div>
</div>

</div>{{-- /.manual-page --}}
@endsection
