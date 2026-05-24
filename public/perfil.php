<?php
session_start();
require_once '../backend/config/config.php';
requireLogin();

$db  = getDB();
$consulta  = $db->prepare('SELECT * FROM usuarios 
                           WHERE id_user = ?
                         ');
$consulta->execute([$_SESSION['usuario_id']]);
$user = $consulta->fetch();

// Ticket de reserva recién confirmada
$ticket = null;
if (isset($_GET['ticket'])) {
    $ticket = [
        'id'          => (int) $_GET['ticket'],
        'pista'       => htmlspecialchars($_GET['pista'] ?? ''),
        'deporte'     => htmlspecialchars($_GET['deporte'] ?? ''),
        'fecha'       => htmlspecialchars($_GET['fecha'] ?? ''),
        'hora_inicio' => htmlspecialchars($_GET['hora_inicio'] ?? ''),
        'hora_fin'    => htmlspecialchars($_GET['hora_fin'] ?? ''),
        'total'       => htmlspecialchars($_GET['total'] ?? ''),
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Perfil – UniSport Booking</title>
  <link rel="stylesheet" href="assets/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<nav class="nav-barra">
  <a class="nav-barra-brand" href="index.php">
    <img src="assets/logo.png" alt="UniSport Logo" style="height: 50px; width: auto; vertical-align: middle; margin-right: 8px;">
    UniSport Booking
  </a>
  <div class="nav-links">
    <a href="index.php">Inicio</a>
    <a href="reservas.php">Mis Reservas</a>
    <a href="perfil.php">Mi Perfil</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>

<div class="main">

  <div class="contenido perfil-container">
    <div class="perfil-wrapper">
      
      <div class="caja">
        <div class="caja-header">MI PERFIL</div>
        <div class="caja-body">
          <div class="user-avatar" style="margin:0 auto 20px;">
            <i class="fa fa-user"></i>
          </div>

          <table class="tabla-perfil">
            <tr>
              <td class="label">Nombre</td>
              <td><?= $user['nombre'] ?></td>
            </tr>
            <tr>
              <td class="label">Email</td>
              <td><?= $user['email'] ?></td>
            </tr>
            <tr>
              <td class="label">Rol</td>
              <td><?= $user['rol'] ?></td>
            </tr>
          </table>

          <div class="btn-volver">
            <a href="index.php"><- Volver al inicio</a>
          </div>
        </div>
      </div>

      <?php if ($ticket): ?>
      <div class="caja" style="margin-top: 24px;">
        <div class="caja-header" style="background:#28a745;">✅ RESERVA CONFIRMADA – TICKET #<?= $ticket['id'] ?></div>
        <div class="caja-body" style="text-align:left;">
          <table class="tabla-perfil">
            <tr><td class="label">Pista</td><td><?= $ticket['pista'] ?> <span class="muted">(<?= $ticket['deporte'] ?>)</span></td></tr>
            <tr><td class="label">Fecha</td><td><?= $ticket['fecha'] ?></td></tr>
            <tr><td class="label">Horario</td><td><?= $ticket['hora_inicio'] ?> – <?= $ticket['hora_fin'] ?></td></tr>
            <tr><td class="label">Total pagado</td><td><strong><?= $ticket['total'] ?> €</strong></td></tr>
          </table>
          <div class="btn-volver" style="margin-top:14px;">
            <a href="reservas.php">Ver todas mis reservas</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

</div>

<footer>
  UniSport Booking System | &copy; <?= date('Y') ?> Servicio de Deportes Universitarios |
  <a href="pages/aviso-legal.php">Aviso Legal</a> ·
  <a href="pages/privacidad.php">Privacidad</a> ·
  <a href="pages/cookies.php">Cookies</a>
</footer>

</body>
</html>