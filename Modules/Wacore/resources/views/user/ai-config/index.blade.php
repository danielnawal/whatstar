@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Configuración IA por bot')])
@endsection
@section('content')

<div class="row">
 <div class="col-12">
 <div class="card mb-3">
 <div class="card-body">
 <h4 class="mb-1"><i class="fas fa-brain text-info mr-2"></i>{{ __('Configuración IA por bot') }}</h4>
 <p class="text-muted mb-0">{{ __('Personaliza el "cerebro" del chatbot por cada dispositivo: nombre del negocio, contexto, prompt custom.') }}</p>
 </div>
 </div>

 <div class="card">
 <div class="card-body">
 <table class="table align-middle">
 <thead class="text-uppercase text-xxs">
 <tr>
 <th>{{ __('Device') }}</th>
 <th>{{ __('Industria') }}</th>
 <th>{{ __('Negocio AI') }}</th>
 <th>{{ __('IA') }}</th>
 <th></th>
 </tr>
 </thead>
 <tbody>
 @forelse($devices as $d)
 <tr>
 <td><strong>{{ $d->name }}</strong><br><small class="text-muted">{{ $d->phone }}</small></td>
 <td>{{ $d->industry ?: '—' }}</td>
 <td>{{ $d->ai_business ?: '—' }}</td>
 <td>
 @if($d->ai_enabled === null)
 <span class="badge bg-secondary">{{ __('sin config') }}</span>
 @elseif($d->ai_enabled)
 <span class="badge bg-success">{{ __('activa') }}</span>
 @else
 <span class="badge bg-warning">{{ __('apagada') }}</span>
 @endif
 </td>
 <td>
 <a href="{{ route('user.ai-config.edit', $d->id) }}" class="btn btn-sm btn-outline-primary">
 <i class="fas fa-cog mr-1"></i>{{ __('Configurar') }}
 </a>
 </td>
 </tr>
 @empty
 <tr><td colspan="5" class="text-center text-muted py-3">{{ __('Sin dispositivos.') }}</td></tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
 </div>
</div>
@endsection
