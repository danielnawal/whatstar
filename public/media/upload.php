<?php
$TOKEN = 'appogio2026up';

$msg = '';
$uploaded = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($token = $_POST['token'] ?? '') !== $TOKEN) {
        $msg = 'Token incorrecto.';
    } elseif (empty($_FILES['archivo']['tmp_name'])) {
        $msg = 'No se recibio ningun archivo.';
    } else {
        $f = $_FILES['archivo'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $msg = 'Solo se permiten archivos PDF.';
        } elseif ($f['size'] > 20 * 1024 * 1024) {
            $msg = 'El archivo supera el limite de 20 MB.';
        } else {
            $dest = __DIR__ . '/' . basename($f['name']);
            if (move_uploaded_file($f['tmp_name'], $dest)) {
                $host = 'http://167.86.111.199/media/' . rawurlencode(basename($f['name']));
                $msg = 'OK';
                $uploaded = $host;
            } else {
                $msg = 'Error al guardar el archivo en el servidor.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Subir PDF — Appogio</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f4f6f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; border-radius: 8px; padding: 36px 40px; width: 100%; max-width: 480px; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
  h2 { font-size: 20px; color: #1a1a2e; margin-bottom: 6px; }
  p.sub { font-size: 13px; color: #666; margin-bottom: 24px; }
  label { display: block; font-size: 13px; font-weight: 600; color: #333; margin-bottom: 6px; }
  input[type=text], input[type=file] { width: 100%; border: 1px solid #ccc; border-radius: 6px; padding: 10px 12px; font-size: 14px; margin-bottom: 16px; }
  input[type=file] { padding: 8px; }
  button { width: 100%; background: #1a1a2e; color: #fff; border: none; border-radius: 6px; padding: 12px; font-size: 15px; font-weight: 600; cursor: pointer; }
  button:hover { background: #2d2d4e; }
  .alert { margin-top: 20px; padding: 12px 16px; border-radius: 6px; font-size: 14px; }
  .alert.ok { background: #e6f4ea; color: #2d6a4f; border: 1px solid #b7dfcc; }
  .alert.err { background: #fdecea; color: #b71c1c; border: 1px solid #f5c6c6; }
  .url-box { margin-top: 10px; background: #f0f0f0; border-radius: 5px; padding: 10px 12px; font-size: 13px; word-break: break-all; font-family: monospace; }
  .note { margin-top: 16px; font-size: 12px; color: #999; }
</style>
</head>
<body>
<div class="card">
  <h2>Subir PDF para campana</h2>
  <p class="sub">El archivo quedara alojado en el servidor y podra enviarse a los contactos via WhatsApp.</p>

  <?php if ($msg === 'OK'): ?>
    <div class="alert ok">
      Archivo subido correctamente.
      <div class="url-box"><?= htmlspecialchars($uploaded) ?></div>
    </div>
    <p class="note">Copie esa URL y compartala con quien configure el workflow, o deje que el asistente la use directamente.</p>
  <?php elseif ($msg): ?>
    <div class="alert err"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" style="margin-top: <?= $msg ? '20px' : '0' ?>">
    <label>Token de acceso</label>
    <input type="text" name="token" placeholder="appogio2026up" autocomplete="off">
    <label>Archivo PDF (max 20 MB)</label>
    <input type="file" name="archivo" accept=".pdf">
    <button type="submit">Subir archivo</button>
  </form>
</div>
</body>
</html>
