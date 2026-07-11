<?php
// Pagina de reconexion automatica para device_100
// No requiere login. Genera QR fresco en cada carga.

$waUrl     = 'http://127.0.0.1:8000';
$deviceId  = 'device_100';
$qrcode    = null;
$error     = null;

// 1. Eliminar sesion existente si esta en estado roto (no authenticated)
$statusResp = @file_get_contents("{$waUrl}/sessions/status/{$deviceId}");
if ($statusResp) {
    $statusData = json_decode($statusResp, true);
    $status = $statusData['data']['status'] ?? 'unknown';
    if ($status === 'authenticated') {
        // Ya esta conectado — no necesita QR
        header('Location: /');
        exit;
    }
    if (in_array($status, ['connected', 'connecting', 'disconnecting', 'disconnected'])) {
        // Sesion en estado intermedio — eliminar para poder crear nueva
        $ctx = stream_context_create(['http' => ['method' => 'DELETE']]);
        @file_get_contents("{$waUrl}/sessions/delete/{$deviceId}", false, $ctx);
        sleep(1);
    }
}

// 2. Crear nueva sesion QR
$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => json_encode(['id' => $deviceId, 'typeAuth' => 'qr']),
        'timeout' => 25,
    ],
]);

$resp = @file_get_contents("{$waUrl}/sessions/add", false, $ctx);
if ($resp) {
    $data = json_decode($resp, true);
    if (!empty($data['data']['qrcode'])) {
        $qrcode = $data['data']['qrcode'];
    } else {
        $error = 'El servidor no devolvio QR. Reintente en 10 segundos.';
    }
} else {
    $error = 'No se pudo conectar al servidor WhatsApp interno.';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reconectar Bot APPOGIO</title>
<style>
* { box-sizing: border-box; }
body { font-family: -apple-system, Arial, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
.card { background: white; border-radius: 16px; padding: 32px 28px; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,0.08); max-width: 400px; width: 94%; }
h2 { color: #1a1a1a; margin: 0 0 6px; font-size: 20px; }
.sub { color: #888; font-size: 13px; margin-bottom: 24px; }
.qr-wrap { display: inline-block; border: 2px solid #e0e0e0; border-radius: 12px; padding: 10px; margin-bottom: 18px; }
.qr-wrap img { display: block; width: 240px; height: 240px; }
.steps { background: #f8f9fa; border-radius: 10px; padding: 14px 16px; text-align: left; font-size: 13px; color: #444; margin-bottom: 16px; }
.steps ol { margin: 6px 0 0; padding-left: 18px; }
.steps li { margin-bottom: 4px; }
.btn { display: inline-block; background: #25D366; color: white; border: none; border-radius: 8px; padding: 11px 24px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; margin-top: 8px; }
.btn:hover { background: #1eb857; }
.error { background: #fff0f0; border: 1px solid #fcc; border-radius: 8px; padding: 14px; color: #c0392b; font-size: 14px; }
.timer { font-size: 12px; color: #aaa; margin-top: 14px; }
</style>
</head>
<body>
<div class="card">
  <h2>Reconectar Bot WhatsApp</h2>
  <p class="sub">Bot APPOGIO — +57 305 229 2372</p>

  <?php if ($qrcode): ?>
    <div class="qr-wrap">
      <img src="<?= htmlspecialchars($qrcode) ?>" alt="QR Code">
    </div>
    <div class="steps">
      <strong>Como escanear:</strong>
      <ol>
        <li>Abra WhatsApp en el telefono <strong>+57 305 229 2372</strong></li>
        <li>Configuracion &gt; Dispositivos vinculados</li>
        <li>Vincular dispositivo</li>
        <li>Apunte la camara al QR de esta pantalla</li>
      </ol>
    </div>
    <p class="timer">El QR expira en ~30 segundos. Si expira, <a href="">recargue la pagina</a>.</p>
  <?php else: ?>
    <div class="error"><?= htmlspecialchars($error ?? 'Error desconocido') ?></div>
    <br>
    <a href="" class="btn">Reintentar</a>
  <?php endif; ?>
</div>
</body>
</html>
