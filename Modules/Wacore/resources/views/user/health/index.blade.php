@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Salud de bots')])
@endsection
@push('css')
<style>
/* ── Página "Salud de bots" — diseño premium, colores uniformes ───────────── */
.hb-page{ color:#1f2a37; }
.hb-head{
   background:#fff; border:1px solid #e6ecf4; border-radius:16px;
   box-shadow:0 4px 20px rgba(20,45,80,.06); padding:1.4rem 1.6rem;
   display:flex; align-items:center; gap:1rem; flex-wrap:wrap;
}
.hb-head-ico{
   width:52px; height:52px; border-radius:13px; flex-shrink:0;
   background:#eaf1fb; color:#1a4b8c;
   display:flex; align-items:center; justify-content:center; font-size:1.5rem;
}
.hb-head h4{ margin:0; font-weight:700; color:#12365f; font-size:1.4rem; }
.hb-head p{ margin:0; color:#5b6673; }
.hb-refresh{
   margin-left:auto; display:inline-flex; align-items:center; gap:.5rem;
   background:#1a4b8c; color:#fff; border:none; border-radius:10px;
   padding:.55rem 1.1rem; font-weight:600; cursor:pointer; transition:background .15s;
}
.hb-refresh:hover{ background:#12365f; color:#fff; }

/* Tarjeta de dispositivo — todas iguales (sin arcoíris) */
.hb-card{
   background:#fff; border:1px solid #e6ecf4; border-radius:16px;
   box-shadow:0 4px 20px rgba(20,45,80,.06); padding:1.4rem; height:100%;
   transition:box-shadow .2s, transform .2s;
}
.hb-card:hover{ box-shadow:0 10px 30px rgba(20,45,80,.1); transform:translateY(-2px); }
.hb-ico{
   width:46px; height:46px; border-radius:12px; flex-shrink:0;
   background:#eaf1fb; color:#1a4b8c;
   display:flex; align-items:center; justify-content:center; font-size:1.25rem;
}
.hb-name{ font-weight:700; color:#12365f; margin:0; font-size:1.12rem; line-height:1.2; }
.hb-phone{ color:#5b6673; font-size:.96rem; }
.hb-meta{ color:#8a94a2; font-size:.92rem; }

/* Píldora de estado — centrada, uniforme, colores semánticos suaves */
.hb-status{
   display:inline-flex; align-items:center; gap:.45rem; white-space:nowrap;
   padding:.34rem .75rem; border-radius:999px; font-weight:700; font-size:.9rem; line-height:1;
}
.hb-status .dot{ width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.hb-status.is-healthy{ background:#e7f6ee; color:#12864a; }      .hb-status.is-healthy .dot{ background:#1f9d57; }
.hb-status.is-connecting{ background:#fff3df; color:#9a5b00; }   .hb-status.is-connecting .dot{ background:#e08a00; }
.hb-status.is-disconnected{ background:#fdecec; color:#c0392b; } .hb-status.is-disconnected .dot{ background:#e04b3a; }
.hb-status.is-inactive{ background:#eef1f5; color:#5b6673; }     .hb-status.is-inactive .dot{ background:#9aa4b0; }

/* Botón reconectar — consistente (contorno navy), nunca naranja */
.hb-btn{
   display:inline-flex; align-items:center; justify-content:center; gap:.5rem; width:100%;
   background:#fff; color:#1a4b8c; border:1px solid #cfe0f4; border-radius:10px;
   padding:.6rem; font-weight:600; text-decoration:none; cursor:pointer; transition:background .15s, border-color .15s;
}
.hb-btn:hover{ background:#eaf1fb; color:#12365f; border-color:#b7d0ee; }

.hb-alert-col{ border-left:1px dashed #e6ecf4; padding-left:1.1rem; }
.hb-alert-label{ display:block; font-weight:700; color:#12365f; font-size:.98rem; margin-bottom:.4rem; }
.hb-alert-input{ border:1px solid #cfe0f4 !important; border-radius:9px 0 0 9px; font-size:.95rem; }
.hb-alert-save{ background:#1a4b8c; color:#fff; font-weight:600; border:none; border-radius:0 9px 9px 0; font-size:.92rem; }
.hb-alert-save:hover{ background:#12365f; color:#fff; }
.hb-alert-hint{ display:block; color:#8a94a2; font-size:.85rem; margin-top:.35rem; line-height:1.4; }
@media (max-width:767px){ .hb-alert-col{ border-left:none; border-top:1px dashed #e6ecf4; padding-left:0; padding-top:1rem; margin-top:.3rem; } }

#qrModal .modal-content{ border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(18,54,95,.3); }
#qrModal .modal-header{ background:#f7fafd; border-bottom:1px solid #e6ecf4; }
#qrModal .modal-title{ color:#12365f; font-weight:700; }
</style>
@endpush
@section('content')
<div class="hb-page">

<div class="row">
 <div class="col-12">

 {{-- Encabezado --}}
 <div class="hb-head mb-3">
 <div class="hb-head-ico"><i class="fas fa-heartbeat"></i></div>
 <div>
 <h4>{{ __('Salud de tus bots') }}</h4>
 <p>{{ __('Monitorea la conexión de tus dispositivos WhatsApp en tiempo real. Si alguno se cae, puedes re-escanear el QR desde aquí.') }}</p>
 </div>
 <button class="hb-refresh" onclick="refresh()">
 <i class="fas fa-sync"></i> {{ __('Refrescar') }}
 </button>
 </div>

 {{-- Aviso contextual: aparece (vía JS) solo cuando hay un bot desconectado --}}
 <div id="planbAlert" class="mb-3" style="display:none;">
 <div style="display:flex; gap:1rem; align-items:center; background:linear-gradient(120deg,#8a2432 0%,#b23a48 100%); color:#fff; border-radius:14px; padding:1rem 1.3rem; box-shadow:0 6px 20px rgba(150,40,55,.25);">
 <i class="fas fa-exclamation-triangle" style="font-size:1.7rem; flex-shrink:0;"></i>
 <div style="flex:1;">
 <strong style="font-size:1.12rem;">{{ __('Tienes un bot desconectado') }}</strong><br>
 <span>{{ __('Con el Plan B (WhatsApp API Oficial de Meta) tu número nunca se desconecta. Revisa la información más abajo.') }}</span>
 </div>
 <a href="#planbInfo" style="background:#fff; color:#b23a48; font-weight:700; border-radius:9px; padding:.55rem 1.05rem; text-decoration:none; white-space:nowrap;">{{ __('Ver Plan B') }}</a>
 </div>
 </div>

 <div class="row" id="devicesGrid">
 @foreach($devices as $d)
 <div class="col-lg-6 mb-4" data-device-id="{{ $d->id }}">
 <div class="hb-card">
 <div class="d-flex align-items-center mb-3">
 <div class="hb-ico mr-3"><i class="fab fa-whatsapp"></i></div>
 <div class="flex-grow-1">
 <h6 class="hb-name">{{ $d->name }}</h6>
 <span class="hb-phone">{{ $d->phone ?: '—' }}</span>
 </div>
 <div data-indicator>
 <span class="hb-status is-inactive"><span class="dot"></span>{{ __('Verificando') }}</span>
 </div>
 </div>
 <div class="hb-meta mb-3" data-meta>{{ __('Esperando estado...') }}</div>

 <div class="row">
 <div class="col-md-5 mb-3 mb-md-0 d-flex flex-column justify-content-start">
 <a href="{{ route('user.device.scan', $d->uuid) }}" class="hb-btn"
 onclick="return confirm('{{ __('Vas a abrir la página de reconexión por QR. Si el bot ya está conectado, no le pasa nada. ¿Continuar?') }}');">
 <i class="fas fa-qrcode"></i> {{ __('Reconectar / nuevo QR') }}
 </a>
 </div>
 <div class="col-md-7 hb-alert-col">
 <label class="hb-alert-label"><i class="fas fa-bell mr-1"></i>{{ __('Avísame si este bot se desconecta') }}</label>
 <div class="input-group">
 <input type="text" class="form-control hb-alert-input" data-device-id="{{ $d->id }}"
 placeholder="{{ __('Ejemplo: +57 300 123 4567') }}" value="{{ $d->disconnect_alert_number }}">
 <div class="input-group-append">
 <button class="btn hb-alert-save" type="button" onclick="saveAlertNumber({{ $d->id }}, this)">{{ __('Guardar') }}</button>
 </div>
 </div>
 <small class="hb-alert-hint">
 {{ __('Escribe un número de celular (móvil) que tenga WhatsApp, empezando por el código del país.') }}
 {{ __('Puedes ponerlo con el signo + o sin él; también puedes usar espacios.') }}<br>
 {{ __('Ejemplos válidos: +57 300 123 4567 · 573001234567 · +52 55 1234 5678') }}<br>
 {{ __('No uses teléfonos fijos. Aquí recibirás el aviso si el bot se desconecta. Déjalo vacío para desactivar.') }}
 </small>
 </div>
 </div>

 </div>
 </div>
 @endforeach
 @if($devices->isEmpty())
 <div class="col-12">
 <div class="alert alert-info">{{ __('No tienes dispositivos. Crea uno en "My Devices".') }}</div>
 </div>
 @endif
 </div>

 {{-- Plan B — WhatsApp API Oficial de Meta (mismo parcial que el modal de "Evitar desconexión") --}}
 <div id="planbInfo" class="mt-3">
 @include('partials.planb')
 </div>

 </div>
</div>

</div>{{-- /.hb-page --}}

@push('js')
<script>
let pollTimer = null;
let currentDeviceId = null;

function badgeFor(health) {
 const map = {
 healthy:      ['is-healthy', @json(__('Conectado'))],
 connecting:   ['is-connecting', @json(__('Conectando'))],
 disconnected: ['is-disconnected', @json(__('Desconectado'))],
 inactive:     ['is-inactive', @json(__('Inactivo'))],
 };
 const [cls, txt] = map[health] || ['is-inactive', health];
 return `<span class="hb-status ${cls}"><span class="dot"></span>${txt}</span>`;
}

function refresh() {
 fetch('{{ route("user.health.statuses") }}').then(r => r.json()).then(j => {
 (j.devices || []).forEach(d => {
 const card = document.querySelector(`[data-device-id="${d.id}"]`);
 if (!card) return;
 card.querySelector('[data-indicator]').innerHTML = badgeFor(d.health);
 const live = d.live_status || '—';
 card.querySelector('[data-meta]').innerHTML = `<strong>Estado en vivo:</strong> ${live}`;
 });
 // Aviso contextual del Plan B: visible solo si hay algún bot desconectado
 const disconnected = (j.devices || []).filter(d => d.health === 'disconnected').length;
 const alertBox = document.getElementById('planbAlert');
 if (alertBox) { alertBox.style.display = disconnected > 0 ? 'block' : 'none'; }
 });
}

// El botón "Reconectar" ahora lleva a la página segura de QR de "Mis Dispositivos"
// (route user.device.scan), que verifica el estado antes y NO borra la sesión.
// Por eso se eliminó el flujo destructivo openReconnect/reconnect que sí desconectaba.

function saveAlertNumber(id, btn) {
 var input = document.querySelector('.hb-alert-input[data-device-id="' + id + '"]');
 if (!input) return;
 var number = input.value.trim();
 var orig = btn.textContent;
 btn.disabled = true; btn.textContent = @json(__('Guardando...'));
 fetch('/user/health/alert-number/' + id, {
 method: 'POST',
 headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
 body: JSON.stringify({ number: number }),
 }).then(r => r.json()).then(j => {
 btn.disabled = false;
 if (j.success) {
 btn.textContent = @json(__('Guardado'));
 setTimeout(function(){ btn.textContent = orig; }, 2000);
 } else {
 btn.textContent = orig;
 alert(j.error || @json(__('No se pudo guardar el número.')));
 }
 }).catch(function(e){ btn.disabled = false; btn.textContent = orig; alert(e.message); });
}

refresh();
setInterval(refresh, 12000);
</script>
@endpush
@endsection
