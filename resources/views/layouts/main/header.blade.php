<style>
/* Botón "Manual" destacado en la barra superior (junto a la bandera de idioma) */
.ws-manual-btn{
   background:linear-gradient(135deg,#7c3aed 0%,#6d28d9 100%);
   color:#fff !important; font-weight:600; font-size:.95rem;
   border-radius:22px; padding:.42rem 1rem; text-decoration:none;
   box-shadow:0 4px 14px rgba(124,58,237,.35);
   transition:transform .15s, box-shadow .15s, filter .15s; white-space:nowrap;
}
.ws-manual-btn:hover{
   color:#fff !important; filter:brightness(1.06);
   transform:translateY(-1px); box-shadow:0 6px 18px rgba(124,58,237,.45);
}
.ws-manual-btn i{ font-size:1rem; }

/* Botón "Evitar desconexión" (verde) — abre la ventana de consejos */
.ws-tips-btn{
   background:linear-gradient(135deg,#1f9d57 0%,#13733f 100%);
   color:#fff !important; font-weight:600; font-size:.95rem;
   border-radius:22px; padding:.42rem 1rem; text-decoration:none;
   box-shadow:0 4px 14px rgba(31,157,87,.35);
   transition:transform .15s, box-shadow .15s, filter .15s; white-space:nowrap;
}
.ws-tips-btn:hover{
   color:#fff !important; filter:brightness(1.06);
   transform:translateY(-1px); box-shadow:0 6px 18px rgba(31,157,87,.45);
}
.ws-tips-btn i{ font-size:1rem; }

/* Ventana de consejos anti-desconexión */
#wsTipsModal .modal-content{ border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(18,54,95,.3); }
#wsTipsModal .ws-tips-head{
   background:linear-gradient(120deg,#12365f 0%,#1a4b8c 70%,#2660b0 100%);
   color:#fff; padding:1.4rem 1.6rem; display:flex; align-items:center; gap:1rem;
}
#wsTipsModal .ws-tips-head .ico{
   width:52px; height:52px; border-radius:13px; flex-shrink:0;
   background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.28);
   display:flex; align-items:center; justify-content:center; font-size:1.5rem;
}
#wsTipsModal .ws-tips-head h5{ margin:0; font-weight:700; color:#fff; font-size:1.35rem; }
#wsTipsModal .ws-tips-head p{ margin:0; color:#dce8f7; font-size:.98rem; }
#wsTipsModal .ws-tips-head .close{ color:#fff; opacity:.9; text-shadow:none; font-size:1.6rem; margin-left:auto; }
#wsTipsModal .modal-body{ padding:1.5rem 1.6rem; background:#fff; }
#wsTipsModal .ws-tip{
   display:flex; gap:1rem; align-items:flex-start;
   padding:.85rem 0; border-bottom:1px solid #eef2f7;
}
#wsTipsModal .ws-tip:last-child{ border-bottom:none; }
#wsTipsModal .ws-tip .n{
   width:34px; height:34px; border-radius:9px; flex-shrink:0;
   background:#eaf1fb; color:#1a4b8c; font-weight:700;
   display:flex; align-items:center; justify-content:center; font-size:1rem;
}
#wsTipsModal .ws-tip h6{ margin:0 0 .15rem; font-weight:700; color:#1f2a37; font-size:1.06rem; }
#wsTipsModal .ws-tip p{ margin:0; color:#5b6673; font-size:1rem; line-height:1.6; }
#wsTipsModal .ws-tips-key{
   background:#fff6ec; border:1px solid #f6e2c6; color:#7a5218;
   border-radius:12px; padding:.9rem 1.1rem; margin-bottom:1.1rem;
   font-size:1.02rem; line-height:1.6;
}
#wsTipsModal .modal-body{ max-height:74vh; overflow-y:auto; }

/* Sección Plan B — WhatsApp API Oficial de Meta */
.ws-upsell{ margin-top:1.4rem; border:1px solid #dfe7f1; border-radius:14px; overflow:hidden; }
.ws-upsell-head{ display:flex; gap:.9rem; align-items:center; padding:1.1rem 1.2rem;
   background:linear-gradient(120deg,#0f2f52 0%,#1a4b8c 100%); color:#fff; }
.ws-upsell-head .ico{ width:46px; height:46px; border-radius:12px; flex-shrink:0;
   background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.28);
   display:flex; align-items:center; justify-content:center; font-size:1.35rem; }
.ws-upsell-head h6{ margin:0; color:#fff; font-weight:700; font-size:1.18rem; }
.ws-plan-now{ display:inline-block; margin-top:.25rem; background:#ffd54a; color:#5a4500;
   font-weight:700; font-size:.8rem; letter-spacing:.3px; padding:.14rem .6rem; border-radius:20px; }
.ws-upsell-lead{ padding:1rem 1.2rem 0; margin:0; color:#1f2a37; }
.ws-upsell-list{ list-style:none; padding:.65rem 1.2rem 0; margin:0; }
.ws-upsell-list li{ display:flex; gap:.6rem; align-items:flex-start; margin-bottom:.5rem; color:#2b3445; font-size:1.02rem; }
.ws-upsell-list li i{ color:#1f9d57; margin-top:.22rem; flex-shrink:0; }
.ws-price-title{ font-weight:700; color:#12365f; padding:.9rem 1.2rem .35rem; font-size:1.06rem; }
.ws-price-grid{ display:grid; grid-template-columns:1fr 1fr; gap:.45rem .8rem; padding:0 1.2rem; }
.ws-price-grid > div{ display:flex; justify-content:space-between; gap:.5rem; align-items:center;
   padding:.5rem .75rem; background:#f5f8fc; border:1px solid #e6ecf4; border-radius:9px; font-size:.98rem; }
.ws-price-grid > div.wide{ grid-column:1 / -1; }
.ws-price-grid b{ color:#12365f; white-space:nowrap; }
.ws-upsell-example{ display:flex; gap:.6rem; align-items:flex-start; margin:1rem 1.2rem 0;
   padding:.85rem 1rem; background:#eef6ff; border:1px solid #d3e5fb; border-radius:10px; color:#183b63; font-size:1rem; }
.ws-upsell-example i{ margin-top:.2rem; flex-shrink:0; }
.ws-upsell-cta{ display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
   padding:1.1rem 1.2rem; margin-top:1rem; background:#fafcff; border-top:1px solid #e6ecf4; }
.ws-upsell-cta span{ color:#2b3445; font-weight:600; }
.ws-cta-btn{ background:linear-gradient(135deg,#1f9d57 0%,#13733f 100%); color:#fff !important;
   font-weight:600; border-radius:10px; padding:.55rem 1.15rem; text-decoration:none; white-space:nowrap;
   box-shadow:0 4px 14px rgba(31,157,87,.3); transition:filter .15s; }
.ws-cta-btn:hover{ filter:brightness(1.07); color:#fff !important; }
@media (max-width:576px){ .ws-price-grid{ grid-template-columns:1fr; } }

/* Tarjetas de planes del Plan B */
.ws-plans{ display:grid; grid-template-columns:repeat(3,1fr); gap:.8rem; padding:.5rem 1.2rem 0; align-items:stretch; }
.ws-plan{ border:1px solid #e6ecf4; border-radius:13px; padding:1.1rem 1rem; background:#fff; display:flex; flex-direction:column; }
.ws-plan.is-featured{ border-color:#1a4b8c; box-shadow:0 8px 22px rgba(26,75,140,.16); }
.ws-plan-tag{ align-self:flex-start; background:#1a4b8c; color:#fff; font-size:.7rem; font-weight:700;
   letter-spacing:.3px; padding:.12rem .6rem; border-radius:20px; margin-bottom:.5rem; }
.ws-plan h5{ font-size:1.08rem; font-weight:700; color:#12365f; margin:0 0 .25rem; }
.ws-plan .price{ font-size:1.7rem; font-weight:800; color:#1a4b8c; line-height:1; margin-bottom:.2rem; }
.ws-plan .price small{ font-size:.82rem; color:#5b6673; font-weight:600; }
.ws-plan .cap{ font-size:.9rem; color:#5b6673; margin-bottom:.6rem; }
.ws-plan ul{ list-style:none; padding:0; margin:0; flex:1; }
.ws-plan li{ display:flex; gap:.45rem; align-items:flex-start; font-size:.92rem; color:#2b3445; margin-bottom:.42rem; }
.ws-plan li i{ color:#1f9d57; margin-top:.22rem; font-size:.82rem; flex-shrink:0; }
.ws-plan-note{ padding:.7rem 1.2rem 0; color:#5b6673; font-size:.95rem; }
.ws-country-wrap{ padding:.7rem 1.2rem 0; }
.ws-country-wrap label{ font-weight:700; color:#12365f; display:block; margin-bottom:.35rem; font-size:1rem; }
.ws-country{ width:100%; max-width:360px; padding:.55rem .8rem; border:1px solid #cfe0f4; border-radius:10px; font-size:1rem; color:#1f2a37; background:#fff; }
.ws-rate-line{ padding:.55rem 1.2rem 0; color:#5b6673; font-size:.95rem; }
.ws-rate-line strong{ color:#12365f; }
.ws-meta-note{ padding:.6rem 1.2rem 0; color:#8a94a2; font-size:.9rem; font-style:italic; line-height:1.55; }
@media (max-width:768px){ .ws-plans{ grid-template-columns:1fr; } }
</style>
<nav class="navbar navbar-top navbar-expand navbar-light bg-secondary border-bottom">
   <div class="container-fluid">
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
         <!-- Search form -->
       
         <!-- Navbar links -->
         <ul class="navbar-nav align-items-center ml-md-auto">
            <li class="nav-item d-xl-none">
               <!-- Sidenav toggler -->
               <div class="pr-3 sidenav-toggler sidenav-toggler-light" data-action="sidenav-pin" data-target="#sidenav-main">
                  <div class="sidenav-toggler-inner">
                     <i class="sidenav-toggler-line"></i>
                     <i class="sidenav-toggler-line"></i>
                     <i class="sidenav-toggler-line"></i>
                  </div>
               </div>
            </li>
            @if(Request::is('user/*'))
            <li class="nav-item dropdown notifications-icon none notifications-area">
               <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="ni ni-bell-55"></i>
               </a>
               <div class="dropdown-menu dropdown-menu-xl dropdown-menu-right py-0 overflow-hidden">
                  <!-- Dropdown header -->
                  <div class="px-3 py-3">
                     <h6 class="text-sm text-muted m-0">{{ __('You have') }} <strong class="text-primary notification-count">0</strong> {{ __('notifications.') }}</h6>
                  </div>
                  <!-- List group -->
                  <div class="list-group list-group-flush notifications-list">
                     

                  </div>
                  <!-- View all -->
                 
               </div>
            </li>

            <li class="nav-item dropdown">
               <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="ni ni-ungroup"></i>
               </a>
               <div class="dropdown-menu dropdown-menu-lg dropdown-menu-dark bg-default dropdown-menu-right">
                  <div class="row shortcuts px-4">
                     <a href="{{ url('/user/device') }}" class="col-4 shortcut-item">
                        <span class="shortcut-media avatar rounded-circle bg-gradient-red">
                           <i class="fi-rs-sensor-on"></i>
                        </span>
                        <small>{{ __('Devices') }}</small>
                     </a>
                     <a href="{{ url('/user/sent-text-message') }}" class="col-4 shortcut-item">
                        <span class="shortcut-media avatar rounded-circle bg-gradient-orange">
                           <i class="fi fi-rs-paper-plane"></i>
                        </span>
                        <small>{{ __('Single Send') }}</small>
                     </a>
                     <a href="{{ url('/user/bulk-message') }}" class="col-4 shortcut-item">
                        <span class="shortcut-media avatar rounded-circle bg-gradient-purple">
                           <i class="fi fi-rs-rocket-lunch"></i>
                        </span>
                        <small>{{ __('Bulk Send') }}</small>
                     </a>
                     <a href="{{ __('/user/chatbot') }}" class="col-4 shortcut-item">
                        <span class="shortcut-media avatar rounded-circle bg-gradient-info">
                           <i class="fas fa-robot"></i>
                        </span>
                        <small>{{ __('Chatbot') }}</small>
                     </a>                  
                     <a href="{{ url('/user/contact') }}" class="col-4 shortcut-item">
                        <span class="shortcut-media avatar rounded-circle bg-gradient-yellow">
                           <i class="fi  fi-rs-address-book"></i>
                        </span>
                        <small>{{ __('Contacts') }}</small>
                     </a>
                     <a href="{{ url('/user/logs') }}" class="col-4 shortcut-item">
                        <span class="shortcut-media avatar rounded-circle bg-gradient-green">
                           <i class="ni ni-books"></i>
                        </span>
                        <small>{{ __('Reports') }}</small>
                     </a>
                  </div>
               </div>
            </li>
             @endif
         </ul>

         <ul class="navbar-nav align-items-center ml-auto ml-md-0">
            @if(Request::is('user/*'))
            <li class="nav-item d-flex align-items-center mr-2">
               <a href="#" data-toggle="modal" data-target="#wsTipsModal"
                  class="d-inline-flex align-items-center ws-tips-btn"
                  title="{{ __('Consejos para evitar desconexión') }}">
                  <i class="fas fa-shield-alt"></i>
                  <span class="ml-2 d-none d-md-inline">{{ __('Evitar desconexión') }}</span>
               </a>
            </li>
            <li class="nav-item d-flex align-items-center mr-2">
               <a href="{{ route('user.manual.index') }}"
                  class="d-inline-flex align-items-center ws-manual-btn"
                  title="{{ __('Manual') }}">
                  <i class="fas fa-book-open"></i>
                  <span class="ml-2 d-none d-sm-inline">{{ __('Manual') }}</span>
               </a>
            </li>
            @endif
            @php $currentLocale = Session::get('locale', config('app.locale', 'en')); @endphp
            <li class="nav-item dropdown">
               <a class="nav-link px-2" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __('Change Language') }}">
                  <img src="{{ asset('assets/img/icons/flags/'.strtoupper($currentLocale == 'en' ? 'GB' : ($currentLocale == 'es' ? 'ES' : 'BR')).'.png') }}"
                       alt="{{ $currentLocale }}" style="width:20px;height:15px;border-radius:2px;">
               </a>
               <div class="dropdown-menu dropdown-menu-right py-1" style="min-width:130px;">
                  @foreach(get_option('languages', true) ?? [] as $code => $name)
                  <a href="{{ url('/local/'.$code) }}" class="dropdown-item d-flex align-items-center gap-2 py-1 {{ $currentLocale == $code ? 'active' : '' }}">
                     <img src="{{ asset('assets/img/icons/flags/'.strtoupper($code == 'en' ? 'GB' : ($code == 'es' ? 'ES' : 'BR')).'.png') }}"
                          alt="{{ $code }}" style="width:20px;height:15px;border-radius:2px;">
                     <span class="ml-2 text-sm">{{ $name }}</span>
                  </a>
                  @endforeach
               </div>
            </li>
            <li class="nav-item dropdown">
               <a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <div class="media align-items-center">
                     <span class="avatar avatar-sm rounded-circle">
                        <img alt="Image placeholder" src="{{ Auth::user()->avatar == null ? 'https://ui-avatars.com/api/?name='.Auth::user()->name : asset(Auth::user()->avatar) }}">
                     </span>
                     <div class="media-body ml-2 d-none d-lg-block">
                        <span class="mb-0 text-sm  font-weight-bold">{{ Auth::user()->name }}</span>
                     </div>
                  </div>
               </a>
               <div class="dropdown-menu dropdown-menu-right">
                  <div class="dropdown-header noti-title">
                     <h6 class="text-overflow m-0">{{ __('Welcome!') }}</h6>
                  </div>
                  <a href="{{ Request::is('user/*') ? url('/user/profile') : url('/admin/profile') }}" class="dropdown-item">
                     <i class="ni ni-single-02"></i>
                     <span>{{ __('My profile') }}</span>
                  </a>
                  @if(Request::is('user/*'))
                  <a href="{{ url('/user/subscription') }}" class="dropdown-item">
                     <i class="ni ni-settings-gear-65"></i>
                     <span>{{ __('Subscription') }}</span>
                  </a>
                  <a href="{{ url('/user/auth-key') }}" class="dropdown-item">
                     <i class="fas fa-code"></i>
                     <span>{{ __('Auth Key') }}</span>
                  </a>
                  @endif
                  <a href="{{ Request::is('user/*') ? url('/user/support') : url('/admin/support') }}" class="dropdown-item">
                     <i class="ni ni-support-16"></i>
                     <span>{{ __('Support') }}</span>
                  </a>
                  <div class="dropdown-divider"></div>
                  <a href="#!" class="dropdown-item logout-button">
                     <i class="ni ni-user-run"></i>
                     <span>{{ __('Logout') }}</span>
                  </a>
               </div>
            </li>
         </ul>
      </div>
   </div>
</nav>
<!-- Header -->
<!-- Header -->

@if(Request::is('user/*'))
{{-- Ventana de consejos para evitar que el teléfono se desconecte --}}
<div class="modal fade" id="wsTipsModal" tabindex="-1" role="dialog" aria-labelledby="wsTipsLabel" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content">
         <div class="ws-tips-head">
            <div class="ico"><i class="fas fa-shield-alt"></i></div>
            <div>
               <h5 id="wsTipsLabel">{{ __('Consejos para evitar la desconexión') }}</h5>
               <p>{{ __('Sigue estas recomendaciones y tu WhatsApp se mantendrá conectado sin interrupciones.') }}</p>
            </div>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
         </div>
         <div class="modal-body">

            <div class="ws-tips-key">
               <strong>{{ __('Lo más importante:') }}</strong>
               {{ __('el teléfono que escaneó el QR debe permanecer encendido, con internet y con WhatsApp abierto al menos una vez cada pocos días. WhatsApp desconecta los dispositivos vinculados si el teléfono principal pasa alrededor de 14 días sin conexión.') }}
            </div>

            <div class="ws-tip">
               <div class="n">1</div>
               <div>
                  <h6>{{ __('Mantén el teléfono con internet estable') }}</h6>
                  <p>{{ __('WiFi o datos móviles, pero que no se corte. Si el celular se queda sin internet por mucho tiempo, la sesión se cae.') }}</p>
               </div>
            </div>

            <div class="ws-tip">
               <div class="n">2</div>
               <div>
                  <h6>{{ __('No cierres la sesión de WhatsApp ni desinstales la app') }}</h6>
                  <p>{{ __('Tampoco borres los datos de WhatsApp. La vinculación depende de que la app siga instalada y con sesión activa en el teléfono.') }}</p>
               </div>
            </div>

            <div class="ws-tip">
               <div class="n">3</div>
               <div>
                  <h6>{{ __('Desactiva el ahorro de batería para WhatsApp') }}</h6>
                  <p>{{ __('En Android, la optimización de batería cierra WhatsApp en segundo plano. Entra en Ajustes → Batería → WhatsApp y elige "Sin restricciones" o "No optimizar".') }}</p>
               </div>
            </div>

            <div class="ws-tip">
               <div class="n">4</div>
               <div>
                  <h6>{{ __('Mantén WhatsApp actualizado') }}</h6>
                  <p>{{ __('Instala siempre la última versión desde la tienda de aplicaciones. Las versiones viejas pierden la conexión con más frecuencia.') }}</p>
               </div>
            </div>

            <div class="ws-tip">
               <div class="n">5</div>
               <div>
                  <h6>{{ __('No apagues el teléfono por largos periodos') }}</h6>
                  <p>{{ __('Si vas a apagarlo o dejarlo sin uso varios días, abre WhatsApp al volver para reactivar la conexión.') }}</p>
               </div>
            </div>

            <div class="ws-tip">
               <div class="n">6</div>
               <div>
                  <h6>{{ __('No ocupes todos los dispositivos vinculados') }}</h6>
                  <p>{{ __('WhatsApp permite un número limitado de dispositivos vinculados. Si vinculas otros servicios de más, puede expulsar la sesión de WhatStar.') }}</p>
               </div>
            </div>

            <div class="ws-tip">
               <div class="n">7</div>
               <div>
                  <h6>{{ __('Usa un teléfono dedicado si es posible') }}</h6>
                  <p>{{ __('Para operaciones importantes, lo ideal es un celular que quede siempre encendido, cargando y con internet — así la conexión nunca se interrumpe.') }}</p>
               </div>
            </div>

            <div class="ws-tip">
               <div class="n">8</div>
               <div>
                  <h6>{{ __('Si se desconecta, vuelve a conectar sin perder nada') }}</h6>
                  <p>{{ __('Puedes reconectar el número desde "Salud de bots" o desde "Mis Dispositivos" en el menú lateral: re-escaneas el QR en 1 clic. No pierdes reglas, contactos ni configuración.') }}</p>
               </div>
            </div>

            {{-- Plan B: WhatsApp API Oficial de Meta (parcial reutilizable) --}}
            @include('partials.planb')

         </div>
      </div>
   </div>
</div>

{{-- Calculadora de precios del Plan B por país. Inicializa TODOS los .ws-upsell
     de la página (funciona a la vez en el modal y en la sección de Salud de bots). --}}
<script>
(function () {
   var MARKUP = 1.0;         // mensajes incluidos al costo (tu ganancia va en la parte fija)
   var OVERAGE_MARKUP = 1.5; // alertas adicionales con 50% de margen para no perder
   function initUpsell(box) {
      var sel = box.querySelector('.ws-country');
      if (!sel) return;
      var rateLine    = box.querySelector('.ws-rate-line');
      var rateVal     = box.querySelector('.ws-rate-val');
      var overageLine = box.querySelector('.ws-overage-line');
      var overageVal  = box.querySelector('.ws-overage-val');
      function update() {
         var opt = sel.options[sel.selectedIndex];
         var rate = parseFloat(opt ? opt.getAttribute('data-rate') : '');
         box.querySelectorAll('.ws-plan').forEach(function (card) {
            var amt = card.querySelector('.amt');
            if (!amt) return;
            if (isNaN(rate)) { amt.textContent = '—'; return; }
            var platform = parseFloat(card.getAttribute('data-platform')) || 0;
            var included = parseFloat(card.getAttribute('data-included')) || 0;
            var price = Math.ceil(platform + included * rate * MARKUP);
            amt.textContent = '$' + price;
         });
         if (rateLine) {
            if (isNaN(rate)) { rateLine.style.display = 'none'; }
            else { rateLine.style.display = 'block'; if (rateVal) rateVal.textContent = '$' + rate.toFixed(4); }
         }
         if (overageLine) {
            if (isNaN(rate)) { overageLine.style.display = 'none'; }
            else { overageLine.style.display = 'block'; if (overageVal) overageVal.textContent = '$' + (rate * OVERAGE_MARKUP).toFixed(4); }
         }
      }
      sel.addEventListener('change', update);
      update();
   }
   function boot() { document.querySelectorAll('.ws-upsell').forEach(initUpsell); }
   if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', boot);
   } else { boot(); }
})();
</script>
@endif