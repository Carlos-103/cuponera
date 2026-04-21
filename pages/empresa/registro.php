<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) redirect('/index.php');

$errors = []; $data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre'   => trim($_POST['nombre']    ?? ''),
        'nit'      => trim($_POST['nit']       ?? ''),
        'direccion'=> trim($_POST['direccion'] ?? ''),
        'telefono' => trim($_POST['telefono']  ?? ''),
        'correo'   => trim($_POST['correo']    ?? ''),
        'usuario'  => trim($_POST['usuario']   ?? ''),
        'password' => $_POST['password']       ?? '',
        'password2'=> $_POST['password2']      ?? '',
    ];

    if (empty($data['nombre']))    $errors[] = 'El nombre de la empresa es obligatorio.';
    if (empty($data['nit']))       $errors[] = 'El NIT es obligatorio.';
    if (empty($data['direccion'])) $errors[] = 'La dirección es obligatoria.';
    if (empty($data['telefono']))  $errors[] = 'El teléfono es obligatorio.';
    if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo no es válido.';
    if (strlen($data['usuario']) < 4)  $errors[] = 'El usuario debe tener al menos 4 caracteres.';
    if (strlen($data['password']) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($data['password'] !== $data['password2']) $errors[] = 'Las contraseñas no coinciden.';

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT id FROM empresas WHERE usuario=? OR correo=? OR nit=?");
        $stmt->execute([$data['usuario'], $data['correo'], $data['nit']]);
        if ($stmt->fetch()) $errors[] = 'El usuario, correo o NIT ya está registrado.';
    }

    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO empresas (nombre,nit,direccion,telefono,correo,usuario,password) VALUES (?,?,?,?,?,?,?)")
           ->execute([$data['nombre'],$data['nit'],$data['direccion'],$data['telefono'],$data['correo'],$data['usuario'],$hash]);
        setFlash('success', 'Solicitud enviada. El administrador revisará tu registro pronto.');
        redirect('/login.php');
    }
}

$pageTitle = 'Registro de empresa – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="auth-wrap" style="max-width:600px">
  <div class="auth-header"><div class="auth-logo">🏢</div><h1>Registrar empresa</h1><p>Tu solicitud será revisada por el administrador.</p></div>
  <div class="card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" novalidate>
      <div class="form-group"><label>Nombre de la empresa *</label><input type="text" name="nombre" value="<?= sanitize($data['nombre']??'') ?>" required></div>
      <div class="form-row">
        <div class="form-group"><label>NIT *</label><input type="text" name="nit" value="<?= sanitize($data['nit']??'') ?>" required></div>
        <div class="form-group"><label>Teléfono *</label><input type="text" name="telefono" value="<?= sanitize($data['telefono']??'') ?>" required></div>
      </div>
      <div class="form-group"><label>Dirección *</label><input type="text" name="direccion" value="<?= sanitize($data['direccion']??'') ?>" required></div>
      <div class="form-group"><label>Correo electrónico *</label><input type="email" name="correo" value="<?= sanitize($data['correo']??'') ?>" required></div>
      <div class="form-group"><label>Usuario *</label><input type="text" name="usuario" value="<?= sanitize($data['usuario']??'') ?>" required></div>
      <div class="form-row">
        <div class="form-group"><label>Contraseña * (mín. 8)</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirmar contraseña *</label><input type="password" name="password2" required></div>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Enviar solicitud</button>
    </form>
    <p style="margin-top:1rem;font-size:.9rem;">¿Ya tienes cuenta? <a href="<?= BASE_URL ?>/login.php">Inicia sesión</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
