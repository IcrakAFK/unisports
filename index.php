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
  <link rel="stylesheet" href="styles.css">
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
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

<div class="main">

  <div class="lat-barra">
    <div class="caja">
      <div class="caja-header">USUARIO</div>
      <div class="caja-body">
        <div class="user-avatar" style="margin:0 auto 20px;">
            <i class="fa fa-user"></i>
          </div>
        <p><strong><?= $nombre ?></strong></p>
        <p class="muted"><?= $email ?></p>
        <span class="etiqueta-rol etiqueta-rol"><?= $rol ?></span>
        <br><br>
        <span class="etiqueta-saldo">Saldo: <span id="saldoDisplay"><?= $saldo ?></span> €</span>
      </div>
    </div>

    <div class="caja" style="margin-top: 20px;">
      <div class="caja-header">📅 SELECCIONAR FECHA</div>
      <div class="caja-body" style="padding: 10px;">
        <div id="calendarioFijo"></div>
      </div>
    </div>
  </div>

  <div class="contenido">

    <h5 class="section-titulo">📅 Mis Próximas Reservas</h5>
    <div class="grid" id="listaReservas">
      <p class="muted">Cargando reservas...</p>
    </div>

    <h5 class="section-titulo" id="tituloPistas">🏟️ Pistas Disponibles Hoy</h5>
    <div class="grid" id="listaPistas">
      <p class="muted">Cargando pistas...</p>
    </div>

  </div>
</div>

<footer>
  UniSport Booking System | &copy; <?= date('Y') ?> Servicio de Deportes Universitarios |
  <a href="aviso-legal.php">Aviso Legal</a> ·
  <a href="privacidad.php">Privacidad</a> ·
  <a href="cookies.php">Cookies</a>
</footer>

<button class="flotante" title="Nueva Reserva" onclick="abrirModal()">+</button>

<div class="modal-overlay" id="modalReserva">
  <div class="modal-box">
    <div class="modal-header">
      <h5>Nueva Reserva</h5>
      <button class="modal-cerrar" onclick="cerrarModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="alerta" id="modalMsg"></div>

      <div class="form-grupo">
        <label>Pista</label>
        <select id="selectPista" onchange="actualizarResumen()"></select>
      </div>

      <div class="form-grupo">
        <label>Fecha</label>
        <input type="text" id="inputFecha" placeholder="Selecciona una fecha..." readonly>
      </div>

      <div class="form-row">
        <div class="form-grupo">
          <label>Hora inicio</label>
          <input type="time" id="inputHoraInicio" value="10:00" min="08:00" max="23:00" onchange="actualizarResumen()">
        </div>
        <div class="form-grupo">
          <label>Hora fin</label>
          <input type="time" id="inputHoraFin" value="11:00" onchange="actualizarResumen()">
        </div>
      </div>

      <hr class="separador">

      <div class="form-grupo">
        <label>Monitor <span class="label-hint">(opcional)</span></label>
        <select id="selectMonitor" onchange="actualizarResumen()"></select>
      </div>

      <div class="form-row">
        <div class="form-grupo flex-2">
          <label>Material <span class="label-hint">(opcional)</span></label>
          <select id="selectMaterial" onchange="actualizarResumen()"></select>
        </div>
        <div class="form-grupo flex-1">
          <label>Cantidad</label>
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

<!-- Modal confirmación cancelar reserva -->
<div class="modal-overlay" id="modalCancelar">
  <div class="modal-box" style="max-width:380px;">
    <div class="modal-header">
      <h5>Cancelar Reserva</h5>
      <button class="modal-cerrar" onclick="cerrarModalCancelar()">&times;</button>
    </div>
    <div class="modal-body" style="text-align:center; padding:24px 18px;">
      <div style="font-size:42px; margin-bottom:12px;">⚠️</div>
      <p style="font-size:15px; margin-bottom:6px;"><strong>¿Seguro que quieres cancelar esta reserva?</strong></p>
      <p class="muted" style="font-size:13px;">Se te devolverá el importe completo a tu saldo.</p>
    </div>
    <div class="modal-footer">
      <button class="btn-secundario" onclick="cerrarModalCancelar()">No, volver</button>
      <button class="btn-cancelar" id="btnConfirmarCancelar" style="margin-top:0; padding:9px 16px;">Sí, cancelar</button>
    </div>
  </div>
</div>

<div id="toastContainer"></div>
<script src="app.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('calendarioFijo')) {
        flatpickr("#calendarioFijo", {
            inline: true, // Mantiene el calendario abierto permanentemente
            locale: "es",
            minDate: "today",
            dateFormat: "Y-m-d",
            monthSelectorType: "static", 
            yearSelectorType: "static", 
            
            onChange: function(selectedDates, dateStr, instance) {
                if (typeof actualizarPistasDisponibles === "function") {
                    actualizarPistasDisponibles(dateStr);
                }
            }
        });
    }
});
</script>
</body>
</html>