<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT o.*, e.nombre AS empresa_nombre FROM ofertas o JOIN empresas e ON e.id=o.empresa_id WHERE o.id=? AND o.estado='disponible' AND e.estado='aprobada'");
$stmt->execute([$id]);
$oferta = $stmt->fetch();

if (!$oferta) { setFlash('danger','Oferta no encontrada.'); redirect('/index.php'); }

$descuento = round((1 - $oferta['precio_oferta'] / $oferta['precio_regular']) * 100);

$pageTitle = sanitize($oferta['titulo']) . ' – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="max-width:700px;margin:0 auto">
  <div class="card" style="border-top:4px solid var(--red)">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1rem">
      <div>
        <div style="font-size:.8rem;color:#888;font-weight:700;text-transform:uppercase"><?= sanitize($oferta['empresa_nombre']) ?></div>
        <h1 style="font-size:1.6rem;font-weight:900;margin:.3rem 0"><?= sanitize($oferta['titulo']) ?></h1>
      </div>
      <span class="badge badge-success" style="font-size:1rem;padding:.5rem 1rem">-<?= $descuento ?>%</span>
    </div>

    <div style="display:flex;align-items:baseline;gap:1rem;margin-bottom:1.2rem">
      <span style="text-decoration:line-through;color:#aaa;font-size:1rem">$<?= number_format($oferta['precio_regular'],2) ?></span>
      <span style="font-size:2rem;font-weight:900;color:var(--red)">$<?= number_format($oferta['precio_oferta'],2) ?></span>
    </div>

    <div style="background:#f8f8f8;border-radius:8px;padding:1rem;margin-bottom:1.2rem;font-size:.9rem;line-height:1.7">
      <?= nl2br(sanitize($oferta['descripcion'])) ?>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;font-size:.85rem;color:#666;margin-bottom:1.5rem">
      <div>📅 Inicio: <strong><?= date('d/m/Y', strtotime($oferta['fecha_inicio'])) ?></strong></div>
      <div>📅 Fin: <strong><?= date('d/m/Y', strtotime($oferta['fecha_fin'])) ?></strong></div>
      <div>⏰ Canjear antes del: <strong><?= date('d/m/Y', strtotime($oferta['fecha_limite_canje'])) ?></strong></div>
      <div>🎟️ Cupones: <strong><?= $oferta['cantidad_cupones'] ? $oferta['cantidad_cupones'] : 'Ilimitados' ?></strong></div>
    </div>

    <?php if (isCliente()): ?>
      <a href="<?= BASE_URL ?>/pages/cliente/comprar.php?id=<?= $oferta['id'] ?>" class="btn btn-primary btn-full" style="font-size:1.05rem;padding:.8rem">🛒 Comprar cupón</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-full" style="font-size:1.05rem;padding:.8rem">Inicia sesión para comprar</a>
    <?php endif; ?>
  </div>
  <p style="margin-top:1rem;font-size:.9rem"><a href="<?= BASE_URL ?>/index.php">← Ver todas las ofertas</a></p>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
