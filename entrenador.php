<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); exit;
}

$pdo = getDB();
$fecha_hoy = date('Y-m-d');
$msg = '';

//DATOS MONITOR 
$consulta = $pdo->prepare('SELECT * FROM monitores WHERE id_user = ? LIMIT 1');
$consulta->execute([$_SESSION['usuario_id']]);
$monitor = $consulta->fetch();

if (!$monitor) {
    header('Location: index.php'); exit;
}

$id_monitor = (int)$monitor['id_monitor'];

//DISPONIBILIDAD MONITOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disponibilidad'])) {
    $disponible = (int)$_POST['disponibilidad'];
    $pdo->prepare('UPDATE monitores SET disponibilidad = ? WHERE id_monitor = ?')->execute([$disponible, $id_monitor]);
    $monitor['disponibilidad'] = $disponible;
    $msg = $disponible ? '✅ Estás disponible.' : '🔴 Estás no disponible.';
}

//AGENDA MONITOR
$fecha_fin = date('Y-m-d', time() + (7 * 24 * 60 * 60));
$consultaAgenda = $pdo->prepare('
    SELECT r.id_reserva, u.nombre AS alumno, p.nombre_pista AS pista,
           r.fecha, r.hora_inicio, r.hora_fin, r.precio_final, r.estado_pago
    FROM   reservas r
    JOIN   reserva_monitor rm ON rm.id_reserva = r.id_reserva
    JOIN   usuarios u  ON u.id_user  = r.id_user
    JOIN   pistas   p  ON p.id_pista = r.id_pista
    WHERE  rm.id_monitor = ? AND r.fecha BETWEEN ? AND ? AND r.estado_pago != "cancelada"
    ORDER BY r.fecha, r.hora_inicio
');
$consultaAgenda->execute([$id_monitor, $fecha_hoy, $fecha_fin]);
$agenda_completa = $consultaAgenda->fetchAll();

$clases_hoy = array_filter($agenda_completa, function($c) use ($fecha_hoy) { return $c['fecha'] === $fecha_hoy; });
$clases_semana = array_filter($agenda_completa, function($c) use ($fecha_hoy) { return $c['fecha'] !== $fecha_hoy; });
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Agenda – UniSport Booking</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="nav-barra">
  <a class="nav-barra-brand" href="index.php">
    <img src="logo.png" alt="UniSport Logo" style="height: 50px; width: auto; vertical-align: middle; margin-right: 8px;">
    UniSport Booking - Entrenador
  </a>
  <div class="nav-links">
    <a href="#hoy">Clases de Hoy</a>
    <a href="#semana">Próximos 7 Días</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>>

<div id="toastContainer"></div>

<div class="main">
  <aside class="lat-barra">
    <div class="caja">
      <div class="caja-header">MI PERFIL</div>
      <div class="caja-body">
        <div class="user-avatar" style="margin:0 auto 20px;">
            <i class="fa fa-user"></i>
          </div>
        <p><strong><?= $monitor['nombre'] ?></strong></p>
        <p class="muted"><?= $monitor['especialidad'] ?></p>
        <p class="muted"><?= number_format($monitor['precio_sesion'], 2) ?> € / sesión</p>
        <p style="margin-top: 10px;">
          Estado: <span class="<?= $monitor['disponibilidad'] ? 'disponible' : 'nodisponible' ?>">● <?= $monitor['disponibilidad'] ? 'Disponible' : 'No disponible' ?></span>
        </p>

        <form method="POST" style="margin-top: 14px;">
          <input type="hidden" name="disponibilidad" value="<?= $monitor['disponibilidad'] ? 0 : 1 ?>">
          <button type="submit" class="<?= $monitor['disponibilidad'] ? 'btn-disponible' : 'btn-nodisponible' ?>">
            <?= $monitor['disponibilidad'] ? '🔴 Marcarme no disponible' : '✅ Marcarme disponible' ?>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <div class="contenido">
    <?php if ($msg): ?>
      <div class="alerta success visible"><?= $msg ?></div>
    <?php endif; ?>

    <h5 class="section-titulo">📅 Clases de hoy — <?= $fecha_hoy ?></h5>
    <?php if (empty($clases_hoy)): ?>
      <p class="sin-clases">No tienes clases asignadas para hoy.</p>
    <?php else: ?>
      <div style="overflow-x: auto; margin-bottom: 28px;">
        <table class="agenda-tabla">
          <thead>
            <tr>
              <th>Horario</th>
              <th>Alumno</th>
              <th>Pista</th>
              <th>Importe</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clases_hoy as $c): ?>
              <tr>
                <td><?= $c['hora_inicio'] ?> – <?= $c['hora_fin'] ?></td>
                <td><?= $c['alumno'] ?></td>
                <td><?= $c['pista'] ?></td>
                <td><strong><?= number_format($c['precio_final'], 2) ?> €</strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <h5 class="section-titulo">🗓️ Próximos 7 días</h5>
    <?php if (empty($clases_semana)): ?>
      <p class="sin-clases">No hay más clases programadas esta semana.</p>
    <?php else: ?>
      <div style="overflow-x: auto;">
        <table class="agenda-tabla">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Horario</th>
              <th>Alumno</th>
              <th>Pista</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clases_semana as $c): ?>
              <tr>
                <td><?= $c['fecha'] ?></td>
                <td><?= $c['hora_inicio'] ?> – <?= $c['hora_fin'] ?></td>
                <td><?= $c['alumno'] ?></td>
                <td><?= $c['pista'] ?></td>
                <td><span class="etiqueta-<?= $c['estado_pago'] ?>"><?= $c['estado_pago'] ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
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