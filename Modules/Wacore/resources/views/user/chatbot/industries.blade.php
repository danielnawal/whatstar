@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Plantillas pre-armadas por industria')])
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
 @if(session('error'))
 <div class="alert alert-danger alert-dismissible fade show">
 {{ session('error') }}
 <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
 </div>
 @endif

 <div class="card mb-4">
 <div class="card-body">
 <h4 class="mb-1"><i class="fas fa-magic mr-2"></i>{{ __('Plantillas pre-armadas') }}</h4>
 <p class="text-muted mb-0">
 {{ __('Elige tu industria y en 1 clic cargamos 7-10 reglas listas, personalizadas con tu información. Empieza a vender en 5 minutos.') }}
 </p>
 </div>
 </div>

 <div class="row">
 @foreach($industries as $i)
 <div class="col-md-6 col-lg-4 mb-3">
 <div class="card h-100 industry-card" style="cursor:pointer; border:2px solid transparent;"
 onclick="openInstall('{{ $i->industry_key }}', '{{ $i->industry_name }}')">
 <div class="card-body text-center">
 <div class="icon icon-shape icon-lg bg-gradient-primary text-white rounded-circle mb-3 mx-auto"
 style="width:64px;height:64px;display:flex;align-items:center;justify-content:center;">
 <i class="{{ $i->icon ?: 'fas fa-store' }} fa-2x"></i>
 </div>
 <h5 class="font-weight-bold mb-1">{{ $i->industry_name }}</h5>
 <p class="small text-muted mb-2">{{ $i->description }}</p>
 <button class="btn btn-sm btn-outline-primary">
 <i class="fas fa-download mr-1"></i> {{ __('Instalar') }}
 </button>
 </div>
 </div>
 </div>
 @endforeach
 </div>
 </div>
</div>

{{-- Modal: instalar industria --}}
<div class="modal fade" id="installModal" tabindex="-1">
 <div class="modal-dialog">
 <form class="modal-content" method="POST" action="{{ route('user.chatbot.industries.install') }}">
 @csrf
 <input type="hidden" name="industry_key" id="indKey">
 <div class="modal-header">
 <h5 class="modal-title" id="indTitle">{{ __('Instalar plantilla') }}</h5>
 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
 </div>
 <div class="modal-body">
 <div class="mb-3">
 <label class="form-label">{{ __('Dispositivo destino') }}</label>
 <select name="device_id" class="form-select" required>
 <option value="">{{ __('— elige un device WhatsApp —') }}</option>
 @foreach($devices as $d)
 <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->phone }})</option>
 @endforeach
 </select>
 @if($devices->isEmpty())
 <small class="text-danger">{{ __('Primero conecta un dispositivo WhatsApp en "My Devices".') }}</small>
 @endif
 </div>
 <div class="mb-3">
 <label class="form-label">{{ __('Nombre del negocio') }} <span class="text-danger">*</span></label>
 <input name="business_name" class="form-control" required placeholder="Ej: Óptica El Visión">
 <small class="text-muted">{{ __('Aparece en los saludos del bot.') }}</small>
 </div>
 <div class="mb-3">
 <label class="form-label">{{ __('Teléfono de contacto (opcional)') }}</label>
 <input name="business_phone" class="form-control" placeholder="+57 300 1234567">
 </div>
 <div class="mb-3">
 <label class="form-label">{{ __('Dirección (opcional)') }}</label>
 <input name="business_addr" class="form-control" placeholder="Calle 80 #45-23, Bogotá">
 </div>
 <div class="mb-3">
 <label class="form-label">{{ __('URL de tu web/demo (opcional)') }}</label>
 <input name="business_demo" class="form-control" placeholder="https://tu-sitio.com">
 </div>
 <div class="form-check">
 <input type="checkbox" name="overwrite" value="1" class="form-check-input" id="overwrite">
 <label class="form-check-label" for="overwrite">
 <strong>{{ __('Borrar reglas anteriores') }}</strong>
 <small class="d-block text-muted">{{ __('Si lo dejas desmarcado, las nuevas reglas se suman a las existentes.') }}</small>
 </label>
 </div>
 </div>
 <div class="modal-footer">
 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
 <button class="btn btn-primary" @if($devices->isEmpty()) disabled @endif>
 <i class="fas fa-download mr-1"></i> {{ __('Instalar plantilla') }}
 </button>
 </div>
 </form>
 </div>
</div>

@push('css')
<style>
.industry-card:hover { border-color: #5e72e4 !important; transform: translateY(-3px); transition: all .2s; }
</style>
@endpush
@push('js')
<script>
function openInstall(key, name) {
 document.getElementById('indKey').value = key;
 document.getElementById('indTitle').textContent = 'Instalar: ' + name;
 new bootstrap.Modal(document.getElementById('installModal')).show();
}
</script>
@endpush
@endsection
