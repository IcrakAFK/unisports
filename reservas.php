<?php
session_start();
require_once 'config.php';

// Verificación de sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$nombre = $_SESSION['usuario_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Reservas - UniSport</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<nav class="nav-barra">
    <a class="nav-barra-brand" href="index.php">🏆 UniSport Booking</a>
    <div class="nav-links">
        <a href="index.php">Inicio</a>
        <a href="reservas.php">Mis Reservas</a>
        <a href="perfil.php">Perfil</a>
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>
</nav>

<div class="main-detalle">
    <div class="container-detalle">
        <h5 class="section-titulo">📋 Historial Detallado de Reservas</h5>
        
        <div class="caja-detalle">
            <div class="table-responsive">
                <table class="tabla-detalle">
                    <thead>
                        <tr>
                            <th>Pista / Deporte</th>
                            <th>Fecha y Horario</th>
                            <th>Monitor</th>
                            <th>Material Extra</th>
                            <th>Total Pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpoTablaReservas">
                        <tr>
                            <td colspan="6" class="muted">Cargando detalles de reservas...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="btn-volver">
            <a href="index.php">Ir al Panel Principal</a>
        </div>
    </div>
</div>

<footer>
    TFG DAW: UniSport Booking &nbsp;|&nbsp; Copyright &copy; UniSport Booking
</footer>

<div id="toastContainer"></div>

</body>
</html>