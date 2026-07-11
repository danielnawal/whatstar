<?php
// Token de acceso — cambia esto si quieres mayor seguridad
define('ACCESS_TOKEN', 'brasil2026');
define('UPLOAD_DIR',   '/root/uploads-brasil/');
define('MAX_SIZE_MB',  20);

// Verificar token
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($token !== ACCESS_TOKEN) {
    http_response_code(403);
    die('<h2 style="font-family:sans-serif;color:#c00;text-align:center;margin-top:80px">Acceso denegado.</h2>');
}

// Crear directorio de uploads si no existe
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

$mensaje = '';
$tipo    = '';

// Procesar subida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $file      = $_FILES['archivo'];
    $nombre    = basename($file['name']);
    $ext       = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    $tamano_mb = $file['size'] / 1024 / 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Error al subir el archivo (codigo ' . $file['error'] . ').';
        $tipo    = 'error';
    } elseif (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
        $mensaje = 'Solo se aceptan archivos .xlsx, .xls o .csv';
        $tipo    = 'error';
    } elseif ($tamano_mb > MAX_SIZE_MB) {
        $mensaje = 'El archivo excede el limite de ' . MAX_SIZE_MB . ' MB.';
        $tipo    = 'error';
    } else {
        // Nombre seguro con timestamp
        $nombre_seguro = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nombre);
        $destino       = UPLOAD_DIR . date('Ymd_His') . '_' . $nombre_seguro;
        if (move_uploaded_file($file['tmp_name'], $destino)) {
            $mensaje = 'Archivo subido correctamente: <strong>' . htmlspecialchars($nombre) . '</strong><br>Guardado en el servidor como: <code>' . basename($destino) . '</code>';
            $tipo    = 'ok';
        } else {
            $mensaje = 'Error al guardar el archivo en el servidor.';
            $tipo    = 'error';
        }
    }
}

// Listar archivos ya subidos
$archivos = glob(UPLOAD_DIR . '*') ?: [];
usort($archivos, fn($a, $b) => filemtime($b) - filemtime($a));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Subir listas Brasil</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f0f2f5; min-height: 100vh; display: flex; align-items: flex-start; justify-content: center; padding: 40px 16px; }
  .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.10); padding: 36px 40px; width: 100%; max-width: 560px; }
  h1 { font-size: 1.4rem; color: #1a1a1a; margin-bottom: 6px; }
  .sub { color: #666; font-size: .9rem; margin-bottom: 28px; }
  .drop-zone { border: 2px dashed #ccc; border-radius: 8px; padding: 36px 20px; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; color: #888; font-size: .95rem; }
  .drop-zone:hover, .drop-zone.over { border-color: #2563eb; background: #eff6ff; color: #2563eb; }
  .drop-zone input[type=file] { display: none; }
  .drop-zone .ico { font-size: 2.4rem; margin-bottom: 10px; }
  label.btn { display: inline-block; margin-top: 12px; background: #2563eb; color: #fff; padding: 8px 22px; border-radius: 6px; cursor: pointer; font-size: .9rem; }
  label.btn:hover { background: #1d4ed8; }
  .filename { margin-top: 10px; font-size: .85rem; color: #444; min-height: 18px; }
  button[type=submit] { width: 100%; margin-top: 20px; background: #16a34a; color: #fff; border: none; padding: 13px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background .2s; }
  button[type=submit]:hover { background: #15803d; }
  .alert { border-radius: 8px; padding: 14px 18px; margin-bottom: 24px; font-size: .92rem; line-height: 1.5; }
  .alert.ok    { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
  .alert.error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  .lista-titulo { margin-top: 32px; font-size: .85rem; font-weight: 600; color: #444; text-transform: uppercase; letter-spacing: .05em; border-top: 1px solid #eee; padding-top: 20px; }
  .lista { list-style: none; margin-top: 10px; }
  .lista li { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f3f3; font-size: .88rem; color: #333; }
  .lista li .fecha { color: #999; font-size: .78rem; }
  .tag { display: inline-block; background: #e0f2fe; color: #0369a1; border-radius: 4px; padding: 1px 7px; font-size: .75rem; font-weight: 600; }
</style>
</head>
<body>
<div class="card">
  <h1>Subir listas de Brasil</h1>
  <p class="sub">Sube los archivos Excel con los contactos de las campanas de Brasil.</p>

  <?php if ($mensaje): ?>
  <div class="alert <?= $tipo ?>"><?= $mensaje ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="frmUpload">
    <input type="hidden" name="token" value="<?= htmlspecialchars(ACCESS_TOKEN) ?>">
    <div class="drop-zone" id="dropZone">
      <div class="ico">📂</div>
      <div>Arrastra tu archivo aqui</div>
      <label class="btn" for="fileInput">Seleccionar archivo</label>
      <input type="file" id="fileInput" name="archivo" accept=".xlsx,.xls,.csv">
      <div class="filename" id="fileName">Ningún archivo seleccionado</div>
    </div>
    <button type="submit">Subir archivo</button>
  </form>

  <?php if ($archivos): ?>
  <p class="lista-titulo">Archivos subidos (<?= count($archivos) ?>)</p>
  <ul class="lista">
    <?php foreach (array_slice($archivos, 0, 10) as $f): ?>
    <li>
      <span>
        <span class="tag"><?= strtoupper(pathinfo($f, PATHINFO_EXTENSION)) ?></span>
        &nbsp;<?= htmlspecialchars(basename($f)) ?>
      </span>
      <span class="fecha"><?= date('d/m/Y H:i', filemtime($f)) ?></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>

<script>
const dz = document.getElementById('dropZone');
const fi = document.getElementById('fileInput');
const fn = document.getElementById('fileName');

fi.addEventListener('change', () => {
  fn.textContent = fi.files[0] ? fi.files[0].name : 'Ningún archivo seleccionado';
});
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('over'); });
dz.addEventListener('dragleave', () => dz.classList.remove('over'));
dz.addEventListener('drop', e => {
  e.preventDefault(); dz.classList.remove('over');
  const dt = e.dataTransfer;
  if (dt.files.length) {
    fi.files = dt.files; // solo funciona en algunos navegadores
    fn.textContent = dt.files[0].name;
    // Asignar via DataTransfer
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(dt.files[0]);
    fi.files = dataTransfer.files;
  }
});
</script>
</body>
</html>
