<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="UTF-8">
<title>Cotización APPOGIO — {{ $client_name }}</title>
<style>
@page { margin: 28px 34px; }
* { font-family: 'DejaVu Sans', sans-serif; }
body { color:#1f2937; font-size:10.5px; line-height:1.5; }
.header { display:table; width:100%; border-bottom:3px solid #0d6efd; padding-bottom:12px; margin-bottom:16px; }
.brand { display:table-cell; width:60%; vertical-align:middle; }
.brand h1 { color:#0d6efd; margin:0; font-size:26px; letter-spacing:1px; }
.brand .tag { color:#6c757d; font-size:9.5px; margin-top:3px; }
.meta { display:table-cell; width:40%; text-align:right; vertical-align:middle; font-size:9.5px; color:#6c757d; }
.meta strong { color:#212529; }
.client-box { background:#f0f7ff; border-left:4px solid #0d6efd; padding:10px 13px; margin-bottom:14px; border-radius:4px; }
.client-box h3 { margin:0 0 5px; font-size:11px; color:#495057; text-transform:uppercase; letter-spacing:.5px; }
.client-box p { margin:2px 0; font-size:10.5px; }
h2.section { color:#0d6efd; font-size:13px; margin:14px 0 8px; padding-bottom:3px; border-bottom:1px solid #e5e7eb; }
.intro-box { background:#f8f9fa; border-left:4px solid #198754; padding:10px 13px; margin-bottom:14px; border-radius:4px; font-size:10.5px; }
.intro-box .stat { display:inline-block; margin-right:18px; }
.intro-box .stat strong { color:#0f5132; font-size:12px; }
table.pricing { width:100%; border-collapse:collapse; margin-bottom:14px; }
table.pricing th { background:#0d6efd; color:#fff; text-align:left; padding:7px 9px; font-size:10.5px; }
table.pricing td { padding:8px 9px; border-bottom:1px solid #e5e7eb; font-size:10.5px; }
table.pricing tr.total td { background:#fff3cd; font-weight:bold; font-size:11px; }
.tier-highlight { background:#d1e7dd; }
.tier-highlight td { color:#0f5132; }
.savings { color:#198754; font-weight:bold; }
.features { display:table; width:100%; margin:8px 0; }
.feature-col { display:table-cell; width:50%; padding-right:6px; vertical-align:top; }
.feature-col:last-child { padding-right:0; padding-left:6px; }
.features ul { margin:0; padding-left:13px; }
.features li { margin-bottom:3px; }
.info-box { padding:9px 12px; border-radius:4px; margin:10px 0; font-size:10.5px; }
.info-box.blue { background:#e7f1ff; border:1px solid #9ec5fe; }
.info-box.green { background:#d1e7dd; border:1px solid #a3cfbb; }
.info-box.yellow { background:#fff8e1; border:1px solid #ffe082; }
.info-box strong { color:#0a3d62; }
.info-box.green strong { color:#0f5132; }
.info-box.yellow strong { color:#7a5700; }
.apps-table { width:100%; border-collapse:collapse; margin:8px 0; }
.apps-table th { background:#6610f2; color:#fff; padding:6px 9px; font-size:10px; text-align:left; }
.apps-table td { padding:7px 9px; border-bottom:1px solid #e5e7eb; font-size:10px; vertical-align:top; }
.apps-table tr:nth-child(even) td { background:#fafafa; }
.app-name { font-weight:bold; color:#0d6efd; }
.wa-box { background:#e8f5e9; border-left:4px solid #25D366; padding:10px 13px; margin:10px 0; border-radius:4px; font-size:10.5px; }
.cta { background:linear-gradient(135deg,#0d6efd,#6610f2); color:#fff; padding:14px; text-align:center; border-radius:6px; margin:14px 0 10px; }
.cta h3 { margin:0 0 6px; font-size:13px; }
.cta p { margin:0; font-size:10px; }
.guarantee-box { background:#fff3cd; border:1px solid #ffc107; padding:9px 12px; border-radius:4px; margin:10px 0; font-size:10.5px; text-align:center; }
.footer { margin-top:18px; padding-top:10px; border-top:1px solid #e5e7eb; font-size:8.5px; color:#6c757d; text-align:center; line-height:1.6; }
.terms { font-size:8.5px; color:#6c757d; margin-top:10px; padding:8px 10px; background:#f8f9fa; border-radius:4px; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="brand">
        @if(!empty($logo_path))
            <img src="{{ $logo_path }}" alt="APPOGIO" style="max-height:52px; max-width:220px; display:block; margin-bottom:4px;">
        @else
            <h1>APPOGIO</h1>
        @endif
        <div class="tag">{{ $t['tag'] }}</div>
    </div>
    <div class="meta">
        <div><strong>{{ $t['quote_label'] }}</strong> #{{ $quote_id }}</div>
        <div>{{ $today }}</div>
        <div>{{ $t['valid_label'] }}: {{ $valid_until }}</div>
    </div>
</div>

{{-- Cliente --}}
<div class="client-box">
    <h3>{{ $t['client_label'] }}</h3>
    <p><strong>{{ $client_name }}</strong></p>
    @if($company)<p>{{ $company }}</p>@endif
    @if($contact)<p>{{ $contact }}</p>@endif
    <p>{{ $t['quantity_label'] }}: <strong>{{ $qty }} {{ $t['units_label'] }}</strong></p>
</div>

{{-- Intro plataforma --}}
<h2 class="section">🌎 {{ $t['platform_title'] }}</h2>
<div class="intro-box">
    <p style="margin:0 0 8px">{{ $t['platform_intro'] }}</p>
    <div>
        <span class="stat"><strong>+11 {{ $t['years_label'] }}</strong> {{ $t['years_desc'] }}</span>
        <span class="stat"><strong>+500</strong> {{ $t['companies_label'] }}</span>
        <span class="stat"><strong>+3.800</strong> {{ $t['models_label'] }}</span>
        <span class="stat"><strong>+400</strong> {{ $t['functions_label'] }}</span>
    </div>
</div>

{{-- Modelo de negocio --}}
<div class="info-box green">
    <strong>💼 {{ $t['bizmodel_title'] }}</strong>
    <p style="margin:5px 0 0">{{ $t['bizmodel_desc'] }}</p>
</div>

{{-- Precios --}}
<h2 class="section">💰 {{ $t['pricing_title'] }}</h2>
<table class="pricing">
    <thead>
        <tr>
            <th>{{ $t['plan_label'] }}</th>
            <th>{{ $t['range_label'] }}</th>
            <th style="text-align:right">{{ $t['monthly_label'] }} (USD/u)</th>
            <th style="text-align:right">{{ $t['annual_label'] }} (USD/u)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tiers as $tier)
        <tr class="{{ $tier['active'] ? 'tier-highlight' : '' }}">
            <td>
                <strong>{{ $tier['plan'] }}</strong>
                @if($tier['active']) ← <span class="savings">{{ $t['your_plan'] }}</span> @endif
            </td>
            <td>{{ $tier['range'] }}</td>
            <td style="text-align:right">${{ number_format($tier['monthly'], 2) }}</td>
            <td style="text-align:right">${{ number_format($tier['annual'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<h2 class="section">🎯 {{ $t['your_quote'] }}</h2>
<table class="pricing">
    <tr>
        <td>{{ $t['monthly_total'] }} ({{ $qty }} u × ${{ number_format($selected['monthly'], 2) }})</td>
        <td style="text-align:right"><strong>${{ number_format($monthly_total, 2) }} USD/{{ $t['per_month'] }}</strong></td>
    </tr>
    <tr class="total">
        <td>{{ $t['annual_total'] }} ({{ $qty }} u × ${{ number_format($selected['annual'], 2) }})
            @if($savings_pct > 0) <span class="savings">— {{ $t['save'] }} {{ $savings_pct }}%</span> @endif
        </td>
        <td style="text-align:right"><strong>${{ number_format($annual_total, 2) }} USD/{{ $t['per_year'] }}</strong></td>
    </tr>
</table>

{{-- Qué incluye --}}
<h2 class="section">✨ {{ $t['included_title'] }}</h2>
<div class="features">
    <div class="feature-col">
        <ul>
            <li>{{ $t['feat_1'] }}</li>
            <li>{{ $t['feat_2'] }}</li>
            <li>{{ $t['feat_3'] }}</li>
            <li>{{ $t['feat_4'] }}</li>
            <li>{{ $t['feat_5'] }}</li>
            <li>{{ $t['feat_6'] }}</li>
        </ul>
    </div>
    <div class="feature-col">
        <ul>
            <li>{{ $t['feat_7'] }}</li>
            <li>{{ $t['feat_8'] }}</li>
            <li>{{ $t['feat_9'] }}</li>
            <li>{{ $t['feat_10'] }}</li>
            <li>{{ $t['feat_11'] }}</li>
            <li>{{ $t['feat_12'] }}</li>
        </ul>
    </div>
</div>

<div class="info-box blue">
    <strong>🌐 {{ $t['infra_title'] }}</strong>
    <p style="margin:4px 0 0">{{ $t['infra_desc'] }}</p>
</div>

@if($app_free)
<div class="info-box green">
    <strong>📱 {{ $t['app_free_title'] }}</strong>
    <p style="margin:4px 0 0">{{ $t['app_free_desc'] }}</p>
</div>
@endif

{{-- App móvil personalizada --}}
<h2 class="section">📱 {{ $t['branded_app_title'] }}</h2>
<table class="apps-table">
    <thead>
        <tr>
            <th style="width:30%">{{ $t['branded_app_option'] }}</th>
            <th style="width:25%;text-align:center">Android</th>
            <th style="width:25%;text-align:center">iOS</th>
            <th style="text-align:center">Android + iOS</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>{{ $t['branded_app_buy'] }}</strong></td>
            <td style="text-align:center">—</td>
            <td style="text-align:center">—</td>
            <td style="text-align:center;color:#0d6efd;font-weight:bold">$155 USD</td>
        </tr>
        <tr>
            <td><strong>{{ $t['branded_app_rent'] }}</strong></td>
            <td style="text-align:center">$12 USD/{{ $t['per_month'] }}</td>
            <td style="text-align:center">$15 USD/{{ $t['per_month'] }}</td>
            <td style="text-align:center;color:#6610f2;font-weight:bold">$20 USD/{{ $t['per_month'] }}</td>
        </tr>
        <tr>
            <td><strong>{{ $t['branded_app_free_row'] }}</strong></td>
            <td colspan="3" style="text-align:center;color:#198754;font-weight:bold">✅ {{ $t['branded_app_free_val'] }}</td>
        </tr>
    </tbody>
</table>
<div class="info-box blue" style="margin-top:6px;font-size:9.5px">
    {{ $t['branded_app_note'] }}
</div>

{{-- Apps del ecosistema --}}
<h2 class="section">🧩 {{ $t['apps_title'] }}</h2>
<p style="margin:0 0 6px;font-size:10px;color:#374151">{{ $t['apps_subtitle'] }}</p>
<table class="apps-table">
    <thead>
        <tr>
            <th style="width:18%">{{ $t['app_name_label'] }}</th>
            <th>{{ $t['app_desc_label'] }}</th>
            <th style="width:22%;text-align:center">{{ $t['app_price_label'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($apps as $app)
        <tr>
            <td><span class="app-name">{{ $app['icon'] }} {{ $app['name'] }}</span></td>
            <td>{{ $app['desc'] }}</td>
            <td style="text-align:center;color:#198754;font-weight:bold">{{ $t['apps_included'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- WhatsApp Integration --}}
<div class="wa-box">
    <strong>💬 {{ $t['wa_title'] }}</strong>
    <p style="margin:5px 0 0">{{ $t['wa_desc'] }}</p>
</div>

{{-- Pagos --}}
<h2 class="section">💳 {{ $t['payment_title'] }}</h2>
<div class="info-box yellow">
    <p style="margin:0">{{ $t['payment_desc'] }}</p>
    <p style="margin:6px 0 0">{{ $t['payment_methods'] }}</p>
</div>

{{-- Garantía --}}
<div class="guarantee-box">
    🛡️ <strong>{{ $t['guarantee_title'] }}</strong> — {{ $t['guarantee_desc'] }}
</div>

{{-- CTA --}}
<div class="cta">
    <h3>{{ $t['cta_title'] }}</h3>
    <p>{{ $t['cta_desc'] }}</p>
</div>

<div class="terms">
    <strong>{{ $t['terms_title'] }}</strong><br>
    {{ $t['terms_1'] }}<br>
    {{ $t['terms_2'] }}<br>
    {{ $t['terms_3'] }}
</div>

<div class="footer">
    APPOGIO · {{ $t['footer_brand'] }}<br>
    {{ $t['footer_offices'] }}
</div>

</body>
</html>
