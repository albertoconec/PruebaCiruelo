<?php
require_once __DIR__ . "/../Controllers/ExpedicionesController.php";
session_start();
ini_set('display_errors',1); error_reporting(E_ALL);

$ctrl = new ExpedicionesController();
$ordenId = (int)($_GET['orden_id'] ?? $_POST['orden_id'] ?? 0);
if (!$ordenId) { header("Location: index.php"); exit; }

$msg=null; $tipo=null;

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['asignar'])) {
  $id_camion = (int)($_POST['id_camion'] ?? 0);
  $id_palet  = (int)($_POST['id_palet']  ?? 0);

  if (!$id_camion || !$id_palet) {
    $msg="Selecciona un camión e introduce un id_palet."; $tipo="danger";
  } else {
    $res = $ctrl->asignar($ordenId, $id_palet, $id_camion);
    if ($res['ok'] ?? false) {
      $q = http_build_query([
        'orden_id'     => $ordenId,
        'id_palet'     => $id_palet,
        'id_camion'    => $id_camion,
        'matricula'    => $res['camion']['matricula'] ?? '',
        'usados'       => $res['ocupacion']['usados'] ?? 0,
        'capacidad'    => $res['ocupacion']['capacidad'] ?? 0,
        'estado_carga' => $res['orden']['estado_carga'] ?? '',
      ]);
      header("Location: success.php?".$q);
      exit;
    } else {
      $msg = "✕ " . ($res['error'] ?? 'Error');
      $tipo = "warning";
    }
  }
}

$cargas = $ctrl->listarCargasDeOrden($ordenId);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Asignar palet · El Ciruelo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#0f1113;color:#e9eef4}
    .card{background:#1a1d21;border:1px solid #2a2f35;border-radius:12px}
    .form-select,.form-control{background:#22272c;color:#e9eef4;border:1px solid #2f353c}
    .btn-success{background:#30c375;border:none}
    a{color:#9cd3ff}
  </style>
</head>
<body class="p-3">
  <div class="container" style="max-width:560px;">
    <a href="index.php" class="text-decoration-none">&larr; Volver</a>
    <h4 class="mt-2 mb-3">Asignar palet a camión</h4>

    <?php if ($msg): ?><div class="alert alert-<?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="card p-3">
      <form method="post">
        <input type="hidden" name="orden_id" value="<?= $ordenId ?>">

        <!-- Camiones de la orden -->
        <div class="mb-3">
          <label class="form-label text-light">Camión de la orden</label>
          <select name="id_camion" class="form-select" required>
            <?php if (!$cargas): ?>
              <option value="">(Esta orden no tiene camiones asignados)</option>
            <?php else: foreach ($cargas as $c): 
              $restantes = max(0, (int)$c['capacidad'] - (int)$c['ocupados']);
            ?>
              <option value="<?= (int)$c['id_camion'] ?>">
                <?= htmlspecialchars($c['matricula']) ?> (<?= htmlspecialchars($c['estado']) ?>, quedan <?= $restantes ?>)
              </option>
            <?php endforeach; endif; ?>
          </select>
        </div>

        <!-- ID de palet a mano -->
        <div class="mb-3">
          <label class="form-label text-light">id_palet</label>
          <input class="form-control" type="number" name="id_palet" placeholder="Escribe el ID del palet" required>
        </div>

        <button type="submit" name="asignar" class="btn btn-success w-100">Asignar palet</button>
      </form>
    </div>
  </div>
</body>
</html>
