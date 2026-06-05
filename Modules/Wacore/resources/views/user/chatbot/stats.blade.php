@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> 'Chatbot — Analíticas','buttons'=>[]])
@endsection

@push('css')
<style>
.kpi-card { border-radius:12px; padding:18px; box-shadow:0 2px 6px rgba(0,0,0,.06); background:#fff; }
.kpi-label { color:#6c757d; font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
.kpi-value { font-size:1.8rem; font-weight:700; color:#212529; }
.kpi-sub { color:#6c757d; font-size:.78rem; margin-top:4px; }
.section-title { font-size:1rem; font-weight:600; margin:24px 0 12px; color:#212529; border-bottom:2px solid #0d6efd; padding-bottom:6px; }
.intent-row, .rule-row { padding:8px 0; border-bottom:1px solid #f1f3f5; display:flex; justify-content:space-between; align-items:center; gap:12px; }
.intent-row:last-child, .rule-row:last-child { border-bottom:none; }
.intent-bar { flex:1; height:8px; background:#e9ecef; border-radius:4px; overflow:hidden; }
.intent-fill { height:100%; background:linear-gradient(90deg,#0d6efd,#6610f2); }
.nps-bar-1, .nps-bar-2 { background:#dc3545; }
.nps-bar-3 { background:#ffc107; }
.nps-bar-4, .nps-bar-5 { background:#198754; }
.lang-pill { display:inline-block; padding:4px 12px; border-radius:12px; font-size:.78rem; font-weight:500; margin-right:8px; }
.lang-es { background:#fff3cd; color:#664d03; }
.lang-en { background:#cff4fc; color:#055160; }
.lang-pt { background:#d1e7dd; color:#0f5132; }
.empty-state { padding:60px 20px; text-align:center; color:#6c757d; }
.snippet { color:#6c757d; font-size:.78rem; margin-top:2px; }
</style>
@endpush

@section('content')
<div class="container-fluid">

@if($empty)
    <div class="empty-state">
        <h3>Sin datos aún</h3>
        <p>No tienes dispositivos conectados. Conecta tu primer device para ver las analíticas del chatbot.</p>
    </div>
@else

<!-- KPIs principales -->
<div class="row">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="kpi-card">
            <div class="kpi-label">Mensajes recibidos (30d)</div>
            <div class="kpi-value">{{ number_format($data['total_inbound']) }}</div>
            <div class="kpi-sub">Bot envió {{ number_format($data['total_outbound']) }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="kpi-card">
            <div class="kpi-label">Leads (30d)</div>
            <div class="kpi-value">{{ number_format($data['leads_total']) }}</div>
            <div class="kpi-sub">{{ $data['leads_last7'] }} nuevos en últimos 7 días</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="kpi-card">
            <div class="kpi-label">Handoffs cerrados (30d)</div>
            <div class="kpi-value">{{ number_format($data['handoffs_closed']) }}</div>
            <div class="kpi-sub">
                {{ $data['handoffs_pending'] }} pendientes
                @if($data['avg_resolution_min'])
                    · ~{{ round($data['avg_resolution_min']/60, 1) }}h promedio
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="kpi-card">
            <div class="kpi-label">NPS promedio (30d)</div>
            <div class="kpi-value">
                @if($data['nps_avg'])
                    {{ $data['nps_avg'] }}<span style="font-size:1rem;color:#6c757d">/5</span>
                @else
                    <span style="font-size:1rem;color:#6c757d">sin datos</span>
                @endif
            </div>
            <div class="kpi-sub">{{ $data['nps_total'] }} respuestas</div>
        </div>
    </div>
</div>

<!-- Distribución LLM vs keyword -->
<div class="row">
    <div class="col-md-6">
        <div class="section-title">Cómo responde el bot</div>
        <div class="kpi-card">
            <div class="intent-row">
                <span>🤖 LLM (intent + memoria)</span>
                <div style="display:flex;align-items:center;gap:10px;width:60%">
                    <div class="intent-bar"><div class="intent-fill" style="width:{{ $data['pct_by_llm'] }}%"></div></div>
                    <strong>{{ $data['pct_by_llm'] }}%</strong>
                </div>
            </div>
            <div class="intent-row">
                <span>🔤 Keyword matching</span>
                <div style="display:flex;align-items:center;gap:10px;width:60%">
                    <div class="intent-bar"><div class="intent-fill" style="width:{{ $data['pct_by_keyword'] }}%;background:linear-gradient(90deg,#fd7e14,#f59f00)"></div></div>
                    <strong>{{ $data['pct_by_keyword'] }}%</strong>
                </div>
            </div>
        </div>

        <div class="section-title">Idiomas detectados</div>
        <div class="kpi-card">
            @foreach($data['langs'] as $lang)
                <span class="lang-pill lang-{{ $lang->locked_language }}">
                    {{ strtoupper($lang->locked_language) }}: {{ $lang->c }}
                </span>
            @endforeach
            @if($data['langs']->isEmpty())
                <em style="color:#6c757d">Sin sesiones con idioma bloqueado aún</em>
            @endif
        </div>
    </div>

    <div class="col-md-6">
        <div class="section-title">Top intents detectados (LLM, 30d)</div>
        <div class="kpi-card">
            @php $maxC = $data['intent_dist']->max('c') ?: 1; @endphp
            @forelse($data['intent_dist'] as $row)
                <div class="intent-row">
                    <span><code>{{ $row->intent }}</code></span>
                    <div style="display:flex;align-items:center;gap:10px;width:60%">
                        <div class="intent-bar"><div class="intent-fill" style="width:{{ $row->c / $maxC * 100 }}%"></div></div>
                        <strong>{{ $row->c }}</strong>
                    </div>
                </div>
            @empty
                <em style="color:#6c757d">Sin datos aún</em>
            @endforelse
        </div>
    </div>
</div>

<!-- Top reglas -->
<div class="row">
    <div class="col-md-12">
        <div class="section-title">Top 10 reglas más disparadas (30d)</div>
        <div class="kpi-card">
            @php $maxR = $data['top_rules']->max('count') ?: 1; @endphp
            @forelse($data['top_rules'] as $r)
                <div class="rule-row">
                    <div style="flex:1">
                        <strong>#{{ $r['rule_id'] }}</strong>
                        — <code>{{ $r['keyword'] }}</code>
                        <div class="snippet">{{ $r['snippet'] }}…</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;width:35%">
                        <div class="intent-bar"><div class="intent-fill" style="width:{{ $r['count'] / $maxR * 100 }}%"></div></div>
                        <strong>{{ $r['count'] }}</strong>
                    </div>
                </div>
            @empty
                <em style="color:#6c757d">Sin datos aún. El bot necesita procesar al menos 1 mensaje.</em>
            @endforelse
        </div>
    </div>
</div>

<!-- NPS y leads status -->
<div class="row">
    <div class="col-md-6">
        <div class="section-title">Distribución NPS (30d)</div>
        <div class="kpi-card">
            @if($data['nps_total'] > 0)
                @php
                    $nps = collect([1=>0,2=>0,3=>0,4=>0,5=>0]);
                    foreach($data['nps_rows'] as $row) $nps[$row->nps] = $row->c;
                    $maxN = max($nps->max(), 1);
                @endphp
                @foreach($nps as $score => $c)
                    <div class="intent-row">
                        <span>{{ $score }} ⭐</span>
                        <div style="display:flex;align-items:center;gap:10px;width:60%">
                            <div class="intent-bar"><div class="intent-fill nps-bar-{{ $score }}" style="width:{{ $c / $maxN * 100 }}%"></div></div>
                            <strong>{{ $c }}</strong>
                        </div>
                    </div>
                @endforeach
            @else
                <em style="color:#6c757d">Aún no hay respuestas NPS. Se solicitan automáticamente 48h después de cerrar handoffs.</em>
            @endif
        </div>
    </div>

    <div class="col-md-6">
        <div class="section-title">Leads por estado (30d)</div>
        <div class="kpi-card">
            @forelse($data['leads_by_status'] as $row)
                <div class="intent-row">
                    <span style="text-transform:capitalize">{{ $row->status }}</span>
                    <strong>{{ $row->c }}</strong>
                </div>
            @empty
                <em style="color:#6c757d">Sin leads aún</em>
            @endforelse
        </div>
    </div>
</div>

<!-- A/B testing de variantes -->
@if(!empty($data['ab_report']))
<div class="row">
    <div class="col-md-12">
        <div class="section-title">A/B testing — conversión por variante (30d)</div>
        <div class="kpi-card">
            <small style="color:#6c757d">Conversión = cliente disparó intent <code>wants_advisor / checkout / schedule_demo</code> dentro de 24h tras recibir esta variante.</small>
            <table class="table table-sm" style="margin-top:10px;font-size:.85rem">
                <thead style="background:#f1f3f5">
                    <tr>
                        <th>Regla</th>
                        <th>Variante</th>
                        <th style="text-align:right">Usos</th>
                        <th style="text-align:right">Conversiones</th>
                        <th style="text-align:right">Tasa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['ab_report'] as $rule)
                        @foreach($rule['variants'] as $i => $v)
                            <tr>
                                @if($i === 0)
                                    <td rowspan="{{ count($rule['variants']) }}" style="vertical-align:middle"><strong>#{{ $rule['rule_id'] }}</strong><br><small>{{ $rule['total_uses'] }} usos total</small></td>
                                @endif
                                <td>variante #{{ $v['index'] }}</td>
                                <td style="text-align:right">{{ $v['uses'] }}</td>
                                <td style="text-align:right">{{ $v['conversions'] }}</td>
                                <td style="text-align:right">
                                    @if($v['conversion_pct'] >= 30)
                                        <span style="color:#198754;font-weight:bold">{{ $v['conversion_pct'] }}%</span>
                                    @elseif($v['conversion_pct'] >= 10)
                                        <span style="color:#fd7e14">{{ $v['conversion_pct'] }}%</span>
                                    @else
                                        <span style="color:#6c757d">{{ $v['conversion_pct'] }}%</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- A/B testing report -->
@if(!empty($data['ab_report']))
<div class="row">
    <div class="col-md-12">
        <div class="section-title">A/B testing — conversión por variante (30d)</div>
        <div class="kpi-card">
            <table class="table table-sm" style="margin-bottom:0">
                <thead>
                    <tr>
                        <th>Regla</th>
                        <th>Variante</th>
                        <th style="text-align:right">Usos</th>
                        <th style="text-align:right">Conversiones</th>
                        <th style="text-align:right">% Conversión</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['ab_report'] as $rule)
                        @foreach($rule['variants'] as $i => $v)
                            <tr style="background:{{ $i === 0 ? '#d1e7dd' : 'transparent' }}">
                                <td>{{ $loop->parent->first ? '#'.$rule['rule_id'].' ('.$rule['total_uses'].' total)' : '' }}</td>
                                <td>variante {{ $v['index'] }}</td>
                                <td style="text-align:right">{{ $v['uses'] }}</td>
                                <td style="text-align:right">{{ $v['conversions'] }}</td>
                                <td style="text-align:right">
                                    <strong>{{ $v['conversion_pct'] }}%</strong>
                                    @if($i === 0 && $rule['total_uses'] > 5) 🏆 @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            <p class="snippet" style="margin-top:8px">Conversión = cliente disparó wants_advisor, checkout o schedule_demo en las 24h siguientes al envío.</p>
        </div>
    </div>
</div>
@endif

<!-- Mensajes por día -->
<div class="row">
    <div class="col-md-12">
        <div class="section-title">Mensajes por día (últimos 30d)</div>
        <div class="kpi-card">
            <canvas id="msgsChart" height="80"></canvas>
        </div>
    </div>
</div>

@endif
</div>
@endsection

@push('script')
@if(!$empty)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('msgsChart');
const labels = @json($data['msgs_by_day']->pluck('d'));
const inbound = @json($data['msgs_by_day']->pluck('inbound'));
const outbound = @json($data['msgs_by_day']->pluck('outbound'));

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            { label: 'Recibidos', data: inbound, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,.1)', tension: 0.3, fill: true },
            { label: 'Enviados',  data: outbound, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,.1)', tension: 0.3, fill: true }
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});
</script>
@endif
@endpush
