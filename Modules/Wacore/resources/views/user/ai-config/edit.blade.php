@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Configurar IA — ').$device->name])
@endsection
@section('content')

<div class="row">
 <div class="col-lg-9 mx-auto">
 @if(session('success'))
 <div class="alert alert-success">{{ session('success') }}</div>
 @endif

 <form method="POST" action="{{ route('user.ai-config.save', $device->id) }}">
 @csrf
 <div class="card mb-3">
 <div class="card-header pb-0">
 <h5 class="mb-0"><i class="fas fa-brain text-info mr-2"></i>{{ $device->name }}</h5>
 <small class="text-muted">{{ __('Personaliza la IA de este bot. Si no rellenas nada, el bot usa el prompt de Appogio (default).') }}</small>
 </div>
 <div class="card-body">
 <div class="row g-3">
 <div class="col-md-6">
 <label class="form-label">{{ __('Nombre del negocio') }}</label>
 <input name="business_name" class="form-control" value="{{ old('business_name', $config->business_name) }}" placeholder="Ej: Óptica El Visión">
 <small class="text-muted">{{ __('Aparece en respuestas naturales.') }}</small>
 </div>
 <div class="col-md-6">
 <label class="form-label">{{ __('Industria') }}</label>
 <input name="industry" class="form-control" value="{{ old('industry', $config->industry) }}" placeholder="Óptica, Restaurante, Inmobiliaria...">
 </div>
 <div class="col-12">
 <label class="form-label">{{ __('Contexto del negocio (opcional)') }}</label>
 <textarea name="business_context" class="form-control" rows="6" placeholder="Somos una óptica con 3 sucursales en Bogotá. Atendemos exámenes visuales gratis con compra. Trabajamos con marcas X, Y, Z. Tarifa promedio $150.000. Horario lun-sáb 9am-7pm...">{{ old('business_context', $config->business_context) }}</textarea>
 <small class="text-muted">{{ __('La IA usa este contexto para responder cosas off-topic con coherencia.') }}</small>
 </div>
 <div class="col-12">
 <label class="form-label">{{ __('Términos prohibidos (coma)') }}</label>
 <input name="banned_terms" class="form-control" value="{{ old('banned_terms', is_array($config->banned_terms) ? implode(',', $config->banned_terms) : '') }}" placeholder="competidor1, marca-rival, palabra prohibida">
 <small class="text-muted">{{ __('Si la IA genera una respuesta con estos términos, se descarta.') }}</small>
 </div>

 <div class="col-12">
 <hr>
 <div class="form-check form-switch mb-2">
 <input type="checkbox" class="form-check-input" id="aiEnabled" name="ai_enabled" value="1" @checked(old('ai_enabled', $config->ai_enabled ?? true))>
 <label class="form-check-label" for="aiEnabled"><strong>{{ __('Activar IA conversacional') }}</strong>
 <small class="d-block text-muted">{{ __('Si la desactivas, el bot SOLO responde con reglas de palabras clave.') }}</small>
 </label>
 </div>
 <div class="form-check form-switch">
 <input type="checkbox" class="form-check-input" id="useDefault" name="use_default_prompt" value="1" @checked(old('use_default_prompt', $config->use_default_prompt ?? true))>
 <label class="form-check-label" for="useDefault"><strong>{{ __('Usar prompt por defecto + contexto') }}</strong>
 <small class="d-block text-muted">{{ __('Recomendado. Si lo desmarcas, debes proveer un prompt completo abajo.') }}</small>
 </label>
 </div>
 </div>

 <div class="col-12">
 <label class="form-label">{{ __('Prompt 100% personalizado (avanzado)') }}</label>
 <textarea name="custom_system_prompt" class="form-control font-monospace" rows="10" style="font-size:.85rem;">{{ old('custom_system_prompt', $config->custom_system_prompt) }}</textarea>
 <small class="text-muted">
 {{ __('Solo se usa si DESMARCAS "Usar prompt por defecto". El LLM seguirá devolviendo JSON. Documentación de intents:') }}
 <code>greeting, menu, pricing, wants_advisor, wants_demo, has_business, wants_to_start, unknown</code>
 </small>
 </div>
 </div>

 <button class="btn btn-primary mt-3"><i class="fas fa-save mr-1"></i>{{ __('Guardar') }}</button>
 <a href="{{ route('user.ai-config.index') }}" class="btn btn-secondary mt-3">{{ __('Volver') }}</a>
 </div>
 </div>
 </form>
 </div>
</div>
@endsection
