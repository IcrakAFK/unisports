<?php
session_start();
require_once '../backend/config/config.php';

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$db = getDB();

//NUMERO USERS/RESERVAS/PISTAS
$num_usuarios  = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$num_reservas  = $db->query("SELECT COUNT(*) FROM reservas WHERE estado_pago = 'pagado'")->fetchColumn();
$pistas_libres = $db->query("SELECT COUNT(*) FROM pistas WHERE estado = 'disponible'")->fetchColumn();

$pistas   = $db->query("SELECT * FROM pistas")->fetchAll();
$usuarios = $db->query("SELECT * FROM usuarios")->fetchAll();
$reservas = $db->query("SELECT r.*, u.nombre AS nombre_usuario, p.nombre_pista
                        FROM reservas r
                        JOIN usuarios u ON r.id_user = u.id_user
                        JOIN pistas p ON r.id_pista = p.id_pista
                        ORDER BY r.fecha DESC
                        LIMIT 50")->fetchAll();

$mensaje = $_SESSION['admin_mensaje'] ?? '';
unset($_SESSION['admin_mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Admin – UniSport Booking</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<nav class="nav-barra">
  <a class="nav-barra-brand" href="index.php">
    <img src="assets/logo.png" alt="UniSport Logo" class="logo-unisport">
    UniSport Booking - Admin
  </a>
  <div class="nav-links">
    <a href="#pistas">Pistas</a>
    <a href="#reservas">Reservas</a>
    <a href="#usuarios">Usuarios</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>

<!-- PANEL PRINCIPAL -->
<div class="main-detalle">
<div class="container-detalle">

  <?php if ($mensaje): ?>
    <div class="alerta success visible"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <div class="grid" style="margin-bottom:32px;">
    <div class="indicador-caja">
      <div class="indicador-valor"><?= $num_usuarios ?></div>
      <div class="indicador-label">Usuarios registrados</div>
    </div>
    <div class="indicador-caja">
      <div class="indicador-valor"><?= $num_reservas ?></div>
      <div class="indicador-label">Reservas pagadas</div>
    </div>
    <div class="indicador-caja">
      <div class="indicador-valor"><?= $pistas_libres ?></div>
      <div class="indicador-label">Pistas disponibles</div>
    </div>
  </div>

  <!-- GESTION DE PISTAS -->
  <h5 class="section-titulo" id="pistas">🏟️ Gestión de Pistas</h5>
  <div class="caja caja-detalle" style="margin-bottom:32px;">
    <table class="tabla-detalle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Deporte</th>
          <th>Precio/hora</th>
          <th>Estado</th>
          <th>Accion</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pistas as $p): ?>
        <tr>
          <td><?= $p['id_pista'] ?></td>
          <td><?= htmlspecialchars($p['nombre_pista']) ?></td>
          <td><?= htmlspecialchars($p['tipo_deporte']) ?></td>
          <td><?= number_format($p['precio_hora'], 2) ?> €</td>
          <td>
            <?php if ($p['estado'] == 'disponible'): ?>
              <span class="etiqueta-estado-bien">Disponible</span>
            <?php else: ?>
              <span class="etiqueta-estado-mal">Mantenimiento</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" action="../backend/api/api_admin.php">
              <input type="hidden" name="action" value="estado_pista">
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

    <!-- GESTION DE RESERVAS -->
  <h5 class="section-titulo" id="reservas">📋 Reservas</h5>
  <div class="caja caja-detalle" style="margin-bottom:32px;">
    <table class="tabla-detalle">
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Pista</th>
          <th>Fecha / Hora</th>
          <th>Total</th>
          <th>Estado</th>
          <th>Accion</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reservas as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['nombre_usuario']) ?></td>
          <td><?= htmlspecialchars($r['nombre_pista']) ?></td>
          <td><?= $r['fecha'] ?> <?= $r['hora_inicio'] ?></td>
          <td><?= number_format($r['precio_final'], 2) ?> €</td>
          <td><?= $r['estado_pago'] ?></td>
          <td>
            <?php if ($r['estado_pago'] != 'cancelada'): ?>
            <form method="POST" action="../backend/api/api_admin.php">
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

    <!-- GESTION DE USUARIOS -->
  <h5 class="section-titulo" id="usuarios">👥 Usuarios</h5>
  <div class="caja caja-detalle">
    <table class="tabla-detalle">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Cambiar Rol</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['nombre']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= $u['rol'] ?></td>
          <td>
            <?php if ($u['rol'] != 'admin'): ?>
            <form method="POST" action="../backend/api/api_admin.php">
              <input type="hidden" name="action" value="cambiar_rol">
              <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
              <select name="nuevo_rol" onchange="this.form.submit()">
                <option value="alumno" <?= $u['rol']=='alumno' ?'selected':'' ?>>Alumno</option>
                <option value="externo" <?= $u['rol']=='externo' ?'selected':'' ?>>Externo</option>
                <option value="entrenador" <?= $u['rol']=='entrenador' ?'selected':'' ?>>Entrenador</option>
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
  UniSport Booking System | &copy; <?= date('Y') ?> Servicio de Deportes Universitarios |
  <a href="paginas/aviso-legal.php">Aviso Legal</a> ·
  <a href="paginas/privacidad.php">Privacidad</a> ·
  <a href="paginas/cookies.php">Cookies</a>
</footer>

</body>
</html>
