@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> 'Widget Web — Chat en tu sitio','buttons'=>[]])
@endsection

@push('css')
<style>
.code-box { background:#1e1e2e; color:#cdd6f4; border-radius:10px; padding:20px; font-family:monospace; font-size:.82rem; line-height:1.6; position:relative; overflow-x:auto; }
.code-box .token-tag  { color:#89dceb; }
.code-box .token-attr { color:#a6e3a1; }
.code-box .token-val  { color:#f9e2af; }
.preview-frame { border:none; border-radius:10px; width:100%; height:200px; background:#f8f9fa; }
.option-label { font-weight:600; font-size:.85rem; margin-bottom:4px; color:#495057; }
.device-badge { display:inline-block; background:#e7f3ff; color:#0c5bb0; border-radius:6px; padding:4px 10px; font-size:.78rem; font-weight:500; margin-right:6px; margin-bottom:4px; cursor:pointer; border:2px solid transparent; }
.device-badge.selected { border-color:#0d6efd; background:#cfe2ff; }
</style>
@endpush

@section('content')
<div class="container-fluid">
<div class="row">

  <!-- Configurador -->
  <div class="col-lg-5">
    <div class="card shadow-sm mb-4">
      <div class="card-header fw-bold">Configurar widget</div>
      <div class="card-body">

        <div class="mb-3">
          <div class="option-label">Device (número WhatsApp)</div>
          @foreach($devices as $d)
            @php $meta = json_decode($d->meta ?? '{}', true); $phone = $meta['phone'] ?? $d->phone ?? ''; @endphp
            @if($phone)
            <span class="device-badge" data-phone="{{ $phone }}" onclick="selectDevice(this)">
              {{ $d->name }} ({{ $phone }})
            </span>
            @endif
          @endforeach
          <input type="hidden" id="cfg-phone" value="{{ optional($devices->first())->phone ?? '' }}">
        </div>

        <div class="mb-3">
          <div class="option-label">Mensaje inicial</div>
          <input type="text" id="cfg-msg" class="form-control" value="Hola, quiero información sobre APPOGIO GPS" oninput="updateCode()">
        </div>

        <div class="row mb-3">
          <div class="col">
            <div class="option-label">Etiqueta del botón</div>
            <input type="text" id="cfg-label" class="form-control" value="¿Hablamos?" oninput="updateCode()">
          </div>
          <div class="col">
            <div class="option-label">Color</div>
            <input type="color" id="cfg-color" class="form-control form-control-color" value="#25d366" oninput="updateCode()">
          </div>
        </div>

        <div class="row mb-3">
          <div class="col">
            <div class="option-label">Posición</div>
            <select id="cfg-pos" class="form-select" onchange="updateCode()">
              <option value="right">Derecha</option>
              <option value="left">Izquierda</option>
            </select>
          </div>
          <div class="col">
            <div class="option-label">Abrir automáticamente (seg)</div>
            <input type="number" id="cfg-delay" class="form-control" value="3" min="0" max="60" oninput="updateCode()">
          </div>
        </div>

      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header fw-bold">Información de uso</div>
      <div class="card-body small text-muted">
        <ul class="mb-0 ps-3">
          <li>Copia el código y pégalo antes del cierre <code>&lt;/body&gt;</code> de tu sitio web.</li>
          <li>El widget abre WhatsApp directamente — no requiere servidor.</li>
          <li>El popup se abre automáticamente una vez por sesión (configurable).</li>
          <li>Compatible con WordPress, Wix, Squarespace y cualquier HTML.</li>
          <li>Para <strong>desactivarlo</strong>: simplemente elimina el tag script de tu HTML.</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Código y preview -->
  <div class="col-lg-7">
    <div class="card shadow-sm mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold">Código de embed</span>
        <button class="btn btn-sm btn-outline-primary" onclick="copyCode()">📋 Copiar</button>
      </div>
      <div class="card-body p-0">
        <pre id="code-output" class="code-box m-0" style="border-radius:0 0 10px 10px"></pre>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header fw-bold">Vista previa (simulada)</div>
      <div class="card-body" style="min-height:160px;background:#f0f2f5;border-radius:0 0 10px 10px;position:relative;overflow:hidden;">
        <div style="text-align:center;padding:30px;color:#aaa;font-size:.85rem">Tu sitio web aquí</div>
        <div id="preview-btn" style="position:absolute;bottom:20px;right:20px;display:flex;align-items:center;gap:8px;cursor:pointer">
          <div id="preview-label" style="background:#fff;border-radius:20px;padding:6px 12px;font-size:12px;font-weight:600;box-shadow:0 2px 6px rgba(0,0,0,.15)">¿Hablamos?</div>
          <div id="preview-bubble" style="width:50px;height:50px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(0,0,0,.2)">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.099.54 4.07 1.485 5.786L.057 23.93l6.304-1.654A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.876 0-3.638-.491-5.164-1.354l-.37-.22-3.824 1.003 1.02-3.726-.242-.383A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
</div>

@push('js')
<script>
var baseUrl = "{{ $baseUrl }}";

function getConfig() {
  return {
    phone:    document.getElementById('cfg-phone').value.trim(),
    msg:      document.getElementById('cfg-msg').value.trim(),
    label:    document.getElementById('cfg-label').value.trim(),
    color:    document.getElementById('cfg-color').value,
    pos:      document.getElementById('cfg-pos').value,
    delay:    document.getElementById('cfg-delay').value,
  };
}

function updateCode() {
  var c = getConfig();
  var code = '<script src="' + baseUrl + '/js/chat-widget.js"\n'
    + '  data-phone="'    + c.phone    + '"\n'
    + '  data-msg="'      + c.msg      + '"\n'
    + '  data-label="'    + c.label    + '"\n'
    + '  data-color="'    + c.color    + '"\n'
    + '  data-position="' + c.pos      + '"\n'
    + '  data-delay="'    + c.delay    + '">\n'
    + '<\/script>';
  document.getElementById('code-output').textContent = code;

  // Preview
  document.getElementById('preview-bubble').style.background = c.color;
  document.getElementById('preview-label').textContent = c.label;
  document.getElementById('preview-btn').style[c.pos === 'left' ? 'left' : 'right'] = '20px';
  if (c.pos === 'left') {
    document.getElementById('preview-btn').style.right = 'auto';
    document.getElementById('preview-btn').style.left = '20px';
    document.getElementById('preview-btn').style.flexDirection = 'row-reverse';
  } else {
    document.getElementById('preview-btn').style.left = 'auto';
    document.getElementById('preview-btn').style.right = '20px';
    document.getElementById('preview-btn').style.flexDirection = 'row';
  }
}

function copyCode() {
  var code = document.getElementById('code-output').textContent;
  navigator.clipboard.writeText(code).then(function () {
    alert('Código copiado al portapapeles.');
  });
}

function selectDevice(el) {
  document.querySelectorAll('.device-badge').forEach(function(b){ b.classList.remove('selected'); });
  el.classList.add('selected');
  document.getElementById('cfg-phone').value = el.getAttribute('data-phone');
  updateCode();
}

// Auto-seleccionar primer device
var first = document.querySelector('.device-badge');
if (first) selectDevice(first);
else updateCode();
</script>
@endpush
@endsection
