<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id_user = ?');
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Perfil – UniSport Booking</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="navbar">
  <a class="navbar-brand" href="#">🏆 UniSport Booking</a>
  <div class="nav-links">
    <a href="index.php">Inicio</a>
    <a href="reservas.php">Mis Reservas</a>
    <a href="perfil.php">Perfil</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>

<div class="main">
  <div class="perfil-wrapper perfil-container">

    <div class="card">
      <div class="card-header">MI PERFIL</div>
      <div class="card-body">

        <div class="user-avatar">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
          </svg>
        </div>

        <table class="tabla-perfil">
          <tr>
            <td class="label">ID</td>
            <td><?= $user['id_user'] ?></td>
          </tr>
          <tr>
            <td class="label">Nombre</td>
            <td><?=$user['nombre'] ?></td>
          </tr>
          <tr>
            <td class="label">Email</td>
            <td><?= $user['email'] ?></td>
          </tr>
          <tr>
            <td class="label">Rol</td>
            <td><?= $user['rol'] ?></td>
          </tr>
          <tr>
            <td class="label">Saldo</td>
            <td>
              <span class="badge-saldo"><?= $user['saldo'] ?> €</span>
            </td>
          </tr>
        </table>

        <div class="btn-volver">
          <a href="index.php">← Volver al inicio</a>
        </div>

      </div>
    </div>

  </div>
</div>

<footer>
  TFG DAW: UniSport Booking &nbsp;|&nbsp; Copyright &copy; UniSport Booking
</footer>

</body>
</html>