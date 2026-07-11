{{-- ── Plan B: WhatsApp API Oficial de Meta ─────────────────────────────────────
     Parcial reutilizable. Se incluye en el modal de "Evitar desconexión"
     (header) y en la página de "Salud de bots". Usa CLASES (no IDs) porque en
     esa página aparece dos veces. El cálculo de precios por país lo hace el JS
     de layouts/main/header.blade.php, que inicializa TODOS los .ws-upsell.
     Estilos .ws-upsell / .ws-plans / .ws-country en el header. --}}
@php
    // Tarifa de Meta (Utility) por país, USD por alerta. Referencia — Meta la
    // cambia cuando quiere; el JS recalcula el precio con la tarifa vigente.
    $waCountries = [
        'Argentina' => 0.0260, 'Bolivia' => 0.0113, 'Brasil' => 0.0068,
        'Canadá' => 0.0034, 'Chile' => 0.0200, 'Colombia' => 0.0008,
        'Costa Rica' => 0.0113, 'Cuba' => 0.0113, 'Ecuador' => 0.0113,
        'El Salvador' => 0.0113, 'Estados Unidos' => 0.0034, 'Guatemala' => 0.0113,
        'Honduras' => 0.0113, 'México' => 0.0085, 'Nicaragua' => 0.0113,
        'Panamá' => 0.0113, 'Paraguay' => 0.0113, 'Perú' => 0.0200,
        'Puerto Rico' => 0.0034, 'República Dominicana' => 0.0113,
        'Uruguay' => 0.0113, 'Venezuela' => 0.0113,
    ];
    // platform = hospedaje del número en 360dialog (Regular Channel = ~$59 USD/mes)
    // + tu ganancia FIJA. Los mensajes se cobran al costo (sin recargo). Así tu
    // margen real es constante: Esencial +$10, Profesional +$20, Empresa +$50.
    // (Fuente 360dialog: Regular $59, Premium $119, Higher Throughput $299 por canal/mes.)
    $waHosting = 59;
    $waPlans = [
        ['name' => __('Esencial'),    'platform' => 69,  'included' => 1000,  'tag' => '',
         'features' => [__('1 número oficial verificado'), __('Conexión permanente 24/7'), __('Sin QR ni reconexiones'), __('Soporte estándar')]],
        ['name' => __('Profesional'), 'platform' => 79,  'included' => 5000,  'tag' => __('Recomendado'),
         'features' => [__('1 número oficial verificado'), __('Gestión de verificación de marca'), __('Plantillas de alerta personalizadas'), __('Soporte prioritario')]],
        ['name' => __('Empresa'),     'platform' => 109, 'included' => 15000, 'tag' => '',
         'features' => [__('Números múltiples disponibles'), __('Integraciones y API a medida'), __('Multi-canal (WhatsApp, SMS, Email)'), __('Soporte dedicado')]],
    ];
@endphp
<div class="ws-upsell">
   <div class="ws-upsell-head">
      <div class="ico"><i class="fas fa-certificate"></i></div>
      <div>
         <h6>{{ __('¿Quiere que nunca se desconecte?') }}</h6>
         <span class="ws-plan-now">{{ __('Actualmente usas el plan gratuito') }}</span>
      </div>
   </div>

   <p class="ws-upsell-lead"><strong>{{ __('WhatsApp API Oficial de Meta') }}</strong> — {{ __('la opción profesional para operaciones que no pueden fallar.') }}</p>

   <ul class="ws-upsell-list">
      <li><i class="fas fa-check-circle"></i>{{ __('Conexión permanente: nunca se desconecta.') }}</li>
      <li><i class="fas fa-check-circle"></i>{{ __('Número oficial verificado con la palomita de WhatsApp.') }}</li>
      <li><i class="fas fa-check-circle"></i>{{ __('Requiere un número exclusivo para las alertas.') }}</li>
      <li><i class="fas fa-check-circle"></i>{{ __('Sin necesidad de escanear QR ni reconectar nunca.') }}</li>
   </ul>

   <div class="ws-country-wrap">
      <label>{{ __('Selecciona tu país para ver el precio') }}</label>
      <select class="ws-country">
         <option value="" data-rate="">{{ __('— Selecciona tu país —') }}</option>
         @foreach($waCountries as $name => $rate)
         <option data-rate="{{ $rate }}">{{ $name }}</option>
         @endforeach
      </select>
   </div>

   <div class="ws-rate-line" style="display:none;">
      {{ __('Tarifa oficial de Meta para este país:') }} <strong class="ws-rate-val">—</strong> {{ __('por alerta.') }}
   </div>
   <div class="ws-overage-line" style="display:none;">
      {{ __('Alertas adicionales (si superas tu cupo):') }} <strong class="ws-overage-val">—</strong> {{ __('cada una.') }}
      {{ __('También puedes activar un tope: al llegar al límite se pausan los envíos y no se genera costo extra.') }}
   </div>

   <div class="ws-price-title">{{ __('Elige tu plan') }}</div>
   <div class="ws-plans">
      @foreach($waPlans as $p)
      <div class="ws-plan {{ $p['tag'] ? 'is-featured' : '' }}" data-platform="{{ $p['platform'] }}" data-included="{{ $p['included'] }}">
         @if($p['tag'])<span class="ws-plan-tag">{{ $p['name'] }} — {{ $p['tag'] }}</span>
         @else<span class="ws-plan-tag">{{ $p['name'] }}</span>@endif
         <div class="price"><span class="amt">—</span> <small>{{ __('/ mes') }}</small></div>
         <div class="cap">{{ __('Hasta') }} {{ number_format($p['included']) }} {{ __('alertas al mes') }}</div>
         <ul>
            @foreach($p['features'] as $f)
            <li><i class="fas fa-check"></i>{{ $f }}</li>
            @endforeach
         </ul>
      </div>
      @endforeach
   </div>

   <div class="ws-meta-note">{{ __('Meta puede actualizar sus tarifas en cualquier momento; el precio del plan se ajusta automáticamente a la tarifa vigente del país. Alertas adicionales sobre el cupo se cobran a la tarifa del país más un pequeño margen.') }}</div>

   <div class="ws-upsell-example">
      <i class="fas fa-lightbulb"></i>
      <div>{{ __('Ejemplo: 10 vehículos con 5 alertas diarias = 1,500 alertas al mes, cubiertas por el plan Esencial.') }}</div>
   </div>

   <div class="ws-upsell-cta">
      <span>{{ __('Elige tu plan y lo activamos en 1 a 3 días hábiles.') }}</span>
      <a href="{{ route('user.support.create') }}" class="ws-cta-btn">
         <i class="fas fa-headset mr-1"></i>{{ __('Quiero activar el Plan B') }}
      </a>
   </div>
</div>
