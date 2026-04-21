<?php
require_once __DIR__ . '/auth.php';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'La Cuponera SV' ?></title>
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
<div class="container">
<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['tipo'] ?>" style="margin-top:1rem"><?= sanitize($flash['mensaje']) ?></div>
<?php endif; ?>
