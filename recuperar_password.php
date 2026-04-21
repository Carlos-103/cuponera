<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$errors = []; $success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $tipo   = $_POST['tipo'] ?? '';
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
    if (!in_array($tipo, ['admin','empresa','cliente'])) $errors[] = 'Selecciona el tipo de usuario.';
    if (empty($errors)) {
        $db    = getDB();
        $tabla = match($tipo) { 'admin'=>'administradores','empresa'=>'empresas',default=>'clientes' };
        $stmt  = $db->prepare("SELECT id FROM $tabla WHERE correo=?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $token  = generarToken();
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $db->prepare("DELETE FROM password_resets WHERE correo=? AND tipo_usuario=?")->execute([$correo,$tipo]);
            $db->prepare("INSERT INTO password_resets (correo,token,tipo_usuario,expires_at) VALUES (?,?,?,?)")->execute([$correo,$token,$tipo,$expiry]);
            $resetLink = BASE_URL . "/reset_password.php?token=$token";
        }
        $success = true;
    }
}
$pageTitle = 'Recuperar contraseña';
require_once __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap"><div class="auth-header"><div class="auth-logo">🔑</div><h1>Recuperar contraseña</h1></div>
<div class="card">
  <?php if ($success): ?>
    <div class="alert alert-success">Si el correo existe, recibirás instrucciones.
      <?php if (isset($resetLink)): ?><br><strong>Modo desarrollo:</strong> <a href="<?= $resetLink ?>">Clic aquí para restablecer</a><?php endif; ?>
    </div>
  <?php else: ?>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" novalidate>
      <div class="form-group"><label>Tipo de usuario</label><select name="tipo" required><option value="">-- Selecciona --</option><option value="cliente">Cliente</option><option value="empresa">Empresa</option><option value="admin">Administrador</option></select></div>
      <div class="form-group"><label>Correo electrónico</label><input type="email" name="correo" required autofocus></div>
      <button type="submit" class="btn btn-primary btn-full">Enviar instrucciones</button>
    </form>
  <?php endif; ?>
  <p style="margin-top:1rem;font-size:.9rem;"><a href="<?= BASE_URL ?>/login.php">← Volver al login</a></p>
</div></div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
