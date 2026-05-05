<?php
// =============================================
//  UNISPORT BOOKING - index.php  (Dashboard)
// =============================================
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$nombre = htmlspecialchars($_SESSION['usuario_nombre']);
$email  = htmlspecialchars($_SESSION['usuario_email']);
$saldo  = number_format((float)$_SESSION['usuario_saldo'], 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UniSport Booking</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg px-3 py-2">
  <a class="navbar-brand" href="#">🏆 UniSport Booking</a>
  <button class="navbar-toggler border-light" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navMenu">
    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
      <li class="nav-item"><a class="nav-link active" href="index.php">Inicio</a></li>
      <li class="nav-item"><a class="nav-link" href="#misReservas">Mis Reservas</a></li>
      <li class="nav-item"><a class="nav-link" href="#perfil">Perfil</a></li>
      <li class="nav-item ms-lg-2">
        <a href="logout.php" class="btn btn-logout btn-sm mt-2 mt-lg-0">Cerrar Sesión</a>
      </li>
    </ul>
  </div>
</nav>

<!-- MAIN -->
<div class="container-fluid py-4 px-3 px-md-4">
  <div class="row g-4">

    <!-- SIDEBAR -->
    <div class="col-12 col-md-3" id="perfil">
      <div class="card sidebar-card shadow-sm">
        <div class="card-header">USUARIO</div>
        <div class="card-body text-center">
          <div class="user-avatar">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
          </div>
          <p class="mb-1"><strong>Nombre:</strong><br><?= $nombre ?></p>
          <p class="mb-2 text-muted small"><?= $email ?></p>
          <span class="badge badge-saldo">Saldo: <span id="saldoDisplay"><?= $saldo ?></span> €</span>
        </div>
      </div>
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <div class="col-12 col-md-9">

      <!-- PRÓXIMAS RESERVAS -->
      <h5 class="section-title" id="misReservas">📅 Mis Próximas Reservas</h5>
      <div class="row g-3 mb-4" id="listaReservas">
        <div class="col-12 text-muted">Cargando reservas...</div>
      </div>

      <!-- PISTAS DISPONIBLES -->
      <h5 class="section-title">🏟️ Pistas Disponibles</h5>
      <div class="row g-3" id="listaPistas">
        <div class="col-12 text-muted">Cargando pistas...</div>
      </div>

    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="text-center text-muted py-3 small border-top mt-4">
  TFG DAW: UniSport Booking &nbsp;|&nbsp; Copyright &copy; UniSport Booking
</footer>

<!-- FAB -->
<button class="fab" title="Nueva Reserva" data-bs-toggle="modal" data-bs-target="#modalReserva">+</button>

<!-- ── MODAL: NUEVA RESERVA ────────────────────────── -->
<div class="modal fade" id="modalReserva" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:0">
      <div class="modal-header" style="background:#0056b3;color:#fff;border-radius:0">
        <h5 class="modal-title fw-bold">Nueva Reserva</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="modalMsg" class="alert d-none"></div>
        <div class="mb-3">
          <label class="form-label fw-bold">Pista</label>
          <select id="selectPista" class="form-select" style="border-radius:0"></select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Fecha</label>
          <input type="date" id="inputFecha" class="form-control" style="border-radius:0">
        </div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label fw-bold">Hora inicio</label>
            <input type="time" id="inputHoraIni" class="form-control" style="border-radius:0" value="10:00">
          </div>
          <div class="col-6">
            <label class="form-label fw-bold">Hora fin</label>
            <input type="time" id="inputHoraFin" class="form-control" style="border-radius:0" value="11:00">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn" style="border-radius:0;border:1px solid #ccc" data-bs-dismiss="modal">Cancelar</button>
        <button id="btnConfirmarReserva" class="btn btn-reservar px-4">Confirmar Reserva</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="app.js"></script>
</body>
</html>
