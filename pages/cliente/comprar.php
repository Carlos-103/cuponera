<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireCliente();

$id         = (int)($_GET['id'] ?? 0);
$db         = getDB();
$cliente_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT o.*, e.nombre AS empresa_nombre FROM ofertas o JOIN empresas e ON e.id=o.empresa_id WHERE o.id=? AND o.estado='disponible' AND e.estado='aprobada'");
$stmt->execute([$id]);
$oferta = $stmt->fetch();

if (!$oferta) { setFlash('danger','Oferta no disponible.'); redirect('/index.php'); }

// Verificar máximo 5 cupones por oferta
$stmt2 = $db->prepare("SELECT COUNT(*) as total FROM compras WHERE cliente_id=? AND oferta_id=?");
$stmt2->execute([$cliente_id, $id]);
$yaComprados = $stmt2->fetch()['total'];

if ($yaComprados >= 5) {
    setFlash('danger','Ya compraste el máximo de 5 cupones para esta oferta.');
    redirect('/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_tarjeta  = preg_replace('/\s+/', '', $_POST['numero_tarjeta'] ?? '');
    $fecha_venc      = trim($_POST['fecha_venc'] ?? '');
    $cvv             = trim($_POST['cvv']        ?? '');

    if (!preg_match('/^\d{16}$/', $numero_tarjeta)) $errors[] = 'El número de tarjeta debe tener 16 dígitos.';
    if (!preg_match('/^\d{2}\/\d{2}$/', $fecha_venc)) {
        $errors[] = 'La fecha de vencimiento debe tener el formato MM/AA.';
    } else {
        [$mes, $anio] = explode('/', $fecha_venc);
        $venc = \DateTime::createFromFormat('m/y', $fecha_venc);
        if (!$venc || $venc < new \DateTime('first day of this month')) {
            $errors[] = 'La tarjeta está vencida.';
        }
    }
    if (!preg_match('/^\d{3,4}$/', $cvv)) $errors[] = 'El CVV debe tener 3 o 4 dígitos.';

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // Registrar compra
            $db->prepare("INSERT INTO compras (cliente_id,oferta_id,numero_tarjeta,fecha_venc_tarjeta,cvv,monto_pagado) VALUES (?,?,?,?,?,?)")
               ->execute([$cliente_id, $id, substr($numero_tarjeta,-4), $fecha_venc, $cvv, $oferta['precio_oferta']]);
            $compra_id = $db->lastInsertId();

            // Generar cupón único
            $codigo = strtoupper(bin2hex(random_bytes(8)));
            $db->prepare("INSERT INTO cupones (compra_id, codigo_unico) VALUES (?,?)")->execute([$compra_id, $codigo]);

            $db->commit();
            redirect('/pages/cliente/factura.php?id=' . $compra_id);
        } catch (\Exception $e) {
            $db->rollBack();
            $errors[] = 'Error al procesar la compra. Intenta nuevamente.';
        }
    }
}

$pageTitle = 'Comprar cupón – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="max-width:550px;margin:0 auto">
  <div class="auth-header"><div class="auth-logo">💳</div><h1>Comprar cupón</h1></div>

  <div class="card" style="margin-bottom:1rem;background:linear-gradient(135deg,#1a1a2e,#e63946);color:#fff">
    <div style="font-size:.8rem;opacity:.7;font-weight:700;text-transform:uppercase"><?= sanitize($oferta['empresa_nombre']) ?></div>
    <div style="font-size:1.2rem;font-weight:800;margin:.3rem 0"><?= sanitize($oferta['titulo']) ?></div>
    <div style="font-size:1.8rem;font-weight:900">$<?= number_format($oferta['precio_oferta'],2) ?></div>
    <div style="font-size:.8rem;opacity:.7;margin-top:.3rem">Cupones ya comprados de esta oferta: <?= $yaComprados ?>/5</div>
  </div>

  <div class="card">
    <h2 class="card-title">Datos de pago</h2>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= sanitize($e) ?></div><?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label>Número de tarjeta *</label>
        <input type="text" name="numero_tarjeta" maxlength="19" placeholder="1234 5678 9012 3456"
               oninput="this.value=this.value.replace(/[^0-9]/g,'').replace(/(.{4})/g,'$1 ').trim()" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Fecha de vencimiento * (MM/AA)</label>
          <input type="text" name="fecha_venc" maxlength="5" placeholder="12/27"
                 oninput="if(this.value.length===2&&!this.value.includes('/'))this.value+='/'" required>
        </div>
        <div class="form-group">
          <label>CVV *</label>
          <input type="text" name="cvv" maxlength="4" placeholder="123" required>
        </div>
      </div>
      <div class="alert alert-info" style="margin-bottom:1rem">
        🔒 Pago simulado — no se realizará ningún cargo real.
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="font-size:1.05rem;padding:.8rem">
        Pagar $<?= number_format($oferta['precio_oferta'],2) ?>
      </button>
    </form>
    <p style="margin-top:1rem;font-size:.9rem"><a href="<?= BASE_URL ?>/pages/cliente/detalle_oferta.php?id=<?= $id ?>">← Volver a la oferta</a></p>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
