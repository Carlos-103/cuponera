<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

$db = getDB();

// Total de cupones vendidos por empresa
$cupones_por_empresa = $db->query("
    SELECT e.nombre AS empresa, COUNT(cu.id) AS total_cupones
    FROM empresas e
    LEFT JOIN ofertas o ON o.empresa_id = e.id
    LEFT JOIN compras c ON c.oferta_id = o.id
    LEFT JOIN cupones cu ON cu.compra_id = c.id
    WHERE e.estado = 'aprobada'
    GROUP BY e.id, e.nombre
    ORDER BY total_cupones DESC
")->fetchAll();

// Total de ventas por empresa (monto)
$ventas_por_empresa = $db->query("
    SELECT e.nombre AS empresa, e.porcentaje_comision,
           COALESCE(SUM(c.monto_pagado), 0) AS total_ventas,
           COALESCE(SUM(c.monto_pagado * e.porcentaje_comision / 100), 0) AS ganancia_cuponera,
           COUNT(c.id) AS num_compras
    FROM empresas e
    LEFT JOIN ofertas o ON o.empresa_id = e.id
    LEFT JOIN compras c ON c.oferta_id = o.id
    WHERE e.estado = 'aprobada'
    GROUP BY e.id, e.nombre, e.porcentaje_comision
    ORDER BY total_ventas DESC
")->fetchAll();

// Totales generales
$totales = $db->query("
    SELECT
        COUNT(DISTINCT cu.id)   AS total_cupones,
        COUNT(DISTINCT c.id)    AS total_compras,
        COALESCE(SUM(c.monto_pagado), 0) AS total_ventas,
        COALESCE(SUM(c.monto_pagado * e.porcentaje_comision / 100), 0) AS total_ganancias
    FROM compras c
    JOIN ofertas o ON o.id = c.oferta_id
    JOIN empresas e ON e.id = o.empresa_id
    JOIN cupones cu ON cu.compra_id = c.id
")->fetch();

$pageTitle = 'Reportes – La Cuponera SV';
require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
  <h1 class="section-title" style="margin:0">Reportes del sistema</h1>
  <a href="<?= BASE_URL ?>/pages/admin/dashboard.php" class="btn btn-outline btn-sm">← Volver al panel</a>
</div>

<!-- Tarjetas resumen -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">
  <div class="stat-card">
    <div class="stat-num"><?= number_format($totales['total_cupones']) ?></div>
    <div class="stat-label">Cupones vendidos</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= number_format($totales['total_compras']) ?></div>
    <div class="stat-label">Compras realizadas</div>
  </div>
  <div class="stat-card">
    <div class="stat-num">$<?= number_format($totales['total_ventas'], 2) ?></div>
    <div class="stat-label">Total ventas</div>
  </div>
  <div class="stat-card">
    <div class="stat-num">$<?= number_format($totales['total_ganancias'], 2) ?></div>
    <div class="stat-label">Ganancias obtenidas</div>
  </div>
</div>

<!-- Reporte 1: Cupones vendidos por empresa -->
<div class="card">
  <h2 class="card-title">Total de cupones vendidos por empresa</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Empresa</th>
          <th style="text-align:center">Cupones vendidos</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cupones_por_empresa as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['empresa']) ?></td>
          <td style="text-align:center;font-weight:700">
            <?= number_format($r['total_cupones']) ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($cupones_por_empresa)): ?>
        <tr><td colspan="2" style="text-align:center;color:#aaa">Sin datos aún</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Reporte 2: Ventas y ganancias por empresa -->
<div class="card">
  <h2 class="card-title">Total de ventas y ganancias por empresa</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Empresa</th>
          <th style="text-align:center">Compras</th>
          <th style="text-align:right">Total ventas</th>
          <th style="text-align:center">Comisión</th>
          <th style="text-align:right">Ganancia Cuponera</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ventas_por_empresa as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['empresa']) ?></td>
          <td style="text-align:center"><?= number_format($r['num_compras']) ?></td>
          <td style="text-align:right;font-weight:700">$<?= number_format($r['total_ventas'], 2) ?></td>
          <td style="text-align:center">
            <span class="badge badge-info"><?= $r['porcentaje_comision'] ?>%</span>
          </td>
          <td style="text-align:right;font-weight:700;color:#2d6a4f">
            $<?= number_format($r['ganancia_cuponera'], 2) ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($ventas_por_empresa)): ?>
        <tr><td colspan="5" style="text-align:center;color:#aaa">Sin datos aún</td></tr>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($ventas_por_empresa)): ?>
      <tfoot>
        <tr style="background:#1a1a2e;color:#fff">
          <td colspan="2" style="padding:.75rem 1rem;font-weight:700">TOTALES</td>
          <td style="text-align:right;padding:.75rem 1rem;font-weight:700">
            $<?= number_format($totales['total_ventas'], 2) ?>
          </td>
          <td></td>
          <td style="text-align:right;padding:.75rem 1rem;font-weight:700;color:#f9c74f">
            $<?= number_format($totales['total_ganancias'], 2) ?>
          </td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
