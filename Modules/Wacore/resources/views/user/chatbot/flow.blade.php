@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Constructor visual de flujos')])
@endsection
@push('css')
<style>
.flow-canvas {
 position: relative;
 width: 100%;
 min-height: 600px;
 background:
 linear-gradient(#f0f3f7 1px, transparent 1px) 0 0 / 20px 20px,
 linear-gradient(90deg, #f0f3f7 1px, transparent 1px) 0 0 / 20px 20px,
 #fafbfc;
 border: 1px solid #e8ecf0;
 border-radius: 8px;
 overflow: auto;
}
.flow-node {
 position: absolute;
 width: 220px;
 background: #fff;
 border: 2px solid #5e72e4;
 border-radius: 10px;
 padding: 12px;
 cursor: move;
 box-shadow: 0 2px 6px rgba(0,0,0,.08);
 font-size: .82rem;
}
.flow-node.any { border-color: #f5365c; background:#fff5f7; }
.flow-node.equal { border-color: #11cdef; background:#e6f9fc; }
.flow-node.like { border-color: #2dce89; background:#eafaf2; }
.flow-node h6 { margin:0 0 4px; font-size:.78rem; text-transform:uppercase; color:#888; }
.flow-node .kw { font-weight:700; color:#1a4b8c; word-break:break-all; }
.flow-node .reply { margin-top:6px; color:#525f7f; max-height:48px; overflow:hidden; }
.flow-node .actions { display:flex; gap:4px; margin-top:8px; }
.flow-node .btn-xs { padding:2px 8px; font-size:.7rem; }
.flow-edge {
 position: absolute;
 pointer-events: none;
 stroke: #adb5bd;
 stroke-width: 2;
 fill: none;
}
.flow-toolbar {
 display:flex; gap:8px; align-items:center;
 background:#fff; padding:10px; border:1px solid #e8ecf0;
 border-radius: 8px 8px 0 0; border-bottom: none;
}
</style>
@endpush
@section('content')

<div class="row">
 <div class="col-12">
 <div class="card mb-3">
 <div class="card-body d-flex align-items-center flex-wrap gap-2">
 <div>
 <h4 class="mb-1"><i class="fas fa-project-diagram text-primary mr-2"></i>{{ __('Constructor visual de flujos') }}</h4>
 <p class="text-muted mb-0">{{ __('Vista gráfica de tus reglas de chatbot. Arrastra para reorganizar, doble-clic para editar.') }}</p>
 </div>
 <div class="ms-auto d-flex gap-2 align-items-center">
 <form method="GET" class="d-flex gap-2 align-items-center mb-0">
 <select name="device_id" class="form-select form-select-sm" onchange="this.form.submit()">
 @foreach($devices as $d)
 <option value="{{ $d->id }}" @selected($deviceId == $d->id)>{{ $d->name }}</option>
 @endforeach
 </select>
 </form>
 <button class="btn btn-primary btn-sm" onclick="addNode()">
 <i class="fas fa-plus mr-1"></i> {{ __('Nuevo nodo') }}
 </button>
 </div>
 </div>
 </div>

 <div class="flow-toolbar">
 <span class="text-muted small">{{ __('Total reglas:') }} <strong>{{ count($flowData['nodes']) }}</strong></span>
 <span class="text-muted small">{{ __('Conexiones:') }} <strong>{{ count($flowData['edges']) }}</strong></span>
 <div class="ms-auto small">
 <span class="badge bg-success">like</span>
 <span class="badge bg-info">equal</span>
 <span class="badge bg-danger">any (catch-all)</span>
 </div>
 </div>
 <div class="flow-canvas" id="flowCanvas">
 <svg id="flowEdges" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none;"></svg>
 </div>

 <div class="card mt-3">
 <div class="card-body">
 <strong>{{ __('Tip:') }}</strong>
 <span class="text-muted small">
 {{ __('Esta vista visualiza tus reglas. Para edición avanzada usa "Reglas Chatbot" en el menú. El constructor drag-drop completo está disponible en plan premium.') }}
 </span>
 </div>
 </div>
 </div>
</div>

@push('js')
<script>
const flowData = @json($flowData);
const canvas = document.getElementById('flowCanvas');
const edgesSvg = document.getElementById('flowEdges');
const positions = {};

function layout(nodes) {
 const rootNodes = nodes.filter(n => !n.parent_reply_id);
 const childMap = {};
 nodes.forEach(n => {
 if (n.parent_reply_id) {
 (childMap[n.parent_reply_id] = childMap[n.parent_reply_id] || []).push(n);
 }
 });

 let y = 30;
 rootNodes.forEach((n, i) => {
 positions[n.id] = { x: 30 + (i % 5) * 250, y: y + Math.floor(i / 5) * 200 };
 });

 // hijos un nivel debajo
 Object.entries(childMap).forEach(([parentId, kids]) => {
 const p = positions[parentId];
 if (!p) return;
 kids.forEach((k, i) => {
 positions[k.id] = { x: p.x + (i - kids.length/2) * 240, y: p.y + 180 };
 });
 });
}

function render() {
 layout(flowData.nodes);
 flowData.nodes.forEach(n => {
 const pos = positions[n.id] || { x: 30, y: 30 };
 const el = document.createElement('div');
 el.className = 'flow-node ' + (n.data.match_type || 'like');
 el.style.left = pos.x + 'px';
 el.style.top = pos.y + 'px';
 el.dataset.id = n.id;
 el.innerHTML = `
 <h6>${n.data.match_type} · prio ${n.data.priority}</h6>
 <div class="kw">${(n.data.keyword || '*').substring(0, 50)}</div>
 <div class="reply">${(n.data.reply || '').substring(0, 80)}${n.data.reply.length > 80 ? '...' : ''}</div>
 <div class="actions">
 <a href="/user/chatbot/${n.id}/edit" class="btn btn-xs btn-outline-primary">{{ __('Editar') }}</a>
 </div>
 `;
 makeDraggable(el);
 canvas.appendChild(el);
 });

 drawEdges();
}

function drawEdges() {
 edgesSvg.innerHTML = '';
 flowData.edges.forEach(e => {
 const a = positions[e.source];
 const b = positions[e.target];
 if (!a || !b) return;
 const x1 = a.x + 110, y1 = a.y + 70;
 const x2 = b.x + 110, y2 = b.y;
 const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
 path.setAttribute('d', `M${x1} ${y1} C${x1} ${y1+60}, ${x2} ${y2-60}, ${x2} ${y2}`);
 path.setAttribute('class', 'flow-edge');
 edgesSvg.appendChild(path);
 });
}

function makeDraggable(el) {
 let ox = 0, oy = 0, dragging = false;
 el.addEventListener('mousedown', e => {
 dragging = true;
 ox = e.clientX - parseInt(el.style.left);
 oy = e.clientY - parseInt(el.style.top);
 e.preventDefault();
 });
 document.addEventListener('mousemove', e => {
 if (!dragging) return;
 const nx = e.clientX - ox;
 const ny = e.clientY - oy;
 el.style.left = nx + 'px';
 el.style.top = ny + 'px';
 positions[el.dataset.id] = { x: nx, y: ny };
 drawEdges();
 });
 document.addEventListener('mouseup', () => dragging = false);
}

function addNode() {
 window.location.href = '/user/chatbot/create?device_id={{ $deviceId }}';
}

render();
</script>
@endpush
@endsection
