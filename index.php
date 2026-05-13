<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$nombre = $_SESSION['usuario_nombre'];
$email  = $_SESSION['usuario_email'];
$saldo  = (float)$_SESSION['usuario_saldo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UniSport Booking</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a class="navbar-brand" href="#">🏆 UniSport Booking</a>
  <div class="nav-links">
    <a href="index.php">Inicio</a>
    <a href="reservas.php">Mis Reservas</a>
    <a href="perfil.php">Perfil</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>

<!-- LAYOUT PRINCIPAL -->
<div class="main">

  <!-- SIDEBAR -->
  <div class="sidebar" id="perfil">
    <div class="card">
      <div class="card-header">USUARIO</div>
      <div class="card-body">
        <div class="user-avatar">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
          </svg>
        </div>
        <p><strong>Nombre:</strong><br><?= $nombre ?></p>
        <p class="muted"><?= $email ?></p>
        <span class="badge-saldo">Saldo: <span id="saldoDisplay"><?= $saldo ?></span> €</span>
      </div>
    </div>
  </div>

  <!-- CONTENIDO PRINCIPAL -->
  <div class="contenido">

    <!-- PRÓXIMAS RESERVAS -->
    <h5 class="section-title" id="misReservas">📅 Mis Próximas Reservas</h5>
    <div class="grid" id="listaReservas">
      <p class="muted">Cargando reservas...</p>
    </div>

    <!-- PISTAS DISPONIBLES -->
    <h5 class="section-title">🏟️ Pistas Disponibles</h5>
    <div class="grid" id="listaPistas">
      <p class="muted">Cargando pistas...</p>
    </div>

  </div>
</div>

<!-- FOOTER -->
<footer>
  TFG DAW: UniSport Booking &nbsp;|&nbsp; Copyright &copy; UniSport Booking
</footer>

<!-- BOTÓN FLOTANTE (FAB) -->
<button class="fab" title="Nueva Reserva" onclick="abrirModal()">+</button>

<div class="modal-overlay" id="modalReserva">
  <div class="modal-box">

    <div class="modal-header">
      <h5>Nueva Reserva</h5>
      <button class="modal-cerrar" onclick="cerrarModal()">&times;</button>
    </div>

    <div class="modal-body">
      <div class="alerta" id="modalMsg"></div>

      <div class="form-group">
        <label>Pista</label>
        <select id="selectPista" onchange="actualizarResumen()"></select>
      </div>

      <div class="form-group">
        <label>Fecha</label>
        <input type="date" id="inputFecha">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Hora inicio</label>
          <input type="time" id="inputHoraIni" value="10:00" onchange="actualizarResumen()">
        </div>
        <div class="form-group">
          <label>Hora fin</label>
          <input type="time" id="inputHoraFin" value="11:00" onchange="actualizarResumen()">
        </div>
      </div>

      <hr class="separador-modal">

    <div class="form-group">
        <label>Monitor</label>
        <select id="selectMonitor" onchange="actualizarResumen()"></select>
    </div>

    <div class="form-row">
        <div class="form-group flex-2">
            <label>Material</label>
            <select id="selectMaterial" onchange="actualizarResumen()"></select>
        </div>
        <div class="form-group flex-1">
            <label>Cantidad</label>
            <input type="number" id="cantidadMaterial" value="1" min="1" max="10" onchange="actualizarResumen()">
        </div>
    </div>

    <div id="resumenPago" class="resumen-contenedor">
        <h6 class="resumen-titulo">Resumen del pago</h6>

        <div class="resumen-linea">
            <span>Precio Pista:</span>
            <span id="resumenPista">0.00 €</span>
        </div>

        <div class="resumen-linea">
            <span>Monitor:</span>
            <span id="resumenMonitor">0.00 €</span>
        </div>

        <div class="resumen-linea">
            <span>Material:</span>
            <span id="resumenMaterial">0.00 €</span>
        </div>

        <div class="resumen-linea total">
            <span>TOTAL:</span>
            <span id="resumenTotal">0.00 €</span>
        </div>
    </div>
    </div><!-- /modal-body -->

    <div class="modal-footer">
      <button class="btn-secundario" onclick="cerrarModal()">Cancelar</button>
      <button class="btn-confirmar" onclick="confirmarReserva()">✅ Confirmar Reserva</button>
    </div>

  </div><!-- /modal-box -->
</div><!-- /modal-overlay -->

<div id="toastContainer"></div>

<script src="app.js"></script>
</body>
</html>