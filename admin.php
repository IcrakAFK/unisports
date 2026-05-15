<?php
session_start();
require_once 'config.php';
requireRol('admin');

$db = getDB();

// 1. Estadísticas básicas (una por una para que sea fácil de leer)
$num_usuarios = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$num_reservas = $db->query("SELECT COUNT(*) FROM reservas WHERE estado_pago = 'pagado'")->fetchColumn();
$ingresos     = $db->query("SELECT SUM(precio_final) FROM reservas WHERE estado_pago = 'pagado'")->fetchColumn();
$pistas_libres = $db->query("SELECT COUNT(*) FROM pistas WHERE estado = 'disponible'")->fetchColumn();

// 2. Obtener datos de las tablas
$reservas = $db->query("
    SELECT r.*, u.nombre as usuario, u.rol as rol_usuario, p.nombre_pista, p.tipo_deporte, m.nombre as monitor
    FROM reservas r
    JOIN usuarios u ON u.id_user = r.id_user
    JOIN pistas p ON p.id_pista = r.id_pista
    LEFT JOIN monitores m ON m.id_monitor = r.id_monitor
    ORDER BY r.fecha DESC, r.hora_inicio DESC
    LIMIT 50
")->fetchAll();

$pistas   = $db->query("SELECT * FROM pistas ORDER BY tipo_deporte")->fetchAll();
$usuarios = $db->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC")->fetchAll();

// Mensaje de sesión
$msg = "";
if(isset($_SESSION['admin_msg'])) {
    $msg = $_SESSION['admin_msg'];
    unset($_SESSION['admin_msg']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Admin – UniSport Booking</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="nav-barra">
  <a class="nav-barra-brand" href="admin.php">🏆 UniSport Booking – Admin</a>
  <div class="nav-links">
    <a href="#pistas">Pistas</a>
    <a href="#reservas">Reservas</a>
    <a href="#usuarios">Usuarios</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>

<div class="main-detalle">
<div class="container-detalle">

  <?php if ($msg != ""): ?>
    <div class="alerta success visible"><?= $msg ?></div>
  <?php endif; ?>

  <div class="grid" style="margin-bottom:32px;">
    <div class="kpi-caja">
      <div class="kpi-valor"><?= $num_usuarios ?></div>
      <div class="kpi-label">Usuarios registrados</div>
    </div>
    <div class="kpi-caja">
      <div class="kpi-valor"><?= $num_reservas ?></div>
      <div class="kpi-label">Reservas pagadas</div>
    </div>
    <div class="kpi-caja kpi-green">
      <div class="kpi-valor"><?= number_format($ingresos, 2) ?> €</div>
      <div class="kpi-label">Ingresos totales</div>
    </div>
    <div class="kpi-caja">
      <div class="kpi-valor"><?= $pistas_libres ?></div>
      <div class="kpi-label">Pistas disponibles</div>
    </div>
  </div>

  <h5 class="section-titulo" id="pistas">🏟️ Gestión de Pistas</h5>
  <div class="caja caja-detalle" style="margin-bottom:32px;">
    <table class="tabla-detalle">
        <thead>
          <tr><th>ID</th><th>Nombre</th><th>Deporte</th><th>Estado</th><th>Acción</th></tr>
        </thead>
        <tbody>
          <?php foreach ($pistas as $p): ?>
          <tr>
            <td><?= $p['id_pista'] ?></td>
            <td><?= $p['nombre_pista'] ?></td>
            <td><?= $p['tipo_deporte'] ?></td>
            <td>
              <?php if ($p['estado'] == 'disponible'): ?>
                <span class="badge-estado-ok">Disponible</span>
              <?php else: ?>
                <span class="badge-estado-mal">Mantenimiento</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" action="api_admin.php">
                <input type="hidden" name="action" value="toggle_pista">
                <input type="hidden" name="id_pista" value="<?= $p['id_pista'] ?>">
                <input type="hidden" name="estado" value="<?= $p['estado'] == 'disponible' ? 'mantenimiento' : 'disponible' ?>">
                <button type="submit" class="<?= $p['estado'] == 'disponible' ? 'btn-cancelar' : 'btn-reservar' ?>">
                  <?= $p['estado'] == 'disponible' ? 'Bloquear' : 'Activar' ?>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
    </table>
  </div>

  <h5 class="section-titulo" id="reservas">📋 Reservas</h5>
  <div class="caja caja-detalle" style="margin-bottom:32px;">
    <table class="tabla-detalle">
        <thead>
          <tr><th>Usuario</th><th>Pista</th><th>Fecha</th><th>Total</th><th>Acción</th></tr>
        </thead>
        <tbody>
          <?php foreach ($reservas as $r): ?>
          <tr>
            <td><?= $r['usuario'] ?> <small>(<?= $r['rol_usuario'] ?>)</small></td>
            <td><?= $r['nombre_pista'] ?></td>
            <td><?= $r['fecha'] ?> (<?= substr($r['hora_inicio'],0,5) ?>)</td>
            <td><?= number_format($r['precio_final'], 2) ?> €</td>
            <td>
              <?php if ($r['estado_pago'] != 'cancelada'): ?>
              <form method="POST" action="api_admin.php">
                <input type="hidden" name="action" value="cancelar_reserva">
                <input type="hidden" name="id_reserva" value="<?= $r['id_reserva'] ?>">
                <button type="submit" class="btn-cancelar">Cancelar</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
    </table>
  </div>

  <h5 class="section-titulo" id="usuarios">👥 Usuarios</h5>
  <div class="caja caja-detalle">
    <table class="tabla-detalle">
        <thead>
          <tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Saldo</th><th>Cambiar Rol</th></tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= $u['nombre'] ?></td>
            <td><?= $u['email'] ?></td>
            <td><?= $u['rol'] ?></td>
            <td><?= number_format($u['saldo'], 2) ?> €</td>
            <td>
              <?php if ($u['rol'] != 'admin'): ?>
              <form method="POST" action="api_admin.php">
                <input type="hidden" name="action" value="cambiar_rol">
                <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                <select name="nuevo_rol" onchange="this.form.submit()">
                  <option value="alumno" <?= $u['rol']=='alumno'?'selected':'' ?>>Alumno</option>
                  <option value="externo" <?= $u['rol']=='externo'?'selected':'' ?>>Externo</option>
                  <option value="entrenador" <?= $u['rol']=='entrenador'?'selected':'' ?>>Entrenador</option>
                </select>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
    </table>
  </div>

</div>
</div>

<footer>
  UniSport Booking System &nbsp;|&nbsp; &copy; <?= date('Y') ?>
</footer>

</body>
</html>