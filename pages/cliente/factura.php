<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireCliente();

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT c.*, o.titulo, o.fecha_limite_canje, o.precio_oferta,
           e.nombre AS empresa, e.direccion AS empresa_dir,
           cl.nombre_completo, cl.correo AS cliente_correo,
           cu.codigo_unico
    FROM compras c
    JOIN ofertas o ON o.id=c.oferta_id
    JOIN empresas e ON e.id=o.empresa_id
    JOIN clientes cl ON cl.id=c.cliente_id
    JOIN cupones cu ON cu.compra_id=c.id
    WHERE c.id=? AND c.cliente_id=?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$f = $stmt->fetch();

if (!$f) { setFlash('danger','Factura no encontrada.'); redirect('/pages/cliente/mis_cupones.php'); }

$pageTitle = 'Factura #' . str_pad($id,6,'0',STR_PAD_LEFT) . ' – La Cuponera SV';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="/cuponera/public/css/style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    body { background: #f4f4f6; }

    .factura-acciones {
      max-width: 580px;
      margin: 1.5rem auto 0;
      display: flex;
      gap: .8rem;
      flex-wrap: wrap;
    }

    /* Ocultar todo excepto la factura al imprimir */
    @media print {
      body * { visibility: hidden; }
      .factura-print, .factura-print * { visibility: visible; }
      .factura-print {
        position: fixed;
        top: 0; left: 0;
        width: 100%;
        padding: 2rem;
      }
      .factura-acciones { display: none !important; }
      .navbar { display: none !important; }
    }
  </style>
</head>
<body>

<!-- Navbar mínimo solo para navegación, no aparece en impresión -->
<nav class="navbar" style="margin-bottom:0">
  <a class="navbar-brand" href="/cuponera/index.php">🏷️ La <span>Cuponera</span> SV</a>
  <div class="nav-links">
    <a href="/cuponera/pages/cliente/mis_cupones.php">← Mis cupones</a>
    <a href="/cuponera/logout.php" class="btn-nav">Salir</a>
  </div>
</nav>

<!-- FACTURA (esta parte es la que se imprime/descarga) -->
<div id="factura-contenido" class="factura-print" style="max-width:580px;margin:2rem auto;padding:0 1rem">
  <div style="border:2px dashed #e63946;border-radius:12px;padding:2rem;background:#fff">

    <!-- Header -->
    <div style="text-align:center;padding-bottom:1.2rem;border-bottom:2px dashed #eee;margin-bottom:1.2rem">
      <div style="font-size:2rem">🏷️</div>
      <div style="font-size:1.4rem;font-weight:900;color:#e63946">La Cuponera SV</div>
      <div style="font-size:.85rem;color:#888">Comprobante de compra</div>
      <div style="font-size:.8rem;color:#aaa;margin-top:.2rem">
        Factura #<?= str_pad($id,6,'0',STR_PAD_LEFT) ?> &nbsp;·&nbsp; <?= date('d/m/Y H:i', strtotime($f['fecha_compra'])) ?>
      </div>
    </div>

    <!-- Cliente -->
    <div style="margin-bottom:1rem">
      <div style="font-size:.72rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.3rem">Cliente</div>
      <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($f['nombre_completo']) ?></div>
      <div style="font-size:.88rem;color:#666"><?= htmlspecialchars($f['cliente_correo']) ?></div>
    </div>

    <!-- Oferta -->
    <div style="background:#f8f8f8;border-radius:8px;padding:1rem;margin-bottom:1rem">
      <div style="font-size:.72rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.3rem">Oferta</div>
      <div style="font-weight:800;font-size:1.05rem"><?= htmlspecialchars($f['titulo']) ?></div>
      <div style="font-size:.85rem;color:#666"><?= htmlspecialchars($f['empresa']) ?></div>
      <div style="font-size:.82rem;color:#888;margin-top:.3rem">
        Válido para canjear hasta: <strong><?= date('d/m/Y', strtotime($f['fecha_limite_canje'])) ?></strong>
      </div>
    </div>

    <!-- Código único -->
    <div style="text-align:center;background:#1a1a2e;border-radius:10px;padding:1.4rem;margin-bottom:1.2rem">
      <div style="font-size:.72rem;color:#aaa;text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem">Código de cupón</div>
      <div style="font-family:monospace;font-size:1.6rem;font-weight:900;color:#fff;letter-spacing:3px">
        <?= htmlspecialchars($f['codigo_unico']) ?>
      </div>
      <div style="font-size:.75rem;color:#888;margin-top:.4rem">Presenta este código al momento de canjear</div>
    </div>

    <!-- Total -->
    <div style="display:flex;justify-content:space-between;align-items:center;padding-top:1rem;border-top:2px dashed #eee">
      <span style="font-weight:700;font-size:1rem;color:#444">Total pagado</span>
      <span style="font-size:1.6rem;font-weight:900;color:#e63946">$<?= number_format($f['monto_pagado'],2) ?></span>
    </div>

  </div>
</div>

<!-- Botones de acción (NO aparecen en impresión) -->
<div class="factura-acciones">
  <button onclick="imprimirFactura()" class="btn btn-dark">
    🖨️ Imprimir
  </button>
  <button onclick="descargarPDF()" class="btn btn-primary">
    ⬇️ Descargar PDF
  </button>
  <a href="/cuponera/pages/cliente/mis_cupones.php" class="btn btn-outline">
    Ver mis cupones
  </a>
  <a href="/cuponera/index.php" class="btn btn-outline">
    Seguir comprando
  </a>
</div>

<script>
function imprimirFactura() {
  window.print();
}

function descargarPDF() {
  const elemento = document.getElementById('factura-contenido');
  const opciones = {
    margin:       [10, 10, 10, 10],
    filename:     'Factura_<?= str_pad($id,6,'0',STR_PAD_LEFT) ?>_LaCuponeRaSV.pdf',
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { scale: 2, useCORS: true },
    jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
  };
  html2pdf().set(opciones).from(elemento).save();
}
</script>

</body>
</html>
