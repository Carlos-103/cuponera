<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isLoggedIn()) redirect('/index.php');

$errors = []; $data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
        'apellidos'       => trim($_POST['apellidos']       ?? ''),
        'dui'             => trim($_POST['dui']             ?? ''),
        'fecha_nacimiento'=> trim($_POST['fecha_nacimiento'] ?? ''),
        'usuario'         => trim($_POST['usuario']         ?? ''),
        'correo'          => trim($_POST['correo']          ?? ''),
        'password'        => $_POST['password']             ?? '',
        'password2'       => $_POST['password2']            ?? '',
    ];

    if (empty($data['nombre_completo'])) $errors[] = 'El nombre completo es obligatorio.';
    if (empty($data['apellidos']))       $errors[] = 'Los apellidos son obligatorios.';
    if (!preg_match('/^\d{8}-\d$/', $data['dui'])) $errors[] = 'El DUI debe tener el formato 00000000-0.';
    if (empty($data['fecha_nacimiento'])) {
        $errors[] = 'La fecha de nacimiento es obligatoria.';
    } elseif (calcularEdad($data['fecha_nacimiento']) < 18) {
        $errors[] = 'Debes tener al menos 18 años para registrarte.';
    }
    if (strlen($data['usuario']) < 4)  $errors[] = 'El usuario debe tener al menos 4 caracteres.';
    if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) $errors[] = 'El correo no es válido.';
    if (strlen($data['password']) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    if ($data['password'] !== $data['password2']) $errors[] = 'Las contraseñas no coinciden.';

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM clientes WHERE usuario=? OR correo=? OR dui=?");
        $stmt->execute([$data['usuario'], $data['correo'], $data['dui']]);
        if ($stmt->fetch()) $errors[] = 'El usuario, correo o DUI ya está registrado.';
    }

    if (empty($errors)) {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        getDB()->prepare("INSERT INTO clientes (nombre_completo,apellidos,dui,fecha_nacimiento,usuario,correo,password) VALUES (?,?,?,?,?,?,?)")
               ->execute([$data['nombre_completo'],$data['apellidos'],$data['dui'],$data['fecha_nacimiento'],$data['usuario'],$data['correo'],$hash]);
        setFlash('success', '¡Registro exitoso! Ya puedes iniciar sesión.');
        redirect('/login.php');
    }
}

$pageTitle = 'Registro de cliente – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="auth-wrap" style="max-width:600px">
  <div class="auth-header"><div class="auth-logo">👤</div><h1>Crear cuenta</h1><p>Regístrate para comprar cupones</p></div>
  <div class="card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" novalidate>
      <div class="form-row">
        <div class="form-group"><label>Nombre completo *</label><input type="text" name="nombre_completo" value="<?= sanitize($data['nombre_completo']??'') ?>" required></div>
        <div class="form-group"><label>Apellidos *</label><input type="text" name="apellidos" value="<?= sanitize($data['apellidos']??'') ?>" required></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>DUI (00000000-0) *</label>
          <input type="text" name="dui" id="campo-dui"
                 placeholder="12345678-9"
                 value="<?= sanitize($data['dui']??'') ?>"
                 maxlength="10"
                 autocomplete="off"
                 required>
         
        </div>
        <div class="form-group"><label>Fecha de nacimiento *</label><input type="date" name="fecha_nacimiento" value="<?= sanitize($data['fecha_nacimiento']??'') ?>" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required></div>
      </div>
      <div class="form-group"><label>Usuario *</label><input type="text" name="usuario" value="<?= sanitize($data['usuario']??'') ?>" required></div>
      <div class="form-group"><label>Correo electrónico *</label><input type="email" name="correo" value="<?= sanitize($data['correo']??'') ?>" required></div>
      <div class="form-row">
        <div class="form-group"><label>Contraseña * (mín. 8)</label><input type="password" name="password" required></div>
        <div class="form-group"><label>Confirmar contraseña *</label><input type="password" name="password2" required></div>
      </div>
      <button type="submit" class="btn btn-primary btn-full">Crear cuenta</button>
    </form>
    <p style="margin-top:1rem;font-size:.9rem;">¿Ya tienes cuenta? <a href="<?= BASE_URL ?>/login.php">Inicia sesión</a></p>
  </div>
</div>

<script>
(function() {
    var campo = document.getElementById('campo-dui');
    if (!campo) return;

    campo.addEventListener('keypress', function(e) {
        // Solo permitir números
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
        }
    });

    campo.addEventListener('input', function(e) {
        // Quitar todo lo que no sea número
        var solo_nums = e.target.value.replace(/[^0-9]/g, '');

        // Limitar a 9 dígitos
        if (solo_nums.length > 9) {
            solo_nums = solo_nums.slice(0, 9);
        }

        // Insertar guion antes del último dígito cuando hay 9
        if (solo_nums.length === 9) {
            e.target.value = solo_nums.slice(0, 8) + '-' + solo_nums.slice(8);
        } else {
            e.target.value = solo_nums;
        }
    });

    campo.addEventListener('keydown', function(e) {
        // Si borra y el último char es guion, quitar el guion también
        if (e.key === 'Backspace' && e.target.value.endsWith('-')) {
            e.target.value = e.target.value.slice(0, -1);
            e.preventDefault();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
