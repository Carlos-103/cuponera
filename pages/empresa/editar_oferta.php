<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireEmpresa();

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM ofertas WHERE id=? AND empresa_id=?");
$stmt->execute([$id, $_SESSION['user_id']]);
$oferta = $stmt->fetch();

if (!$oferta) { setFlash('danger','Oferta no encontrada.'); redirect('/pages/empresa/dashboard.php'); }

$errors = []; $data = $oferta;

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

    if (empty($data['titulo']))     $errors[] = 'El título es obligatorio.';
    if (!is_numeric($data['precio_regular']) || $data['precio_regular'] <= 0) $errors[] = 'Precio regular inválido.';
    if (!is_numeric($data['precio_oferta'])  || $data['precio_oferta'] <= 0)  $errors[] = 'Precio de oferta inválido.';
    if ($data['precio_oferta'] >= $data['precio_regular']) $errors[] = 'El precio de oferta debe ser menor al regular.';
    if (empty($data['descripcion'])) $errors[] = 'La descripción es obligatoria.';

    if (empty($errors)) {
        $cantidad = !empty($data['cantidad_cupones']) ? (int)$data['cantidad_cupones'] : null;
        $db->prepare("UPDATE ofertas SET titulo=?,precio_regular=?,precio_oferta=?,fecha_inicio=?,fecha_fin=?,fecha_limite_canje=?,cantidad_cupones=?,descripcion=?,estado=? WHERE id=? AND empresa_id=?")
           ->execute([$data['titulo'],$data['precio_regular'],$data['precio_oferta'],$data['fecha_inicio'],$data['fecha_fin'],$data['fecha_limite_canje'],$cantidad,$data['descripcion'],$data['estado'],$id,$_SESSION['user_id']]);
        setFlash('success','Oferta actualizada correctamente.');
        redirect('/pages/empresa/dashboard.php');
    }
}

$pageTitle = 'Editar oferta – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>
<div style="max-width:700px;margin:0 auto">
  <div class="auth-header"><div class="auth-logo">✏️</div><h1>Editar oferta</h1></div>
  <div class="card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" novalidate>
      <div class="form-group"><label>Título *</label><input type="text" name="titulo" value="<?= sanitize($data['titulo']) ?>" required></div>
      <div class="form-row">
        <div class="form-group"><label>Precio regular ($) *</label><input type="number" name="precio_regular" value="<?= $data['precio_regular'] ?>" step="0.01" required></div>
        <div class="form-group"><label>Precio oferta ($) *</label><input type="number" name="precio_oferta" value="<?= $data['precio_oferta'] ?>" step="0.01" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Fecha inicio *</label><input type="date" name="fecha_inicio" value="<?= $data['fecha_inicio'] ?>" required></div>
        <div class="form-group"><label>Fecha fin *</label><input type="date" name="fecha_fin" value="<?= $data['fecha_fin'] ?>" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Fecha límite canje *</label><input type="date" name="fecha_limite_canje" value="<?= $data['fecha_limite_canje'] ?>"></div>
        <div class="form-group"><label>Cantidad cupones (vacío = ilimitado)</label><input type="number" name="cantidad_cupones" value="<?= $data['cantidad_cupones'] ?>" min="1"></div>
      </div>
      <div class="form-group"><label>Descripción *</label><textarea name="descripcion" required><?= sanitize($data['descripcion']) ?></textarea></div>
      <div class="form-group"><label>Estado</label>
        <select name="estado">
          <option value="disponible"    <?= $data['estado']==='disponible'    ? 'selected':'' ?>>Disponible</option>
          <option value="no_disponible" <?= $data['estado']==='no_disponible' ? 'selected':'' ?>>No disponible</option>
        </select>
      </div>
      <div style="display:flex;gap:1rem">
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
        <a href="<?= BASE_URL ?>/pages/empresa/dashboard.php" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
