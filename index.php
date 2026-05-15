<?php
session_start();
require_once 'config.php';
requireLogin();

if (!isset($_SESSION['usuario_rol'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SESSION['usuario_rol'] === 'admin') {
    header('Location: admin.php'); exit;
}
if ($_SESSION['usuario_rol'] === 'entrenador') {
    header('Location: entrenador.php'); exit;
}

$nombre = $_SESSION['usuario_nombre'] ?? '';
$email  = $_SESSION['usuario_email']  ?? '';
$saldo  = number_format((float)($_SESSION['usuario_saldo'] ?? 0), 2);
$rol    = $_SESSION['usuario_rol']    ?? 'alumno';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UniSport Booking – Inicio</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="nav-barra">
  <a class="nav-barra-brand" href="index.php">🏆 UniSport Booking</a>
  <div class="nav-links">
    <a href="index.php">Inicio</a>
    <a href="reservas.php">Mis Reservas</a>
    <a href="perfil.php">Mi Perfil</a>
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </div>
</nav>

<!-- LAYOUT PRINCIPAL -->
<div class="main">

  <!-- SIDEBAR -->
  <div class="lat-barra">
    <div class="caja">
      <div class="caja-header">USUARIO</div>
      <div class="caja-body">
        <div class="user-avatar">
          <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
          </svg>
        </div>
        <p><strong><?= $nombre ?></strong></p>
        <p class="muted"><?= $email ?></p>
        <?= $rol ?>
        <br><br>
        <span class="badge-saldo">Saldo: <span id="saldoDisplay"><?= $saldo ?></span> €</span>
      </div>
    </div>
  </div>

  <!-- CONTENIDO PRINCIPAL -->
  <div class="contenido">

    <!-- PRÓXIMAS RESERVAS -->
    <h5 class="section-titulo">📅 Mis Próximas Reservas</h5>
    <div class="grid" id="listaReservas">
      <p class="muted">Cargando reservas...</p>
    </div>

    <!-- PISTAS DISPONIBLES -->
    <h5 class="section-titulo">🏟️ Pistas Disponibles Hoy</h5>
    <div class="grid" id="listaPistas">
      <p class="muted">Cargando pistas...</p>
    </div>

  </div>
</div>

<!-- FOOTER -->
<footer>
  UniSport Booking System &nbsp;|&nbsp; &copy; <?= date('Y') ?> Servicio de Deportes Universitarios &nbsp;|&nbsp;
</footer>

<!-- MODAL RESERVA -->
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
        <input type="date" id="inputFecha" onchange="actualizarResumen()">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Hora inicio</label>
          <input type="time" id="inputHoraInicio" value="10:00" min="08:00" max="23:00" onchange="actualizarResumen()">
        </div>
        <div class="form-group">
          <label>Hora fin</label>
          <input type="time" id="inputHoraFin" value="11:00" min="09:00" max="00:00" onchange="actualizarResumen()">
        </div>
      </div>

      <hr class="separador">

      <div class="form-group">
        <label>Monitor <span class="label-hint">(opcional)</span></label>
        <select id="selectMonitor" onchange="actualizarResumen()"></select>
      </div>

      <div class="form-row">
        <div class="form-group flex-2">
          <label>Material <span class="label-hint">(opcional)</span></label>
          <select id="selectMaterial" onchange="actualizarResumen()"></select>
        </div>
        <div class="form-group flex-1">
          <label>Cant.</label>
          <input type="number" id="cantidadMaterial" value="1" min="1" max="10" onchange="actualizarResumen()">
        </div>
      </div>

      <div id="resumenPago" class="resumen-contenedor">
        <h6 class="resumen-titulo">Resumen del pago</h6>
        <div class="resumen-linea"><span>Precio Pista:</span><span id="resumenPista">0.00 €</span></div>
        <div class="resumen-linea"><span>Monitor:</span><span id="resumenMonitor">0.00 €</span></div>
        <div class="resumen-linea"><span>Material:</span><span id="resumenMaterial">0.00 €</span></div>
        <div class="resumen-linea total"><span>TOTAL:</span><span id="resumenTotal">0.00 €</span></div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn-secundario" onclick="cerrarModal()">Cancelar</button>
      <button class="btn-confirmar" onclick="confirmarReserva()">Confirmar Reserva</button>
    </div>
  </div>
</div>

<div id="toastContainer"></div>

<script src="app.js"></script>
</body>
</html>
