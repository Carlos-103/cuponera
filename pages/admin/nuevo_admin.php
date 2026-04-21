<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();
$errors = []; $data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = ['nombre'=>trim($_POST['nombre']??''),'usuario'=>trim($_POST['usuario']??''),'correo'=>trim($_POST['correo']??''),'password'=>$_POST['password']??'','password2'=>$_POST['password2']??''];
    if (empty($data['nombre']))  $errors[] = 'El nombre es obligatorio.';
    if (strlen($data['usuario'])<4) $errors[] = 'El usuario debe tener al menos 4 caracteres.';
    if (!filter_var($data['correo'],FILTER_VALIDATE_EMAIL)) $errors[] = 'Correo inválido.';
    if (strlen($data['password'])<8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($data['password']!==$data['password2']) $errors[] = 'Las contraseñas no coinciden.';
    if (empty($errors)) {
        $db=$db=getDB();
        if ($db->prepare("SELECT id FROM administradores WHERE usuario=? OR correo=?")->execute([$data['usuario'],$data['correo']]) && $db->query("SELECT id FROM administradores WHERE usuario='".$data['usuario']."' OR correo='".$data['correo']."'")->fetch()) {
            $errors[]='El usuario o correo ya existe.';
        }
    }
    if (empty($errors)) {
        $hash=password_hash($data['password'],PASSWORD_DEFAULT);
        getDB()->prepare("INSERT INTO administradores (nombre,usuario,correo,password) VALUES (?,?,?,?)")->execute([$data['nombre'],$data['usuario'],$data['correo'],$hash]);
        setFlash('success','Administrador creado correctamente.');
        redirect('/pages/admin/dashboard.php');
    }
}
$pageTitle='Nuevo administrador';
require_once __DIR__.'/../../includes/header.php';
?>
<div class="auth-wrap" style="max-width:520px">
  <div class="auth-header"><div class="auth-logo">👑</div><h1>Nuevo administrador</h1></div>
  <div class="card">
    <?php foreach($errors as $e): ?><div class="alert alert-danger"><?=sanitize($e)?></div><?php endforeach; ?>
    <form method="POST" novalidate>
      <div class="form-group"><label>Nombre completo *</label><input type="text" name="nombre" value="<?=sanitize($data['nombre']??'')?>" required></div>
      <div class="form-group"><label>Usuario *</label><input type="text" name="usuario" value="<?=sanitize($data['usuario']??'')?>" required></div>
      <div class="form-group"><label>Correo *</label><input type="email" name="correo" value="<?=sanitize($data['correo']??'')?>" required></div>
      <div class="form-row">
        <div class="form-group"><label>Contraseña *</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirmar *</label><input type="password" name="password2" required></div>
      </div>
      <div style="display:flex;gap:1rem">
        <button type="submit" class="btn btn-primary">Crear administrador</button>
        <a href="<?=BASE_URL?>/pages/admin/dashboard.php" class="btn btn-outline">Cancelar</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__.'/../../includes/footer.php'; ?>
