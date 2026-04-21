<?php
// reset_password.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$token   = trim($_GET['token'] ?? '');
$errors  = [];
$valid   = false;
$reset   = null;

if (empty($token)) redirect('/login.php');

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $errors[] = 'El enlace es inválido o ha expirado. Solicita uno nuevo.';
} else {
    $valid = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < 8)          $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($password !== $password2)        $errors[] = 'Las contraseñas no coinciden.';

    if (empty($errors)) {
        $hash  = password_hash($password, PASSWORD_DEFAULT);
        $tabla = match($reset['tipo_usuario']) { 'admin' => 'administradores', 'empresa' => 'empresas', default => 'clientes' };

        $db->prepare("UPDATE $tabla SET password=? WHERE correo=?")->execute([$hash, $reset['correo']]);
        $db->prepare("UPDATE password_resets SET used=1 WHERE token=?")->execute([$token]);

        setFlash('success', 'Contraseña actualizada. Ya puedes iniciar sesión.');
        redirect('/login.php');
    }
}

$pageTitle = 'Nueva contraseña – La Cuponera SV';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="card">
    <h1 class="card-title">Nueva contraseña</h1>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= sanitize($e) ?></div>
    <?php endforeach; ?>

    <?php if ($valid): ?>
    <form method="POST" novalidate>
      <input type="hidden" name="token" value="<?= sanitize($token) ?>">
      <div class="form-group">
        <label>Nueva contraseña * (mín. 8 caracteres)</label>
        <input type="password" name="password" required autofocus>
      </div>
      <div class="form-group">
        <label>Confirmar nueva contraseña *</label>
        <input type="password" name="password2" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Guardar contraseña</button>
    </form>
    <?php endif; ?>

    <p style="margin-top:1rem;font-size:.9rem;">
      <a href="/recuperar_password.php">Solicitar nuevo enlace</a>
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
