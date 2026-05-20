<?php
session_start();
require_once 'config.php';

// CONTROL DE ACCESO CORREGIDO
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$db = getDB();

//CONTAR USUARIOS/RESERVAS/PISTAS
$num_usuarios  = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$num_reservas  = $db->query("SELECT COUNT(*) FROM reservas WHERE estado_pago = 'pagado'")->fetchColumn();
$pistas_libres = $db->query("SELECT COUNT(*) FROM pistas WHERE estado = 'disponible'")->fetchColumn();

//CARGAR TABLAS
$pistas   = $db->query("SELECT * FROM pistas")->fetchAll();
$usuarios = $db->query("SELECT * FROM usuarios")->fetchAll();
$reservas = $db->query("SELECT r.*, u.nombre AS nombre_usuario, p.nombre_pista 
                        FROM reservas r
                        JOIN usuarios u ON r.id_user = u.id_user
                        JOIN pistas p ON r.id_pista = p.id_pista
                        LIMIT 50")->fetchAll();

//MENSAJES ALERTA
$mensaje = $_SESSION['admin_mensaje'] ?? '';
unset($_SESSION['admin_mensaje']);
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
  <a class="nav-barra-brand" href="index.php">
  <img src="logo.png" alt="UniSport Logo" style="height: 50px; width: auto; vertical-align: middle; margin-right: 8px;">
  UniSport Booking - Admin
</a>
  <div class="nav-links">
    <a href="#pistas">Pistas</a>
    <a href="#reservas">Reservas</a>
    <a href="#usuarios">Usuarios</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>

<div class="main-detalle">
<div class="container-detalle">

  <?php if ($mensaje != ""): ?>
    <div class="alerta success visible"><?= $mensaje ?></div>
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
                <span class="etiqueta-estado-ok">Disponible</span>
              <?php else: ?>
                <span class="etiqueta-estado-mal">Mantenimiento</span>
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
          <tr>
            <th>Usuario</th>
            <th>Pista</th>
            <th>Fecha / Hora</th>
            <th>Total</th>
            <th>Accion</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservas as $r): ?>
          <tr>
            <td><?= $r['nombre_usuario'] ?></td>
            <td><?= $r['nombre_pista'] ?></td>
            <td><?= $r['fecha'] ?> <?= $r['hora_inicio'] ?></td>
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
          <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Saldo</th>
            <th>Cambiar Rol</th>
          </tr>
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
  UniSport Booking System | &copy; <?= date('Y') ?> Servicio de Deportes Universitarios |
  <a href="aviso-legal.php">Aviso Legal</a> ·
  <a href="privacidad.php">Privacidad</a> ·
  <a href="cookies.php">Cookies</a>
</footer>

</body>
</html>