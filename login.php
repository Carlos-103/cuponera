<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirect('/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $tipo    = $_POST['tipo'] ?? '';

    if (empty($usuario))  $errors[] = 'El usuario es obligatorio.';
    if (empty($password)) $errors[] = 'La contraseña es obligatoria.';
    if (!in_array($tipo, ['admin','empresa','cliente'])) $errors[] = 'Selecciona un tipo de usuario.';

    if (empty($errors)) {
        $db    = getDB();
        $tabla = match($tipo) { 'admin' => 'administradores', 'empresa' => 'empresas', default => 'clientes' };
        $campos = match($tipo) {
        'admin'   => 'id, nombre, password',
        'empresa' => 'id, nombre, password, estado',
        'cliente' => 'id, nombre_completo AS nombre, password',
        default   => 'id, nombre, password'
    };

        $stmt = $db->prepare("SELECT $campos FROM $tabla WHERE usuario = ? OR correo = ?");
        $stmt->execute([$usuario, $usuario]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Credenciales incorrectas. Verifica tu usuario y contraseña.';
        } elseif ($tipo === 'empresa' && $user['estado'] !== 'aprobada') {
            $errors[] = $user['estado'] === 'pendiente'
                ? 'Tu solicitud aún está pendiente de aprobación.'
                : 'Tu empresa ha sido rechazada. Contacta al administrador.';
        } else {
            loginUser($user['id'], $user['nombre'], $tipo);
            $destino = match($tipo) {
                'admin'   => '/pages/admin/dashboard.php',
                'empresa' => '/pages/empresa/dashboard.php',
                default   => '/index.php',
            };
            redirect($destino);
        }
    }
}

$pageTitle = 'Iniciar sesión – La Cuponera SV';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container"><div class="auth-wrap">
  <div class="auth-header">
    <div class="auth-logo">🏷️</div>
    <h1>Iniciar sesión</h1>
    <p>Bienvenido de vuelta a La Cuponera SV</p>
  </div>
  <div class="card">
    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= sanitize($e) ?></div>
    <?php endforeach; ?>
    <form method="POST" novalidate>
      <div class="form-group">
        <label>Tipo de usuario</label>
        <select name="tipo" required>
          <option value="">-- Selecciona --</option>
          <option value="cliente"  <?= (($_POST['tipo']??'') === 'cliente')  ? 'selected':'' ?>>Cliente</option>
          <option value="empresa"  <?= (($_POST['tipo']??'') === 'empresa')  ? 'selected':'' ?>>Empresa</option>
          <option value="admin"    <?= (($_POST['tipo']??'') === 'admin')    ? 'selected':'' ?>>Administrador</option>
        </select>
      </div>
      <div class="form-group">
        <label>Usuario o correo electrónico</label>
        <input type="text" name="usuario" value="<?= sanitize($_POST['usuario']??'') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Entrar</button>
    </form>
    <p style="margin-top:1rem;font-size:.9rem;">
      ¿Olvidaste tu contraseña? <a href="<?= BASE_URL ?>/recuperar_password.php">Recupérala aquí</a>
    </p>
    <p style="margin-top:.5rem;font-size:.9rem;">
      ¿No tienes cuenta? <a href="<?= BASE_URL ?>/pages/cliente/registro.php">Regístrate como cliente</a>
      · <a href="<?= BASE_URL ?>/pages/empresa/registro.php">Registra tu empresa</a>
    </p>
  </div>
</div>
</div><?php require_once __DIR__ . '/includes/footer.php'; ?>
