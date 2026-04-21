<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireCliente();

$db         = getDB();
$cliente_id = $_SESSION['user_id'];

$compras = $db->prepare("
    SELECT c.*, o.titulo, o.fecha_limite_canje, e.nombre AS empresa,
           GROUP_CONCAT(cu.codigo_unico SEPARATOR '|') AS codigos
    FROM compras c
    JOIN ofertas o ON o.id = c.oferta_id
    JOIN empresas e ON e.id = o.empresa_id
    JOIN cupones cu ON cu.compra_id = c.id
    WHERE c.cliente_id = ?
    GROUP BY c.id
    ORDER BY c.fecha_compra DESC
");
$compras->execute([$cliente_id]);
$compras = $compras->fetchAll();

$pageTitle = 'Mis cupones – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="section-title">Mis cupones</h1>

<?php if (empty($compras)): ?>
  <div class="alert alert-info">Aún no has comprado cupones. <a href="<?= BASE_URL ?>/index.php">Ver ofertas disponibles</a></div>
<?php else: ?>
  <?php foreach ($compras as $c): ?>
  <div class="card" style="border-left:4px solid var(--red)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem">
      <div>
        <div style="font-size:.8rem;color:#888;font-weight:600;text-transform:uppercase"><?= sanitize($c['empresa']) ?></div>
        <div style="font-size:1.1rem;font-weight:800;margin:.2rem 0"><?= sanitize($c['titulo']) ?></div>
        <div style="font-size:.85rem;color:#666">Comprado el <?= date('d/m/Y H:i', strtotime($c['fecha_compra'])) ?></div>
        <div style="font-size:.85rem;color:#666">Válido hasta: <?= date('d/m/Y', strtotime($c['fecha_limite_canje'])) ?></div>
      </div>
      <div style="text-align:right">
        <div style="font-size:1.4rem;font-weight:900;color:var(--red)">$<?= number_format($c['monto_pagado'],2) ?></div>
      </div>
    </div>
    <div style="margin-top:1rem;border-top:1px dashed #ddd;padding-top:1rem">
      <div style="font-size:.82rem;font-weight:700;color:#555;margin-bottom:.5rem">CÓDIGOS DE TUS CUPONES:</div>
      <div style="display:flex;flex-wrap:wrap;gap:.5rem">
        <?php foreach (explode('|', $c['codigos']) as $codigo): ?>
          <span style="background:#1a1a2e;color:#fff;font-family:monospace;font-size:.85rem;padding:.4rem .8rem;border-radius:6px;letter-spacing:1px">
            <?= sanitize($codigo) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
