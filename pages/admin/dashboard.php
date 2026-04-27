<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $id     = (int)($_POST['empresa_id'] ?? 0);
    $accion = $_POST['accion'];
    if ($accion === 'aprobar') {
        $comision = (float)($_POST['comision'] ?? 0);
        if ($comision <= 0 || $comision > 100) {
            setFlash('danger', 'El porcentaje de comisión debe ser entre 1 y 100.');
        } else {
            $db->prepare("UPDATE empresas SET estado='aprobada', porcentaje_comision=?, aprobado_por=? WHERE id=? AND estado='pendiente'")
               ->execute([$comision, $_SESSION['user_id'], $id]);
            setFlash('success', 'Empresa aprobada correctamente.');
        }
    } elseif ($accion === 'rechazar') {
        $db->prepare("UPDATE empresas SET estado='rechazada' WHERE id=? AND estado='pendiente'")->execute([$id]);
        setFlash('danger', 'Empresa rechazada.');
    }
    redirect('/pages/admin/dashboard.php');
}

$pendientes = $db->query("SELECT * FROM empresas WHERE estado='pendiente' ORDER BY created_at DESC")->fetchAll();
$empresas   = $db->query("SELECT e.*, a.nombre AS admin_nombre FROM empresas e LEFT JOIN administradores a ON a.id=e.aprobado_por ORDER BY e.created_at DESC")->fetchAll();

$pageTitle = 'Panel de administrador – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Panel de administrador</h1>

<div style="display:flex;gap:1rem;margin-bottom:2rem;flex-wrap:wrap">
  <a href="<?= BASE_URL ?>/pages/admin/nuevo_admin.php" class="btn btn-primary">+ Nuevo administrador</a>
  <a href="<?= BASE_URL ?>/pages/admin/reportes.php" class="btn btn-dark">📊 Ver reportes</a>
</div>

<?php if ($pendientes): ?>
<div class="card">
  <h2 class="card-title">Solicitudes pendientes (<?= count($pendientes) ?>)</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Empresa</th><th>NIT</th><th>Correo</th><th>Teléfono</th><th>Fecha</th><th>Acción</th></tr></thead>
      <tbody>
        <?php foreach ($pendientes as $e): ?>
        <tr>
          <td><?= sanitize($e['nombre']) ?></td>
          <td><?= sanitize($e['nit']) ?></td>
          <td><?= sanitize($e['correo']) ?></td>
          <td><?= sanitize($e['telefono']) ?></td>
          <td><?= date('d/m/Y', strtotime($e['created_at'])) ?></td>
          <td style="display:flex;gap:.5rem;align-items:center">
            <form method="POST" style="display:flex;gap:.4rem;align-items:center" onsubmit="return confirm('¿Aprobar con este % de comisión?')">
              <input type="hidden" name="empresa_id" value="<?= $e['id'] ?>">
              <input type="hidden" name="accion" value="aprobar">
              <input type="number" name="comision" placeholder="% comisión" min="1" max="100" step="0.01" required
                     style="width:110px;padding:.35rem .5rem;border:2px solid #e9ecef;border-radius:8px;font-size:.85rem">
              <button class="btn btn-success btn-sm">Aprobar</button>
            </form>
            <form method="POST" onsubmit="return confirm('¿Rechazar esta empresa?')">
              <input type="hidden" name="empresa_id" value="<?= $e['id'] ?>">
              <input type="hidden" name="accion" value="rechazar">
              <button class="btn btn-danger btn-sm">Rechazar</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="alert alert-info">No hay solicitudes pendientes.</div>
<?php endif; ?>

<div class="card">
  <h2 class="card-title">Todas las empresas</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Empresa</th><th>NIT</th><th>Correo</th><th>Estado</th><th>Comisión</th><th>Aprobada por</th></tr></thead>
      <tbody>
        <?php foreach ($empresas as $e): ?>
        <?php $badge = match($e['estado']) { 'aprobada'=>'success','rechazada'=>'danger',default=>'warning' }; ?>
        <tr>
          <td><?= sanitize($e['nombre']) ?></td>
          <td><?= sanitize($e['nit']) ?></td>
          <td><?= sanitize($e['correo']) ?></td>
          <td><span class="badge badge-<?= $badge ?>"><?= $e['estado'] ?></span></td>
          <td><?= $e['porcentaje_comision'] ? $e['porcentaje_comision'].'%' : '—' ?></td>
          <td><?= sanitize($e['admin_nombre'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
