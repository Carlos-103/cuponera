<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$ofertas = $db->query("
    SELECT o.*, e.nombre AS empresa_nombre
    FROM ofertas o
    JOIN empresas e ON e.id = o.empresa_id
    WHERE o.estado = 'disponible'
      AND e.estado = 'aprobada'
      AND o.fecha_fin >= CURDATE()
    ORDER BY o.created_at DESC
")->fetchAll();

$pageTitle = 'La Cuponera SV – Cupones y descuentos';

// Custom header without container for hero
require_once __DIR__ . '/config/database.php';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="/cuponera/public/css/style.css">
</head>
<body>
<nav class="navbar">
  <a class="navbar-brand" href="/cuponera/index.php">🏷️ La <span>Cuponera</span> SV</a>
  <div class="nav-links">
    <?php if (!isLoggedIn()): ?>
      <a href="/cuponera/login.php">Iniciar sesión</a>
      <a href="/cuponera/pages/cliente/registro.php" class="btn-nav">Registrarse</a>
    <?php elseif (isAdmin()): ?>
      <span style="color:rgba(255,255,255,.5);font-size:.85rem">👋 <?= sanitize($_SESSION['user_nombre']) ?></span>
      <a href="/cuponera/pages/admin/dashboard.php">Panel admin</a>
      <a href="/cuponera/logout.php" class="btn-nav">Salir</a>
    <?php elseif (isEmpresa()): ?>
      <span style="color:rgba(255,255,255,.5);font-size:.85rem">🏢 <?= sanitize($_SESSION['user_nombre']) ?></span>
      <a href="/cuponera/pages/empresa/dashboard.php">Mi panel</a>
      <a href="/cuponera/logout.php" class="btn-nav">Salir</a>
    <?php elseif (isCliente()): ?>
      <span style="color:rgba(255,255,255,.5);font-size:.85rem">👤 <?= sanitize($_SESSION['user_nombre']) ?></span>
      <a href="/cuponera/pages/cliente/mis_cupones.php">Mis cupones</a>
      <a href="/cuponera/logout.php" class="btn-nav">Salir</a>
    <?php endif; ?>
  </div>
</nav>

<?php if ($flash): ?>
<div class="container"><div class="alert alert-<?= $flash['tipo'] ?>" style="margin-top:1rem"><?= sanitize($flash['mensaje']) ?></div></div>
<?php endif; ?>

<div class="hero">
  <h1>¡Descuentos <span>increíbles</span> te esperan!</h1>
  <p>Compra cupones y ahorra en los mejores negocios de El Salvador.</p>
</div>

<div class="container">
<?php if (empty($ofertas)): ?>
  <div class="alert alert-info" style="margin-top:1.5rem">No hay cupones disponibles por el momento. ¡Vuelve pronto!</div>
<?php else: ?>
<div class="cupones-grid">
  <?php foreach ($ofertas as $o): ?>
  <?php $descuento = round((1 - $o['precio_oferta'] / $o['precio_regular']) * 100); ?>
  <div class="cupon-card">
    <div class="cupon-top">
      <div class="empresa-tag"><?= sanitize($o['empresa_nombre']) ?></div>
      <div class="cupon-title"><?= sanitize($o['titulo']) ?></div>
      <span class="discount-badge">-<?= $descuento ?>%</span>
    </div>
    <div class="cupon-body">
      <div class="precio-regular">$<?= number_format($o['precio_regular'],2) ?></div>
      <div class="precio-oferta">$<?= number_format($o['precio_oferta'],2) ?></div>
      <p class="cupon-desc"><?= sanitize(substr($o['descripcion'],0,90)) ?>...</p>
      <div class="cupon-fecha">⏰ Válido hasta: <?= date('d/m/Y', strtotime($o['fecha_fin'])) ?></div>
    </div>
    <div class="cupon-footer">
      <a href="/cuponera/pages/cliente/detalle_oferta.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Ver oferta</a>
      <?php if (isCliente()): ?>
        <a href="/cuponera/pages/cliente/comprar.php?id=<?= $o['id'] ?>" class="btn btn-primary btn-sm">Comprar</a>
      <?php else: ?>
        <a href="/cuponera/login.php" class="btn btn-primary btn-sm">Comprar</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<footer class="footer">
  <p>&copy; <?= date('Y') ?> <span>La Cuponera SV</span> &mdash; Todos los derechos reservados.</p>
</footer>
</body>
</html>
