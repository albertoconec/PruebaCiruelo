<?php
require_once __DIR__ . "/../Controllers/ExpedicionesController.php";
session_start();

$ctrl = new ExpedicionesController();
$users = $ctrl->listarCarretilleros();

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $usuario_id = (int)($_POST['usuario_id'] ?? 0);
  if ($usuario_id) {
    $_SESSION['usuario_id'] = $usuario_id;
    header("Location: index.php");
    exit;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Entrar como carretillero</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#0f1113;color:#e9eef4}
    .card{background:#1a1d21;border:1px solid #2a2f35;border-radius:12px}
    .form-select{background:#22272c;color:#e9eef4;border:1px solid #2f353c}
    .btn-success{background:#30c375;border:none}
  </style>
</head>
<body class="p-3">
  <div class="container" style="max-width:520px;">
    <h4 class="mb-3 text-light">El Ciruelo · Selecciona carretillero</h4>
    <div class="card p-3">
      <form method="post">
        <div class="mb-3">
          <label class="form-label text-light">Carretillero</label>
          <select name="usuario_id" class="form-select" required>
            <option value="">Selecciona un usuario</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-success w-100">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>
