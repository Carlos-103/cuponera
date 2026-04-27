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
    if (empty($data['nit'])) {
        $errors[] = 'El NIT es obligatorio.';
    } elseif (!preg_match('/^\d{4}-\d{6}-\d{3}-\d{1}$/', $data['nit'])) {
        $errors[] = 'El NIT debe tener el formato 0000-000000-000-0.';
    }
    if (empty($data['direccion'])) $errors[] = 'La dirección es obligatoria.';
    if (empty($data['telefono'])) {
        $errors[] = 'El teléfono es obligatorio.';
    } elseif (!preg_match('/^\d{4}-\d{4}$/', $data['telefono'])) {
        $errors[] = 'El teléfono debe tener el formato 0000-0000.';
    }
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
  <div class="form-group">
    <label>NIT * (0000-000000-000-0)</label>
    <input type="text" name="nit" id="campo-nit" placeholder="0614-010101-000-0" value="<?= sanitize($data['nit']??'') ?>" maxlength="17" autocomplete="off" required>
  </div>
  <div class="form-group">
    <label>Teléfono * (0000-0000)</label>
    <input type="text" name="telefono" id="campo-telefono" placeholder="2525-2525" value="<?= sanitize($data['telefono']??'') ?>" maxlength="9" autocomplete="off" required>
  </div>
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


<script>
(function() {
    var tel = document.getElementById('campo-telefono');
    if (!tel) return;

    tel.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    });

    tel.addEventListener('input', function(e) {
        var n = e.target.value.replace(/[^0-9]/g, '');
        if (n.length > 8) n = n.slice(0, 8);
        if (n.length > 4) {
            e.target.value = n.slice(0,4) + '-' + n.slice(4);
        } else {
            e.target.value = n;
        }
    });

    tel.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && e.target.value.endsWith('-')) {
            e.target.value = e.target.value.slice(0, -1);
            e.preventDefault();
        }
    });
})();
</script>

<script>
(function() {
    var campo = document.getElementById('campo-nit');
    if (!campo) return;

    campo.addEventListener('keypress', function(e) {
        // Solo permitir números
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
        }
    });

    campo.addEventListener('input', function(e) {
        // Quitar todo lo que no sea número
        var n = e.target.value.replace(/[^0-9]/g, '');

        // Limitar a 14 dígitos
        if (n.length > 14) n = n.slice(0, 14);

        // Formatear: 0000-000000-000-0
        var formatted = '';
        if (n.length <= 4) {
            formatted = n;
        } else if (n.length <= 10) {
            formatted = n.slice(0,4) + '-' + n.slice(4);
        } else if (n.length <= 13) {
            formatted = n.slice(0,4) + '-' + n.slice(4,10) + '-' + n.slice(10);
        } else {
            formatted = n.slice(0,4) + '-' + n.slice(4,10) + '-' + n.slice(10,13) + '-' + n.slice(13);
        }

        e.target.value = formatted;
    });

    campo.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && e.target.value.endsWith('-')) {
            e.target.value = e.target.value.slice(0, -1);
            e.preventDefault();
        }
    });
})();
</script>
<script>
(function() {
    var tel = document.getElementById('campo-telefono');
    if (!tel) return;
    tel.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    });
    tel.addEventListener('input', function(e) {
        var n = e.target.value.replace(/[^0-9]/g, '');
        if (n.length > 8) n = n.slice(0, 8);
        e.target.value = n.length > 4 ? n.slice(0,4) + '-' + n.slice(4) : n;
    });
    tel.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && e.target.value.endsWith('-')) {
            e.target.value = e.target.value.slice(0, -1);
            e.preventDefault();
        }
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
