<?php
// Clave de acceso
define('CLAVE', 'brasil2026');

session_start();
if ($_POST['clave'] ?? '' === CLAVE) $_SESSION['ok'] = true;
if (!($_SESSION['ok'] ?? false)) {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Campana Brasil</title>
    <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:sans-serif;background:#0f172a;display:flex;align-items:center;justify-content:center;min-height:100vh}.card{background:#1e293b;padding:2rem;border-radius:12px;width:320px;text-align:center}h2{color:#fff;margin-bottom:1.5rem;font-size:1.3rem}input{width:100%;padding:.75rem;border:1px solid #334155;border-radius:8px;background:#0f172a;color:#fff;font-size:1rem;margin-bottom:1rem;outline:none}button{width:100%;padding:.75rem;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:1rem;cursor:pointer}button:hover{background:#2563eb}</style>
    </head><body><div class="card"><h2>Campana Brasil — Acceso</h2>
    <form method="post"><input type="password" name="clave" placeholder="Clave de acceso" autofocus>
    <button type="submit">Entrar</button></form></div></body></html>';
    exit;
}

// Leer datos
$PROGRESS_FILE = '/root/.n8n/campaigns_progress.json';
$DETALLE_FILE  = '/root/.n8n/brasil_detalle.json';
$CONTACTS_FILE = '/root/brasil-710.json';

$progress = json_decode(file_get_contents($PROGRESS_FILE), true)['campana_brasil_710'] ?? [];
$detalle  = json_decode(@file_get_contents($DETALLE_FILE) ?: '{}', true) ?: ['historial' => []];
$contacts = json_decode(@file_get_contents($CONTACTS_FILE) ?: '[]', true) ?: [];

$total     = $progress['total']      ?? 710;
$enviados  = $progress['enviados']   ?? 0;
$fallidos  = $progress['fallidos']   ?? 0;
$idx       = $progress['last_index'] ?? 0;
$status    = $progress['status']     ?? 'sin iniciar';
$updated   = $progress['updated_at'] ?? '';

$procesados = $idx;
$sin_wa     = $procesados - $enviados - $fallidos;
$pendientes = $total - $procesados;
$pct        = $total > 0 ? round($enviados / $total * 100, 1) : 0;

// Filtro de tabla
$filtro = $_GET['f'] ?? 'todos';
$buscar = strtolower(trim($_GET['q'] ?? ''));

$statusColor = [
    'completado'            => '#22c55e',
    'pausado_limite_diario' => '#f59e0b',
    'in_progress'           => '#3b82f6',
    'detenido'              => '#ef4444',
    'nuevo'                 => '#94a3b8',
    'sin iniciar'           => '#94a3b8',
];
$statusLabel = [
    'completado'            => 'Completada',
    'pausado_limite_diario' => 'En pausa (limite diario)',
    'in_progress'           => 'Enviando...',
    'detenido'              => 'Detenida',
    'nuevo'                 => 'Sin iniciar',
    'sin iniciar'           => 'Sin iniciar',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Campana Brasil — Dashboard</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;min-height:100vh}
.top{background:#1e293b;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:between;border-bottom:1px solid #334155}
.top h1{font-size:1.1rem;color:#fff;flex:1}
.badge{display:inline-block;padding:.25rem .75rem;border-radius:99px;font-size:.75rem;font-weight:600}
.container{max-width:1100px;margin:0 auto;padding:1.5rem}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}
.card{background:#1e293b;border-radius:10px;padding:1.25rem;text-align:center}
.card .val{font-size:2rem;font-weight:700;margin-bottom:.25rem}
.card .lbl{font-size:.75rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em}
.enviado{color:#22c55e}.sinwa{color:#94a3b8}.fallido{color:#ef4444}.pendiente{color:#3b82f6}.total{color:#f59e0b}
.progress-bar{background:#1e293b;border-radius:10px;padding:1.25rem;margin-bottom:1.5rem}
.bar-bg{background:#334155;border-radius:99px;height:12px;overflow:hidden;margin:.75rem 0}
.bar-fill{height:100%;background:linear-gradient(90deg,#3b82f6,#22c55e);border-radius:99px;transition:width .5s}
.bar-labels{display:flex;justify-content:space-between;font-size:.75rem;color:#94a3b8}
section{background:#1e293b;border-radius:10px;padding:1.25rem;margin-bottom:1.5rem}
section h2{font-size:.9rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:1rem}
.hist-table,.cont-table{width:100%;border-collapse:collapse;font-size:.85rem}
.hist-table th,.cont-table th{text-align:left;padding:.6rem .75rem;color:#94a3b8;border-bottom:1px solid #334155;font-weight:500}
.hist-table td,.cont-table td{padding:.6rem .75rem;border-bottom:1px solid #1e293b}
.hist-table tr:hover td,.cont-table tr:hover td{background:#334155}
.estado{display:inline-block;padding:.15rem .6rem;border-radius:99px;font-size:.7rem;font-weight:600}
.e-ENVIADO{background:#14532d;color:#4ade80}
.e-SIN_WA{background:#1e293b;color:#64748b}
.e-FALLIDO{background:#450a0a;color:#f87171}
.filters{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center}
.filters a{padding:.35rem .9rem;border-radius:99px;font-size:.8rem;text-decoration:none;background:#334155;color:#94a3b8}
.filters a.active{background:#3b82f6;color:#fff}
.search{padding:.35rem .75rem;border-radius:8px;background:#0f172a;border:1px solid #334155;color:#e2e8f0;font-size:.85rem;flex:1;min-width:180px}
.search:focus{outline:none;border-color:#3b82f6}
.refresh{float:right;font-size:.75rem;color:#3b82f6;text-decoration:none;padding:.3rem .75rem;border:1px solid #3b82f6;border-radius:99px}
.refresh:hover{background:#3b82f6;color:#fff}
.status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:.5rem}
</style>
</head>
<body>

<div class="top">
  <h1>Campana Brasil — GPS Companies</h1>
  &nbsp;&nbsp;
  <span class="badge" style="background:<?= $statusColor[$status] ?? '#94a3b8' ?>22;color:<?= $statusColor[$status] ?? '#94a3b8' ?>">
    <span class="status-dot" style="background:<?= $statusColor[$status] ?? '#94a3b8' ?>"></span>
    <?= htmlspecialchars($statusLabel[$status] ?? $status) ?>
  </span>
  &nbsp;&nbsp;
  <a href="?" class="refresh">Actualizar</a>
</div>

<div class="container">

  <!-- CARDS RESUMEN -->
  <div class="cards">
    <div class="card"><div class="val total"><?= $total ?></div><div class="lbl">Total</div></div>
    <div class="card"><div class="val enviado"><?= $enviados ?></div><div class="lbl">Enviados</div></div>
    <div class="card"><div class="val sinwa"><?= max(0,$sin_wa) ?></div><div class="lbl">Sin WhatsApp</div></div>
    <div class="card"><div class="val fallido"><?= $fallidos ?></div><div class="lbl">Fallidos</div></div>
    <div class="card"><div class="val pendiente"><?= $pendientes ?></div><div class="lbl">Pendientes</div></div>
  </div>

  <!-- BARRA PROGRESO -->
  <div class="progress-bar">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem">
      <span style="font-size:.9rem;color:#fff">Progreso general</span>
      <span style="font-size:1.1rem;font-weight:700;color:#22c55e"><?= $pct ?>%</span>
    </div>
    <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
    <div class="bar-labels">
      <span><?= $enviados ?> enviados</span>
      <span>Procesados: <?= $procesados ?> / <?= $total ?></span>
      <?php if ($updated): ?>
      <span>Ultima act: <?= date('d/m H:i', strtotime($updated)) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <!-- HISTORIAL POR DIA -->
  <section>
    <h2>Historial por dia</h2>
    <?php if (empty($detalle['historial'])): ?>
      <p style="color:#64748b;font-size:.85rem">Sin historial todavia — aparece despues del primer envio.</p>
    <?php else: ?>
    <table class="hist-table">
      <thead><tr>
        <th>Fecha</th><th>Enviados</th><th>Sin WhatsApp</th><th>Fallidos</th><th>Total procesados</th>
      </tr></thead>
      <tbody>
      <?php foreach ($detalle['historial'] as $dia): ?>
        <?php $total_dia = count($dia['contactos'] ?? []); ?>
        <tr>
          <td><?= htmlspecialchars($dia['fecha']) ?></td>
          <td style="color:#4ade80"><?= (int)($dia['enviados'] ?? 0) ?></td>
          <td style="color:#64748b"><?= (int)($dia['sin_wa'] ?? 0) ?></td>
          <td style="color:#f87171"><?= (int)($dia['fallidos'] ?? 0) ?></td>
          <td><?= $total_dia ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>

  <!-- TABLA DE CONTACTOS -->
  <section>
    <h2>Contactos</h2>
    <form method="get" style="display:contents">
    <div class="filters">
      <a href="?f=todos" class="<?= $filtro==='todos'?'active':'' ?>">Todos</a>
      <a href="?f=ENVIADO" class="<?= $filtro==='ENVIADO'?'active':'' ?>">Enviados</a>
      <a href="?f=SIN_WA" class="<?= $filtro==='SIN_WA'?'active':'' ?>">Sin WhatsApp</a>
      <a href="?f=FALLIDO" class="<?= $filtro==='FALLIDO'?'active':'' ?>">Fallidos</a>
      <a href="?f=pendiente" class="<?= $filtro==='pendiente'?'active':'' ?>">Pendientes</a>
      <input class="search" type="text" name="q" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar nombre o numero..." oninput="this.form.submit()">
      <input type="hidden" name="f" value="<?= htmlspecialchars($filtro) ?>">
    </div>
    </form>

    <?php
    // Construir lista de contactos con estado
    $procesados_map = [];
    foreach ($detalle['historial'] as $dia) {
        foreach ($dia['contactos'] ?? [] as $c) {
            $tel = preg_replace('/\D/','',$c['numero'] ?? '');
            $procesados_map[$tel] = $c;
        }
    }

    $rows = [];
    foreach ($contacts as $i => $c) {
        $tel = preg_replace('/\D/','',$c['phone'] ?? '');
        if (isset($procesados_map[$tel])) {
            $r = $procesados_map[$tel];
            $rows[] = ['index'=>$i+1,'telefono'=>$tel,'nombre'=>$c['name']??'','ciudad'=>$c['city']??'','tipo'=>$c['tipo']??'','estado'=>$r['estado']??'?','ts'=>$r['timestamp']??'','error'=>$r['error']??''];
        } elseif ($i < $procesados) {
            // Procesado pero sin detalle (ejecucion anterior sin dashboard)
            $rows[] = ['index'=>$i+1,'telefono'=>$tel,'nombre'=>$c['name']??'','ciudad'=>$c['city']??'','tipo'=>$c['tipo']??'','estado'=>'SIN_DATOS','ts'=>'','error'=>''];
        } else {
            $rows[] = ['index'=>$i+1,'telefono'=>$tel,'nombre'=>$c['name']??'','ciudad'=>$c['city']??'','tipo'=>$c['tipo']??'','estado'=>'PENDIENTE','ts'=>'','error'=>''];
        }
    }

    // Filtrar
    $rows_filtrados = array_filter($rows, function($r) use ($filtro, $buscar) {
        if ($filtro === 'pendiente' && $r['estado'] !== 'PENDIENTE') return false;
        if ($filtro !== 'todos' && $filtro !== 'pendiente' && $r['estado'] !== $filtro) return false;
        if ($buscar && stripos($r['nombre'].$r['telefono'].$r['ciudad'], $buscar) === false) return false;
        return true;
    });
    $count = count($rows_filtrados);
    ?>
    <p style="font-size:.8rem;color:#64748b;margin-bottom:.75rem"><?= $count ?> contactos</p>
    <div style="overflow-x:auto">
    <table class="cont-table">
      <thead><tr>
        <th>#</th><th>Nombre</th><th>Telefono</th><th>Ciudad</th><th>Estado</th><th>Hora</th>
      </tr></thead>
      <tbody>
      <?php foreach (array_slice($rows_filtrados, 0, 500) as $r): ?>
        <tr>
          <td style="color:#64748b"><?= $r['index'] ?></td>
          <td><?= htmlspecialchars($r['nombre']) ?></td>
          <td style="font-family:monospace"><?= htmlspecialchars($r['telefono']) ?></td>
          <td style="color:#94a3b8"><?= htmlspecialchars($r['ciudad']) ?></td>
          <td><span class="estado e-<?= htmlspecialchars($r['estado']) ?>"><?= htmlspecialchars($r['estado']) ?></span></td>
          <td style="color:#64748b;font-size:.75rem"><?= $r['ts'] ? date('d/m H:i', strtotime($r['ts'])) : '' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($count > 500): ?>
        <tr><td colspan="6" style="text-align:center;color:#64748b;padding:1rem">Mostrando 500 de <?= $count ?>. Use el buscador para filtrar.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </section>

</div>
</body>
</html>
