<?php
require_once __DIR__ . "/../Controllers/ExpedicionesController.php";
session_start();
$ctrl = new ExpedicionesController();
$ordenes = $ctrl->listarOrdenes();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Órdenes de carga · El Ciruelo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#0f1113;color:#e9eef4}
    .card{background:#1a1d21;border:1px solid #2a2f35;border-radius:12px}
    .badge{border-radius:10px}
    .btn-success{background:#30c375;border:none}
  </style>
</head>
<body class="p-3">
  <div class="container" style="max-width:560px;">
    <h4 class="mb-3">El Ciruelo · Órdenes de carga</h4>

    <?php if (!$ordenes): ?>
      <div class="alert alert-warning">No hay órdenes abiertas ni en curso.</div>
    <?php endif; ?>

    <?php foreach ($ordenes as $o): ?>
      <div class="card p-3 mb-3 text-light">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="badge <?= $o['estado']==='EN_CURSO'?'bg-warning text-dark':'bg-primary' ?>">
            <?= htmlspecialchars($o['estado']) ?>
          </span> 
        </div>
        <div class="fw-semibold"><?= htmlspecialchars($o['codigo']) ?></div>
        <div class="text-secondary mb-3"><?= htmlspecialchars($o['cliente']) ?></div>
        <form method="get" action="asignar.php" class="m-0">
          <input type="hidden" name="orden_id" value="<?= (int)$o['id'] ?>">
          <button class="btn btn-success w-100">Seleccionar orden</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html> 
