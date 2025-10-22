<?php
$id_palet     = (int)($_GET['id_palet'] ?? 0);
$id_camion    = (int)($_GET['id_camion'] ?? 0);
$ordenId      = (int)($_GET['orden_id'] ?? 0);
$matricula    = htmlspecialchars($_GET['matricula'] ?? '');
$usados       = (int)($_GET['usados'] ?? 0);
$capacidad    = (int)($_GET['capacidad'] ?? 0);
$estadoCarga  = htmlspecialchars($_GET['estado_carga'] ?? '');
$restantes    = max(0, $capacidad - $usados);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>¡Asignación exitosa!</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#0f1113;color:#e9eef4}
    .card{background:#1a1d21;border:1px solid #2a2f35;border-radius:12px}
    .success-circle{width:88px;height:88px;border-radius:50%;background:rgba(48,195,117,.15);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;border:1px solid rgba(48,195,117,.35)}
    .success-circle svg{width:40px;height:40px;color:#30c375}
    .panel{border:1px solid #30c375;border-radius:12px;padding:16px;background:#15191c}
    .row-info{display:flex;gap:12px;align-items:center;background:#20262b;border:1px solid #2e343a;border-radius:10px;padding:12px;margin-bottom:10px}
    .row-info .label{color:#cfd5dc;font-weight:600;min-width:110px}
    .btn-success{background:#30c375;border:none}
    .badge-done{background:#198754}
    .badge-closed{background:#ffc107;color:#111}
    a{color:#9cd3ff}
  </style>
</head>
<body class="p-3">
  <div class="container" style="max-width:520px;">
    <div class="text-center mb-3">
      <div class="success-circle">
        <svg viewBox="0 0 24 24" fill="none"><path d="M20 7L9 18l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <h4 class="mb-1">¡Asignación exitosa!</h4>
      <div class="text-secondary">El palet se asignó correctamente al camión.</div>
    </div>

    <div class="panel mb-3">
      <div class="mb-2 fw-semibold">Resumen</div>
      <div class="row-info"><div class="label">Palet</div><div>#<?= $id_palet ?></div></div>
      <div class="row-info"><div class="label">Camión</div><div><?= $matricula ?> (id <?= $id_camion ?>)</div></div>
      <div class="row-info"><div class="label">Orden</div><div>#<?= $ordenId ?></div></div>
      <div class="row-info"><div class="label">Ocupación</div><div><?= $usados ?>/<?= $capacidad ?> (restan <?= $restantes ?>)</div></div>
      <div class="row-info"><div class="label">Estado carga</div>
        <div>
          <?php if ($estadoCarga==='CERRADA'): ?>
            <span class="badge badge-closed">CERRADA (camión lleno)</span>
          <?php else: ?>
            <span class="badge badge-done"><?= htmlspecialchars($estadoCarga ?: 'ABIERTA') ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <a class="btn btn-success w-100 mb-2" href="asignar.php?orden_id=<?= $ordenId ?>">Asignar otro palet</a>
    <a class="btn btn-outline-light w-100" href="index.php">Volver a órdenes</a>
  </div>
</body>
</html>
