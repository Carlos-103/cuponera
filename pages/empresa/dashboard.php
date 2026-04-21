<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireEmpresa();

$db         = getDB();
$empresa_id = $_SESSION['user_id'];

$empresa = $db->prepare("SELECT * FROM empresas WHERE id=?");
$empresa->execute([$empresa_id]);
$empresa = $empresa->fetch();

$ofertas = $db->prepare("SELECT * FROM ofertas WHERE empresa_id=? ORDER BY created_at DESC");
$ofertas->execute([$empresa_id]);
$ofertas = $ofertas->fetchAll();

$total_ventas = $db->prepare("SELECT COUNT(*) as total FROM compras c JOIN ofertas o ON o.id=c.oferta_id WHERE o.empresa_id=?");
$total_ventas->execute([$empresa_id]);
$total_ventas = $total_ventas->fetch()['total'];

$pageTitle = 'Panel de empresa – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Panel de empresa</h1>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-num"><?= count($ofertas) ?></div>
    <div class="stat-label">Ofertas publicadas</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $total_ventas ?></div>
    <div class="stat-label">Cupones vendidos</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $empresa['porcentaje_comision'] ?>%</div>
    <div class="stat-label">Comisión pactada</div>
  </div>
</div>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem">
    <h2 class="card-title" style="margin:0">Mis ofertas</h2>
    <a href="<?= BASE_URL ?>/pages/empresa/nueva_oferta.php" class="btn btn-primary">+ Nueva oferta</a>
  </div>

  <?php if (empty($ofertas)): ?>
    <div class="alert alert-info">Aún no has publicado ofertas. ¡Crea tu primera oferta!</div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Título</th><th>Precio oferta</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Acciones</th></tr>
      </thead>
      <tbody>
        <?php foreach ($ofertas as $o): ?>
        <tr>
          <td><?= sanitize($o['titulo']) ?></td>
          <td>$<?= number_format($o['precio_oferta'],2) ?></td>
          <td><?= date('d/m/Y', strtotime($o['fecha_inicio'])) ?></td>
          <td><?= date('d/m/Y', strtotime($o['fecha_fin'])) ?></td>
          <td>
            <?php $b = $o['estado']==='disponible' ? 'success':'danger'; ?>
            <span class="badge badge-<?= $b ?>"><?= $o['estado'] ?></span>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/pages/empresa/editar_oferta.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Editar</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
