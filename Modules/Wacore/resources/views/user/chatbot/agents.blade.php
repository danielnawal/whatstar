@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> 'Agentes — Multi-agente','buttons'=>[]])
@endsection

@push('css')
<style>
.agent-card { border-radius:10px; background:#fff; box-shadow:0 2px 6px rgba(0,0,0,.06); padding:16px 20px; margin-bottom:12px; display:flex; align-items:center; gap:16px; }
.agent-avatar { width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,#0d6efd,#6610f2); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:1.1rem; flex-shrink:0; }
.agent-name { font-weight:600; font-size:.95rem; }
.agent-meta { color:#6c757d; font-size:.78rem; margin-top:2px; }
.badge-active   { background:#d1e7dd; color:#0f5132; padding:3px 10px; border-radius:12px; font-size:.72rem; }
.badge-inactive { background:#f8d7da; color:#842029; padding:3px 10px; border-radius:12px; font-size:.72rem; }
.stats-pill { background:#f0f2f5; border-radius:8px; padding:6px 12px; font-size:.78rem; text-align:center; }
.stats-pill strong { display:block; font-size:1rem; color:#0d6efd; }
.section-header { font-size:1rem; font-weight:600; margin:24px 0 12px; border-bottom:2px solid #0d6efd; padding-bottom:6px; color:#212529; }
</style>
@endpush

@section('content')
<div class="container-fluid">

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
@endif

<div class="row">
  <!-- Lista de agentes -->
  <div class="col-lg-8">
    <div class="section-header">Agentes activos (distribucion round-robin)</div>

    @forelse($agents as $agent)
    <div class="agent-card">
      <div class="agent-avatar">{{ strtoupper(substr($agent->name,0,1)) }}</div>
      <div class="flex-grow-1">
        <div class="agent-name">{{ $agent->name }}
          @if($agent->is_active)
            <span class="badge-active ms-1">Activo</span>
          @else
            <span class="badge-inactive ms-1">Pausado</span>
          @endif
        </div>
        <div class="agent-meta">
          📱 {{ $agent->phone }}
          @if($agent->role) · 🏷️ {{ $agent->role }} @endif
          @if($agent->region) · 🌎 {{ $agent->region }} @endif
          @if($agent->device) · 📡 {{ $agent->device->name ?? 'Device #'.$agent->device_id }} @endif
        </div>
      </div>
      <div class="d-flex gap-2 me-2">
        <div class="stats-pill"><strong>{{ $agent->handoffs_received_today }}</strong>hoy</div>
        <div class="stats-pill"><strong>{{ $agent->handoffs_received_total }}</strong>total</div>
      </div>
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $agent->id }}">
          <i class="fas fa-edit"></i>
        </button>
        <form method="POST" action="{{ route('user.chatbot.agents.destroy', $agent->id) }}" onsubmit="return confirm('¿Eliminar este agente?')">
          @csrf @method('DELETE')
          <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
        </form>
      </div>
    </div>

    <!-- Modal editar agente -->
    <div class="modal fade" id="editModal{{ $agent->id }}" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST" action="{{ route('user.chatbot.agents.update', $agent->id) }}">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Editar agente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" value="{{ $agent->name }}" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Teléfono WhatsApp <small class="text-muted">(solo dígitos, con código de país)</small></label>
                <input type="text" name="phone" class="form-control" value="{{ $agent->phone }}" required placeholder="573001234567">
              </div>
              <div class="row">
                <div class="col">
                  <label class="form-label">Rol</label>
                  <input type="text" name="role" class="form-control" value="{{ $agent->role }}" placeholder="sales">
                </div>
                <div class="col">
                  <label class="form-label">Región</label>
                  <input type="text" name="region" class="form-control" value="{{ $agent->region }}" placeholder="CO / EC / MX">
                </div>
              </div>
              <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $agent->id }}" {{ $agent->is_active ? 'checked' : '' }}>
                <label class="form-check-label" for="active{{ $agent->id }}">Agente activo (recibe handoffs)</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted">
      <i class="fas fa-users fa-3x mb-3 d-block" style="opacity:.3"></i>
      <p>No hay agentes registrados todavía.<br>Agrega el primero con el formulario de la derecha.</p>
      <small>Mientras no haya agentes, los handoffs van al número configurado en el device (modo legacy).</small>
    </div>
    @endforelse
  </div>

  <!-- Formulario agregar agente -->
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header fw-bold">Agregar agente</div>
      <div class="card-body">
        <form method="POST" action="{{ route('user.chatbot.agents.store') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">Device (WhatsApp conectado)</label>
            <select name="device_id" class="form-select" required>
              <option value="">— Selecciona —</option>
              @foreach($devices as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nombre del agente</label>
            <input type="text" name="name" class="form-control" required placeholder="Ej: María Ventas">
          </div>
          <div class="mb-3">
            <label class="form-label">Teléfono WhatsApp</label>
            <input type="text" name="phone" class="form-control" required placeholder="573001234567">
            <small class="text-muted">Con código de país, sin + ni espacios</small>
          </div>
          <div class="row mb-3">
            <div class="col">
              <label class="form-label">Rol</label>
              <select name="role" class="form-select">
                <option value="sales">Ventas</option>
                <option value="support">Soporte</option>
                <option value="billing">Cobros</option>
              </select>
            </div>
            <div class="col">
              <label class="form-label">Región <small class="text-muted">(opc.)</small></label>
              <input type="text" name="region" class="form-control" placeholder="CO / EC / MX">
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Agregar agente</button>
        </form>
      </div>
    </div>

    <div class="card shadow-sm mt-3">
      <div class="card-body">
        <h6 class="fw-bold mb-2">¿Cómo funciona?</h6>
        <ul class="small text-muted mb-0 ps-3">
          <li>Cuando un cliente pide <strong>asesor</strong>, el bot elige el agente con <strong>menos handoffs hoy</strong> (round-robin).</li>
          <li>Si hay agentes por región, se asigna primero el del país del cliente.</li>
          <li>Si no hay agentes registrados, se usa el número del device (modo anterior).</li>
          <li>Pausar un agente lo excluye del reparto sin eliminarlo.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
</div>
@endsection
