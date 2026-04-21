<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireEmpresa();

$errors = []; $data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'titulo'             => trim($_POST['titulo']             ?? ''),
        'precio_regular'     => trim($_POST['precio_regular']     ?? ''),
        'precio_oferta'      => trim($_POST['precio_oferta']      ?? ''),
        'fecha_inicio'       => trim($_POST['fecha_inicio']       ?? ''),
        'fecha_fin'          => trim($_POST['fecha_fin']          ?? ''),
        'fecha_limite_canje' => trim($_POST['fecha_limite_canje'] ?? ''),
        'cantidad_cupones'   => trim($_POST['cantidad_cupones']   ?? ''),
        'descripcion'        => trim($_POST['descripcion']        ?? ''),
        'estado'             => $_POST['estado']                  ?? 'disponible',
    ];

    if (empty($data['titulo']))         $errors[] = 'El título es obligatorio.';
    if (!is_numeric($data['precio_regular']) || $data['precio_regular'] <= 0) $errors[] = 'El precio regular debe ser mayor a 0.';
    if (!is_numeric($data['precio_oferta'])  || $data['precio_oferta'] <= 0)  $errors[] = 'El precio de oferta debe ser mayor a 0.';
    if (!empty($data['precio_oferta']) && !empty($data['precio_regular']) && $data['precio_oferta'] >= $data['precio_regular']) $errors[] = 'El precio de oferta debe ser menor al precio regular.';
    if (empty($data['fecha_inicio']))   $errors[] = 'La fecha de inicio es obligatoria.';
    if (empty($data['fecha_fin']))      $errors[] = 'La fecha de fin es obligatoria.';
    if (empty($data['fecha_limite_canje'])) $errors[] = 'La fecha límite de canje es obligatoria.';
    if (!empty($data['fecha_inicio']) && !empty($data['fecha_fin']) && $data['fecha_fin'] < $data['fecha_inicio']) $errors[] = 'La fecha de fin no puede ser anterior a la de inicio.';
    if (empty($data['descripcion']))    $errors[] = 'La descripción es obligatoria.';

    if (empty($errors)) {
        $cantidad = !empty($data['cantidad_cupones']) ? (int)$data['cantidad_cupones'] : null;
        getDB()->prepare("
            INSERT INTO ofertas (empresa_id,titulo,precio_regular,precio_oferta,fecha_inicio,fecha_fin,fecha_limite_canje,cantidad_cupones,descripcion,estado)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $_SESSION['user_id'], $data['titulo'], $data['precio_regular'], $data['precio_oferta'],
            $data['fecha_inicio'], $data['fecha_fin'], $data['fecha_limite_canje'],
            $cantidad, $data['descripcion'], $data['estado']
        ]);
        setFlash('success', '¡Oferta publicada correctamente!');
        redirect('/pages/empresa/dashboard.php');
    }
}

$pageTitle = 'Nueva oferta – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="max-width:700px;margin:0 auto">
  <div class="auth-header">
    <div class="auth-logo">🎟️</div>
    <h1>Nueva oferta</h1>
    <p>Publica un cupón de descuento para tus clientes</p>
  </div>
  <div class="card">
    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= sanitize($e) ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label>Título de la oferta *</label>
        <input type="text" name="titulo" value="<?= sanitize($data['titulo']??'') ?>" placeholder="Ej: 50% en pizza familiar" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Precio regular ($) *</label>
          <input type="number" name="precio_regular" value="<?= sanitize($data['precio_regular']??'') ?>" min="0.01" step="0.01" required>
        </div>
        <div class="form-group">
          <label>Precio de oferta ($) *</label>
          <input type="number" name="precio_oferta" value="<?= sanitize($data['precio_oferta']??'') ?>" min="0.01" step="0.01" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Fecha de inicio *</label>
          <input type="date" name="fecha_inicio" value="<?= sanitize($data['fecha_inicio']??'') ?>" required>
        </div>
        <div class="form-group">
          <label>Fecha de fin *</label>
          <input type="date" name="fecha_fin" value="<?= sanitize($data['fecha_fin']??'') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Fecha límite para canjear *</label>
          <input type="date" name="fecha_limite_canje" value="<?= sanitize($data['fecha_limite_canje']??'') ?>" required>
        </div>
        <div class="form-group">
          <label>Cantidad de cupones (vacío = ilimitado)</label>
          <input type="number" name="cantidad_cupones" value="<?= sanitize($data['cantidad_cupones']??'') ?>" min="1" placeholder="Sin límite">
        </div>
      </div>
      <div class="form-group">
        <label>Descripción *</label>
        <textarea name="descripcion" required><?= sanitize($data['descripcion']??'') ?></textarea>
      </div>
      <div class="form-group">
        <label>Estado</label>
        <select name="estado">
          <option value="disponible"    <?= (($data['estado']??'disponible')==='disponible')    ? 'selected':'' ?>>Disponible</option>
          <option value="no_disponible" <?= (($data['estado']??'')==='no_disponible') ? 'selected':'' ?>>No disponible</option>
        </select>
      </div>
      <div style="display:flex;gap:1rem">
        <button type="submit" class="btn btn-primary">Publicar oferta</button>
        <a href="<?= BASE_URL ?>/pages/empresa/dashboard.php" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
