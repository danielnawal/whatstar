@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title' => __('Conversación #') . $handoff->id, 'buttons' => [
    ['name' => '← Volver', 'url' => route('user.handoffs.index')]
]])
@endsection
@push('css')
<style>
.chat-bubble { max-width: 75%; padding: 10px 14px; border-radius: 12px; margin-bottom: 6px; font-size: .88rem; line-height: 1.5; }
.chat-user { background: #e9f5ff; border-bottom-left-radius: 2px; margin-right: auto; }
.chat-bot  { background: #f0f0f0; border-bottom-right-radius: 2px; margin-left: auto; }
.chat-time { font-size: .7rem; color: #888; margin-top: 2px; }
.chat-wrap { display: flex; flex-direction: column; }
.nps-star { cursor:pointer; font-size:1.5rem; color:#ccc; transition:color .15s; }
.nps-star.active, .nps-star:hover { color: #f4a261; }
</style>
@endpush
@section('content')

<div class="row">
    {{-- Panel izquierdo: info del handoff --}}
    <div class="col-md-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2"><strong>Datos del contacto</strong></div>
            <div class="card-body">
                <p class="mb-1"><strong>Contacto:</strong> <code>{{ $handoff->contact }}</code></p>
                @if($handoff->contact_name)
                <p class="mb-1"><strong>Nombre:</strong> {{ $handoff->contact_name }}</p>
                @endif
                <p class="mb-1"><strong>Motivo:</strong> {{ $handoff->last_message ?: '—' }}</p>
                <p class="mb-1"><strong>Fecha:</strong> {{ $handoff->created_at->format('d/m/Y H:i') }}</p>
                @if($handoff->resolved_at)
                <p class="mb-1"><strong>Cerrado:</strong> {{ \Carbon\Carbon::parse($handoff->resolved_at)->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>

        {{-- Estado --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2"><strong>Estado</strong></div>
            <div class="card-body">
                <select id="status-select" class="form-control form-control-sm mb-2" data-id="{{ $handoff->id }}">
                    @foreach(['pending'=>'Pendiente','active'=>'En atención','closed'=>'Cerrado'] as $val => $lbl)
                    <option value="{{ $val }}" {{ $handoff->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
                <button id="btn-save-status" class="btn btn-sm btn-primary w-100">Guardar estado</button>
            </div>
        </div>

        {{-- NPS --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header py-2"><strong>NPS del cliente (1–10)</strong></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-1 mb-2" id="nps-stars">
                    @for($i = 1; $i <= 10; $i++)
                    <span class="nps-star {{ $handoff->nps >= $i ? 'active' : '' }}" data-val="{{ $i }}">{{ $i <= 5 ? '★' : '★' }}</span>
                    @endfor
                </div>
                <input type="hidden" id="nps-val" value="{{ $handoff->nps }}">
                <textarea id="nps-comment" class="form-control form-control-sm mb-2" rows="2"
                    placeholder="Comentario opcional…">{{ $handoff->nps_comment }}</textarea>
                <button id="btn-save-nps" class="btn btn-sm btn-success w-100">Guardar NPS</button>
            </div>
        </div>
    </div>

    {{-- Panel derecho: historial de mensajes --}}
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <strong>Historial de la conversación</strong>
                <span class="badge badge-secondary">{{ $messages->count() }} mensajes</span>
            </div>
            <div class="card-body" style="max-height:520px;overflow-y:auto;" id="chat-body">
                @forelse($messages as $m)
                <div class="chat-wrap">
                    <div class="chat-bubble {{ $m->role === 'user' ? 'chat-user' : 'chat-bot' }}">
                        <div class="fw-bold small mb-1">{{ $m->role === 'user' ? '👤 Cliente' : '🤖 Bot' }}</div>
                        {!! nl2br(e($m->content)) !!}
                    </div>
                    <div class="chat-time {{ $m->role === 'user' ? '' : 'text-end' }}">
                        {{ \Carbon\Carbon::parse($m->created_at)->format('d/m H:i') }}
                        @if($m->intent) · <em>{{ $m->intent }}</em> @endif
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-4">Sin historial de mensajes registrado.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
// Auto-scroll al fondo
var chatBody = document.getElementById('chat-body');
if (chatBody) chatBody.scrollTop = chatBody.scrollHeight;

// NPS stars
var npsVal = parseInt(document.getElementById('nps-val').value) || 0;
document.querySelectorAll('.nps-star').forEach(function(s) {
    s.addEventListener('click', function() {
        npsVal = parseInt(this.dataset.val);
        document.getElementById('nps-val').value = npsVal;
        document.querySelectorAll('.nps-star').forEach(function(x) {
            x.classList.toggle('active', parseInt(x.dataset.val) <= npsVal);
        });
    });
});

// Guardar estado
document.getElementById('btn-save-status').addEventListener('click', function() {
    fetch('/user/handoffs/{{ $handoff->id }}/status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ status: document.getElementById('status-select').value }),
    }).then(function(r) {
        if (r.ok) { alert('Estado guardado.'); location.reload(); }
        else alert('Error al guardar.');
    });
});

// Guardar NPS
document.getElementById('btn-save-nps').addEventListener('click', function() {
    if (!npsVal) { alert('Selecciona un valor NPS (1–10).'); return; }
    fetch('/user/handoffs/{{ $handoff->id }}/nps', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ nps: npsVal, nps_comment: document.getElementById('nps-comment').value }),
    }).then(function(r) {
        if (r.ok) alert('NPS guardado.');
        else alert('Error al guardar NPS.');
    });
});
</script>
@endpush
@endsection
