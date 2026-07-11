@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Alertas multi-canal')])
@endsection
@section('content')

<div class="row">
 <div class="col-12">
 @if(session('success'))
 <div class="alert alert-success alert-dismissible fade show">
 {{ session('success') }}
 <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
 </div>
 @endif

 <div class="card mb-3">
 <div class="card-header pb-0 d-flex align-items-center">
 <div>
 <h5 class="mb-0">{{ __('Plantillas de alerta') }}</h5>
 <small class="text-muted">{{ __('Personaliza el texto que se envía por cada tipo de evento y canal.') }}</small>
 </div>
 <button class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#tplModal">
 <i class="fas fa-plus mr-1"></i> {{ __('Nueva plantilla') }}
 </button>
 </div>
 <div class="card-body">
 <div class="alert alert-info mb-3 small">
 <strong>{{ __('Variables disponibles:') }}</strong>
 <code>{brand}</code> <code>{unit}</code> <code>{event}</code>
 <code>{speed}</code> <code>{address}</code> <code>{lat}</code>
 <code>{lng}</code> <code>{map_link}</code> <code>{time}</code> <code>{date}</code>
 </div>

 <div class="table-responsive">
 <table class="table table-sm align-middle">
 <thead class="text-uppercase text-xxs">
 <tr>
 <th>{{ __('App') }}</th>
 <th>{{ __('Evento') }}</th>
 <th>{{ __('Canal') }}</th>
 <th>{{ __('Idioma') }}</th>
 <th>{{ __('Plantilla') }}</th>
 <th>{{ __('Activa') }}</th>
 <th></th>
 </tr>
 </thead>
 <tbody>
 @forelse($templates as $t)
 <tr>
 <td>{{ $apps->firstWhere('id', $t->app_id)?->title ?: __('Todas') }}</td>
 <td><span class="badge bg-secondary">{{ \App\Models\AlertTemplate::EVENT_TYPES[$t->event_type] ?? $t->event_type }}</span></td>
 <td>{{ \App\Models\AlertTemplate::CHANNELS[$t->channel] ?? $t->channel }}</td>
 <td>{{ strtoupper($t->language) }}</td>
 <td><small class="text-muted">{{ \Illuminate\Support\Str::limit($t->template_text, 70) }}</small></td>
 <td>{!! $t->is_active ? '<span class="text-success">●</span>' : '<span class="text-muted">○</span>' !!}</td>
 <td>
 <form method="POST" action="{{ route('user.alerts.templates.delete', $t->id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar plantilla?')">
 @csrf @method('DELETE')
 <button class="btn btn-link text-danger p-0"><i class="fas fa-trash"></i></button>
 </form>
 </td>
 </tr>
 @empty
 <tr><td colspan="7" class="text-center text-muted py-3">{{ __('Sin plantillas personalizadas — se usa la plantilla por defecto.') }}</td></tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
 </div>

 <div class="card mb-3">
 <div class="card-header pb-0 d-flex align-items-center">
 <div>
 <h5 class="mb-0">{{ __('Reglas de filtrado') }}</h5>
 <small class="text-muted">{{ __('Silencia o bloquea alertas por horario, velocidad mínima o repetición.') }}</small>
 </div>
 <button class="btn btn-warning btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#ruleModal">
 <i class="fas fa-plus mr-1"></i> {{ __('Nueva regla') }}
 </button>
 </div>
 <div class="card-body">
 <div class="table-responsive">
 <table class="table table-sm align-middle">
 <thead class="text-uppercase text-xxs">
 <tr>
 <th>{{ __('App') }}</th>
 <th>{{ __('Evento') }}</th>
 <th>{{ __('Patrón unidad') }}</th>
 <th>{{ __('Silenciar') }}</th>
 <th>{{ __('Anti-spam') }}</th>
 <th>{{ __('Vel. mín.') }}</th>
 <th>{{ __('Bloquear') }}</th>
 <th></th>
 </tr>
 </thead>
 <tbody>
 @forelse($rules as $r)
 <tr>
 <td>{{ $apps->firstWhere('id', $r->app_id)?->title ?: __('Todas') }}</td>
 <td>{{ \App\Models\AlertTemplate::EVENT_TYPES[$r->event_type] ?? $r->event_type ?: __('Cualquiera') }}</td>
 <td><code>{{ $r->unit_pattern ?: '*' }}</code></td>
 <td>{{ $r->mute_from ? $r->mute_from.' → '.$r->mute_to : '—' }}</td>
 <td>{{ $r->dedupe_seconds ? $r->dedupe_seconds.'s' : '—' }}</td>
 <td>{{ $r->min_speed ? $r->min_speed.' km/h' : '—' }}</td>
 <td>{!! $r->block ? '<span class="badge bg-danger">SI</span>' : '—' !!}</td>
 <td>
 <form method="POST" action="{{ route('user.alerts.rules.delete', $r->id) }}" class="d-inline" onsubmit="return confirm('¿Eliminar regla?')">
 @csrf @method('DELETE')
 <button class="btn btn-link text-danger p-0"><i class="fas fa-trash"></i></button>
 </form>
 </td>
 </tr>
 @empty
 <tr><td colspan="8" class="text-center text-muted py-3">{{ __('Sin reglas — todas las alertas se envían.') }}</td></tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
 </div>

 <div class="card">
 <div class="card-header pb-0">
 <h5 class="mb-0">{{ __('Últimas 50 alertas') }}</h5>
 </div>
 <div class="card-body">
 <div class="table-responsive">
 <table class="table table-sm align-middle">
 <thead class="text-uppercase text-xxs">
 <tr>
 <th>{{ __('Fecha') }}</th>
 <th>{{ __('Evento') }}</th>
 <th>{{ __('Unidad') }}</th>
 <th>{{ __('Canal') }}</th>
 <th>{{ __('Destino') }}</th>
 <th>{{ __('Estado') }}</th>
 <th>{{ __('Motivo') }}</th>
 </tr>
 </thead>
 <tbody>
 @forelse($recent as $l)
 <tr>
 <td><small>{{ $l->created_at }}</small></td>
 <td>{{ \App\Models\AlertTemplate::EVENT_TYPES[$l->event_type] ?? $l->event_type }}</td>
 <td>{{ $l->unit }}</td>
 <td>{{ ucfirst($l->channel) }}</td>
 <td><small>{{ $l->destination }}</small></td>
 <td>
 @if($l->status === 'sent') <span class="badge bg-success"></span>
 @elseif($l->status === 'failed') <span class="badge bg-danger"></span>
 @else <span class="badge bg-warning">{{ $l->status }}</span> @endif
 </td>
 <td><small class="text-muted">{{ $l->reason }}</small></td>
 </tr>
 @empty
 <tr><td colspan="7" class="text-center text-muted py-3">{{ __('Sin alertas registradas todavía.') }}</td></tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
 </div>
 </div>
</div>

{{-- Modal: nueva plantilla --}}
<div class="modal fade" id="tplModal" tabindex="-1">
 <div class="modal-dialog modal-lg">
 <form class="modal-content" method="POST" action="{{ route('user.alerts.templates.store') }}">
 @csrf
 <div class="modal-header">
 <h5 class="modal-title">{{ __('Nueva plantilla de alerta') }}</h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
 </div>
 <div class="modal-body">
 <div class="row g-3">
 <div class="col-md-6">
 <label class="form-label">{{ __('App (opcional)') }}</label>
 <select name="app_id" class="form-select">
 <option value="">{{ __('Todas las apps') }}</option>
 @foreach($apps as $a) <option value="{{ $a->id }}">{{ $a->title }}</option> @endforeach
 </select>
 </div>
 <div class="col-md-6">
 <label class="form-label">{{ __('Tipo de evento') }}</label>
 <select name="event_type" class="form-select" required>
 @foreach(\App\Models\AlertTemplate::EVENT_TYPES as $k => $v)
 <option value="{{ $k }}">{{ $v }}</option>
 @endforeach
 </select>
 </div>
 <div class="col-md-6">
 <label class="form-label">{{ __('Canal') }}</label>
 <select name="channel" class="form-select" required>
 @foreach(\App\Models\AlertTemplate::CHANNELS as $k => $v)
 <option value="{{ $k }}">{{ $v }}</option>
 @endforeach
 </select>
 </div>
 <div class="col-md-6">
 <label class="form-label">{{ __('Idioma') }}</label>
 <select name="language" class="form-select" required>
 <option value="es">Español</option>
 <option value="en">English</option>
 <option value="pt">Português</option>
 </select>
 </div>
 <div class="col-12">
 <label class="form-label">{{ __('Plantilla') }}</label>
 <textarea name="template_text" class="form-control" rows="6" id="tplText" required>{{ \App\Models\AlertTemplate::DEFAULT_TEMPLATE }}</textarea>
 <button type="button" class="btn btn-sm btn-outline-info mt-2" onclick="previewTpl()">
 <i class="fas fa-eye mr-1"></i>{{ __('Previsualizar') }}
 </button>
 <div id="tplPreview" class="mt-2 p-3 bg-light rounded" style="display:none; font-family:monospace; white-space:pre-line;"></div>
 </div>
 </div>
 </div>
 <div class="modal-footer">
 <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
 <button class="btn btn-primary">{{ __('Guardar') }}</button>
 </div>
 </form>
 </div>
</div>

{{-- Modal: nueva regla --}}
<div class="modal fade" id="ruleModal" tabindex="-1">
 <div class="modal-dialog modal-lg">
 <form class="modal-content" method="POST" action="{{ route('user.alerts.rules.store') }}">
 @csrf
 <div class="modal-header">
 <h5 class="modal-title">{{ __('Nueva regla de filtrado') }}</h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
 </div>
 <div class="modal-body">
 <div class="row g-3">
 <div class="col-md-6">
 <label class="form-label">{{ __('App (opcional)') }}</label>
 <select name="app_id" class="form-select">
 <option value="">{{ __('Todas') }}</option>
 @foreach($apps as $a) <option value="{{ $a->id }}">{{ $a->title }}</option> @endforeach
 </select>
 </div>
 <div class="col-md-6">
 <label class="form-label">{{ __('Tipo de evento') }}</label>
 <select name="event_type" class="form-select">
 <option value="">{{ __('Cualquiera') }}</option>
 @foreach(\App\Models\AlertTemplate::EVENT_TYPES as $k => $v)
 @if($k !== 'default') <option value="{{ $k }}">{{ $v }}</option> @endif
 @endforeach
 </select>
 </div>
 <div class="col-md-6">
 <label class="form-label">{{ __('Patrón de unidad') }}</label>
 <input name="unit_pattern" class="form-control" placeholder="ABC-* o Camion*">
 <small class="text-muted">{{ __('Comodines: * y ?') }}</small>
 </div>
 <div class="col-md-3">
 <label class="form-label">{{ __('Silenciar desde') }}</label>
 <input type="time" name="mute_from" class="form-control">
 </div>
 <div class="col-md-3">
 <label class="form-label">{{ __('hasta') }}</label>
 <input type="time" name="mute_to" class="form-control">
 </div>
 <div class="col-md-4">
 <label class="form-label">{{ __('Anti-spam (seg)') }}</label>
 <input type="number" name="dedupe_seconds" class="form-control" min="0" max="3600" value="0">
 <small class="text-muted">{{ __('Misma alerta + unidad: ignorar dentro de N segundos') }}</small>
 </div>
 <div class="col-md-4">
 <label class="form-label">{{ __('Velocidad mínima (km/h)') }}</label>
 <input type="number" name="min_speed" class="form-control" min="0">
 </div>
 <div class="col-md-4">
 <label class="form-label">{{ __('Prioridad') }}</label>
 <input type="number" name="priority" class="form-control" value="0">
 </div>
 <div class="col-12">
 <div class="form-check">
 <input type="checkbox" name="block" value="1" class="form-check-input" id="ruleBlock">
 <label class="form-check-label" for="ruleBlock">{{ __('Bloquear (no enviar la alerta cuando coincida)') }}</label>
 </div>
 </div>
 </div>
 </div>
 <div class="modal-footer">
 <button class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
 <button class="btn btn-warning">{{ __('Guardar regla') }}</button>
 </div>
 </form>
 </div>
</div>

@push('js')
<script>
function previewTpl() {
 const text = document.getElementById('tplText').value;
 fetch('{{ route("user.alerts.preview") }}', {
 method: 'POST',
 headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
 body: JSON.stringify({template: text})
 }).then(r => r.json()).then(j => {
 const p = document.getElementById('tplPreview');
 p.textContent = j.preview;
 p.style.display = 'block';
 });
}
</script>
@endpush
@endsection
