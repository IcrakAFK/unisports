<?php
session_start();
require_once 'config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Reservas – UniSport Booking</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="nav-barra">
<a class="nav-barra-brand" href="index.php">
  <img src="logo.png" alt="UniSport Logo" style="height: 50px; width: auto; vertical-align: middle; margin-right: 8px;">
  UniSport Booking
</a>
  <div class="nav-links">
    <a href="index.php">Inicio</a>
    <a href="reservas.php">Mis Reservas</a>
    <a href="perfil.php">Mi Perfil</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>

<div class="main-detalle">
  <div class="container-detalle">

    <h5 class="section-titulo">📋 Historial de Reservas</h5>

    <div class="caja caja-detalle">
      <div class="table-responsive">
        <table class="tabla-detalle">
          <thead>
            <tr>
              <th>#</th>
              <th>Pista / Deporte</th>
              <th>Fecha</th>
              <th>Horario</th>
              <th>Monitor</th>
              <th>Material</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody id="cuerpoTablaReservas">
            <tr><td colspan="9" style="text-align:center;color:#6c757d;">Cargando reservas...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<footer>
  UniSport Booking System | &copy; <?= date('Y') ?> Servicio de Deportes Universitarios |
  <a href="aviso-legal.php">Aviso Legal</a> ·
  <a href="privacidad.php">Privacidad</a> ·
  <a href="cookies.php">Cookies</a>
</footer>

<div id="toastContainer"></div>
<script src="app.js"></script>
</body>
</html>
