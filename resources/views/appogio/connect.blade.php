<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Conectar WhatsApp</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
    <link rel="stylesheet" href="{{ asset('assets/vendor/@fortawesome/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/argon.css') }}">
    <style>
        body { background: #f4f6f9; }
        .appogio-wrap { max-width: 860px; margin: 40px auto; padding: 0 15px; }
        .brand-bar { background: #1a4b8c; padding: 18px 28px; border-radius: 10px 10px 0 0;
                     display: flex; align-items: center; gap: 12px; }
        .brand-bar h4 { color: #fff; margin: 0; font-size: 1.1rem; }
        .brand-bar small { color: rgba(255,255,255,.7); display: block; font-size: .78rem; }
        .card-main { background: #fff; border-radius: 0 0 10px 10px;
                     box-shadow: 0 4px 20px rgba(0,0,0,.08); }

        /* QR Area */
        .qr-box { text-align: center; padding: 32px 20px; }
        .qr-box img.qr-img { max-width: 240px; border: 3px solid #e9ecef; border-radius: 8px; padding: 6px; }
        .qr-spinner { display: inline-block; }
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px;
                        font-size: .82rem; font-weight: 600; margin-top: 10px; }
        .badge-waiting  { background: #fff3cd; color: #856404; }
        .badge-scanning { background: #cff4fc; color: #055160; }
        .badge-connected{ background: #d1e7dd; color: #0f5132; }
        .badge-error    { background: #f8d7da; color: #842029; }

        /* Keys panel */
        .keys-panel { background: #f8f9fa; border-top: 1px solid #dee2e6; padding: 24px 28px; display: none; }
        .keys-panel h5 { color: #1a4b8c; margin-bottom: 16px; font-size: .95rem; }
        .key-row { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
        .key-label { font-size: .78rem; font-weight: 700; color: #6c757d;
                     text-transform: uppercase; width: 70px; flex-shrink: 0; }
        .key-value { font-family: monospace; font-size: .85rem; background: #fff;
                     border: 1px solid #ced4da; border-radius: 6px;
                     padding: 7px 12px; flex: 1; min-width: 200px; word-break: break-all; }
        .btn-copy { background: #1a4b8c; color: #fff; border: none; border-radius: 6px;
                    padding: 7px 14px; font-size: .8rem; cursor: pointer; white-space: nowrap; }
        .btn-copy:hover { background: #163f78; }
        .btn-copy.copied { background: #198754; }

        /* Webhook URL */
        .webhook-box { background: #e8f0fe; border-left: 4px solid #1a4b8c;
                       border-radius: 0 8px 8px 0; padding: 14px 18px; margin-top: 20px; }
        .webhook-box p { margin: 0 0 6px; font-size: .82rem; color: #1a4b8c; font-weight: 700; }
        .webhook-url  { font-family: monospace; font-size: .78rem; word-break: break-all;
                        color: #333; background: #fff; border-radius: 6px;
                        padding: 8px 12px; display: block; margin-top: 6px; border: 1px solid #b8cef0; }

        /* Steps */
        .steps { padding: 20px 28px; border-top: 1px solid #dee2e6; }
        .step  { display: flex; gap: 14px; margin-bottom: 14px; align-items: flex-start; }
        .step-num { background: #1a4b8c; color: #fff; border-radius: 50%;
                    width: 28px; height: 28px; display: flex; align-items: center;
                    justify-content: center; font-size: .78rem; font-weight: 700; flex-shrink: 0; }
        .step-text { font-size: .87rem; color: #495057; line-height: 1.5; }

        /* Regenerate */
        .regen-area { padding: 12px 28px 20px; text-align: right; }
        .btn-regen { background: transparent; border: 1px solid #dc3545; color: #dc3545;
                     border-radius: 6px; padding: 6px 14px; font-size: .8rem; cursor: pointer; }
        .btn-regen:hover { background: #dc3545; color: #fff; }

        #btn-start { margin-top: 12px; }
    </style>
</head>
<body>
<div class="appogio-wrap">

    <div class="brand-bar">
        {{-- Logo WhatStar --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 46 46" width="42" height="42" style="flex-shrink:0;">
            <rect width="46" height="46" rx="10" fill="#25d366"/>
            <path fill="#ffffff" d="M23 7C14.163 7 7 14.163 7 23c0 2.833.737 5.49 2.028 7.8L7 39l8.47-2.007A15.93 15.93 0 0023 39c8.837 0 16-7.163 16-16S31.837 7 23 7zm0 29.2a13.14 13.14 0 01-6.71-1.842l-.48-.285-4.97 1.177 1.22-4.84-.314-.497A13.147 13.147 0 019.8 23C9.8 15.71 15.71 9.8 23 9.8S36.2 15.71 36.2 23 30.29 36.2 23 36.2zm7.22-9.85c-.395-.197-2.338-1.153-2.7-1.285-.36-.13-.624-.196-.888.198-.263.394-1.02 1.285-1.25 1.547-.23.263-.46.296-.855.099-.395-.198-1.667-.615-3.176-1.96-1.174-1.047-1.967-2.34-2.196-2.735-.23-.394-.025-.608.172-.804.178-.177.395-.46.593-.69.197-.23.263-.395.395-.658.13-.264.065-.494-.033-.69-.1-.198-.888-2.143-1.217-2.934-.32-.77-.645-.665-.888-.677-.23-.011-.494-.014-.757-.014-.263 0-.69.099-1.052.494-.362.395-1.38 1.349-1.38 3.29 0 1.94 1.414 3.815 1.611 4.079.197.263 2.783 4.25 6.745 5.962.943.407 1.679.65 2.252.832.946.3 1.807.258 2.488.156.759-.113 2.338-.956 2.667-1.879.33-.922.33-1.713.23-1.879-.098-.164-.362-.263-.757-.46z"/>
        </svg>
        <div>
            <h4 style="font-size:1.25rem; letter-spacing:.5px;">WhatStar</h4>
            <small>Conectar WhatsApp · Dispositivo: {{ $app->title ?? $device?->name ?? 'Sin nombre' }}</small>
        </div>
    </div>

    <div class="card-main">

        {{-- Panel QR --}}
        <div class="qr-box" id="qr-section">
            <div id="qr-area">
                <div class="qr-spinner">
                    <i class="fas fa-circle-notch fa-spin fa-3x" style="color:#1a4b8c;"></i>
                </div>
                <p style="margin-top:12px; color:#6c757d; font-size:.88rem;">
                    Presiona <strong>Iniciar sesión</strong> para generar el código QR.
                </p>
            </div>
            <div>
                <span id="status-badge" class="status-badge badge-waiting">
                    <i class="fas fa-circle-notch fa-spin me-1"></i> Esperando...
                </span>
            </div>

            @if(!$device || $device->status != 1)
                <button id="btn-start" class="btn btn-primary mt-3">
                    <i class="fas fa-qrcode me-1"></i> Iniciar sesión
                </button>
            @else
                <div style="margin-top:16px;">
                    <img src="{{ asset('uploads/connected.png') }}" style="max-width:80px;" alt="Conectado">
                </div>
            @endif
        </div>

        {{-- Panel de claves (se muestra tras conectar) --}}
        <div class="keys-panel" id="keys-panel"
             @if($device && $device->status == 1) style="display:block;" @endif>
            <h5><i class="fas fa-key me-2"></i>Claves para configurar en tu software GPS</h5>

            <div class="key-row">
                <span class="key-label">App Key</span>
                <span class="key-value" id="val-appkey">{{ $app->key }}</span>
                <button class="btn-copy" onclick="copyKey('val-appkey', this)">
                    <i class="fas fa-copy me-1"></i>Copiar
                </button>
            </div>

            <div class="key-row">
                <span class="key-label">Out Key</span>
                <span class="key-value" id="val-outkey">
                    {{ $outkey ?? '— genera un token primero —' }}
                </span>
                <button class="btn-copy" onclick="copyKey('val-outkey', this)">
                    <i class="fas fa-copy me-1"></i>Copiar
                </button>
            </div>

            @if(!$outkey)
            <div style="margin-top:4px;">
                <button id="btn-gen-token" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sync me-1"></i>Generar Out Key
                </button>
            </div>
            @endif

            <div class="webhook-box">
                <p><i class="fas fa-link me-1"></i>URL webhook para tu software GPS</p>
                <code class="webhook-url" id="webhook-url">
                    {{ url('/appogio/alert') }}?appkey={{ $app->key }}&amp;outkey={{ $outkey ?? '{OUT_KEY}' }}&amp;to={TELEFONO}&amp;unit={UNIDAD}&amp;event={EVENTO}&amp;speed={VELOCIDAD}&amp;lat={LAT}&amp;lng={LNG}&amp;address={DIRECCION}
                </code>
                <button class="btn-copy" style="margin-top:8px;" onclick="copyKey('webhook-url', this)">
                    <i class="fas fa-copy me-1"></i>Copiar URL
                </button>
            </div>
        </div>

        {{-- Pasos de configuración --}}
        <div class="steps">
            <div style="font-weight:700; color:#1a4b8c; margin-bottom:12px; font-size:.9rem;">
                <i class="fas fa-list-ol me-2"></i>¿Cómo conectar?
            </div>
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-text">Presiona <strong>Iniciar sesión</strong> y espera el código QR.</div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-text">Abre WhatsApp en tu celular → <strong>Ajustes → Dispositivos vinculados → Vincular dispositivo</strong>.</div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-text">Escanea el QR con tu teléfono.</div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-text">Una vez conectado, copia el <strong>App Key</strong> y el <strong>Out Key</strong>, luego genera el Out Key si aún no existe.</div>
            </div>
            <div class="step">
                <div class="step-num">5</div>
                <div class="step-text">Ingresa esas claves en la configuración de notificaciones de tu software GPS junto con la URL webhook.</div>
            </div>
        </div>

        {{-- Regenerar token --}}
        <div class="regen-area" id="regen-area"
             @if(!$device || $device->status != 1) style="display:none;" @endif>
            <button class="btn-regen" id="btn-regen">
                <i class="fas fa-redo me-1"></i>Regenerar Out Key
            </button>
        </div>

    </div>{{-- .card-main --}}
</div>

<input type="hidden" id="app-uuid" value="{{ $app->uuid }}">
<input type="hidden" id="base-url" value="{{ url('/') }}">
<input type="hidden" id="device-status" value="{{ $device?->status ?? 0 }}">

<script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
<script>
const appUuid   = $('#app-uuid').val();
const baseUrl   = $('#base-url').val();
let deviceStatus = parseInt($('#device-status').val());
let qrAttempts   = 0;
let checkAttempts= 0;
let qrInterval, checkInterval;

// ── Iniciar sesión ──────────────────────────────────────────
$('#btn-start').on('click', function () {
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cargando...');
    setStatus('scanning', 'Solicitando código QR...');
    requestQr();
    checkInterval = setInterval(checkSession, 5000);
    qrInterval    = setInterval(requestQr, 14000);
});

// ── Pedir QR ────────────────────────────────────────────────
function requestQr() {
    qrAttempts++;
    if (qrAttempts > 8) { clearInterval(qrInterval); return; }

    $.post(baseUrl + '/appogio/get-qr/' + appUuid)
        .done(function (res) {
            if (res.qr) {
                $('#qr-area').html('<img src="' + res.qr + '" class="qr-img" alt="QR">');
                setStatus('scanning', 'Escanea el QR con WhatsApp');
            }
        })
        .fail(function () {
            setStatus('error', 'Error al obtener QR. Recarga la página.');
        });
}

// ── Verificar sesión ────────────────────────────────────────
function checkSession() {
    checkAttempts++;
    if (checkAttempts > 20) { clearInterval(checkInterval); return; }

    $.post(baseUrl + '/appogio/check-session/' + appUuid)
        .done(function (res) {
            if (res.connected) {
                clearInterval(qrInterval);
                clearInterval(checkInterval);
                onConnected(res.appkey, res.outkey);
            }
        });
}

// ── Conectado ───────────────────────────────────────────────
function onConnected(appkey, outkey) {
    setStatus('connected', '¡WhatsApp conectado!');
    $('#qr-area').html('<img src="{{ asset("uploads/connected.png") }}" style="max-width:80px;" alt="Conectado">');
    $('#btn-start').hide();

    if (appkey) $('#val-appkey').text(appkey);
    if (outkey) {
        $('#val-outkey').text(outkey);
        updateWebhookUrl(appkey, outkey);
    }

    $('#keys-panel').slideDown(400);
    $('#regen-area').show();
    $('#btn-gen-token').parent().hide();

    if (!outkey) {
        generateToken();
    }
}

// ── Generar token ───────────────────────────────────────────
function generateToken() {
    $.post(baseUrl + '/appogio/generate-token/' + appUuid)
        .done(function (res) {
            if (res.outkey) {
                $('#val-outkey').text(res.outkey);
                const appkey = $('#val-appkey').text();
                updateWebhookUrl(appkey, res.outkey);
                $('#btn-gen-token').parent().hide();
            }
        });
}

$('#btn-gen-token, #btn-regen').on('click', function () {
    if ($(this).is('#btn-regen') && !confirm('¿Regenerar el Out Key? La URL anterior dejará de funcionar.')) return;
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    generateToken();
    setTimeout(() => $(this).prop('disabled', false).html('<i class="fas fa-redo me-1"></i>Regenerar Out Key'), 2000);
});

// ── Actualizar URL del webhook ───────────────────────────────
function updateWebhookUrl(appkey, outkey) {
    const base = baseUrl + '/appogio/alert';
    const url  = base + '?appkey=' + appkey + '&outkey=' + outkey
               + '&to={TELEFONO}&unit={UNIDAD}&event={EVENTO}'
               + '&speed={VELOCIDAD}&lat={LAT}&lng={LNG}&address={DIRECCION}';
    $('#webhook-url').text(url);
}

// ── Copiar al portapapeles ───────────────────────────────────
function copyKey(elemId, btn) {
    const text = document.getElementById(elemId).innerText.trim();
    navigator.clipboard.writeText(text).then(function () {
        const prev = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copiado';
        btn.classList.add('copied');
        setTimeout(function () { btn.innerHTML = prev; btn.classList.remove('copied'); }, 2000);
    });
}

// ── Badge de estado ──────────────────────────────────────────
function setStatus(type, text) {
    const icons = {
        waiting:  'fas fa-circle-notch fa-spin',
        scanning: 'fas fa-qrcode',
        connected:'fas fa-check-circle',
        error:    'fas fa-exclamation-triangle',
    };
    $('#status-badge')
        .attr('class', 'status-badge badge-' + type)
        .html('<i class="' + icons[type] + ' me-1"></i>' + text);
}

// ── Si ya estaba conectado, verificar de inmediato ───────────
if (deviceStatus == 1) {
    setStatus('connected', 'WhatsApp conectado');
}
</script>
</body>
</html>
