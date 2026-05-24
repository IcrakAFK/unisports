<?php
session_start();
require_once '../backend/config/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Reservas – UniSport Booking</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

<!-- BARRA NAVEGACION -->
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
  <a href="pages/aviso-legal.php">Aviso Legal</a> ·
  <a href="pages/privacidad.php">Privacidad</a> ·
  <a href="pages/cookies.php">Cookies</a>
</footer>

<!-- MODAL CANCELAR RESERVA -->
<div class="modal-overlay" id="modalCancelar">
  <div class="modal-box" style="max-width:380px;">
    <div class="modal-header">
      <h5>Cancelar Reserva</h5>
      <button class="modal-cerrar" onclick="cerrarModalCancelar()">&times;</button>
    </div>
    <div class="modal-body" style="text-align:center; padding:24px 18px;">
      <div style="font-size:42px; margin-bottom:12px;">⚠️</div>
      <p style="font-size:15px; margin-bottom:6px;"><strong>¿Seguro que quieres cancelar esta reserva?</strong></p>
    </div>
    <div class="modal-footer">
      <button class="btn-secundario" onclick="cerrarModalCancelar()">No, volver</button>
      <button class="btn-cancelar" id="btnConfirmarCancelar" style="margin-top:0; padding:9px 16px;">Sí, cancelar</button>
    </div>
  </div>
</div>

<div id="mensajeTemporal"></div>
<script src="assets/app.js"></script>
</body>
</html>