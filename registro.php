<?php
session_start();
require_once 'config.php';

// Si ya está logueado, al inicio
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre   = $_POST['nombre'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // Validaciones básicas
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Rellena todos los campos.';
    } elseif ($password != $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña es muy corta (mínimo 8).';
    } else {
        $db = getDB();

        // Mirar si el email ya existe
        $sql_email = $db->prepare("SELECT id_user FROM usuarios WHERE email = ?");
        $sql_email->execute([$email]);
        
        if ($sql_email->fetch()) {
            $error = 'Este email ya está en uso.';
        } else {
            // Lógica de rol simplificada
            $rol = 'externo';
            if (strpos($email, '@unisport.es') !== false) {
                $rol = 'alumno';
            }

            // Guardar usuario - CAMBIO: Usamos $password directamente en lugar de cifrarla
            $sql_insert = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
            $sql_insert->execute([$nombre, $email, $password, $rol]);

            $success = '¡Registro ok! <a href="login.php">Inicia sesión aquí</a>.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro – UniSport</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-screen">
        <div class="login-caja">
            <div class="login-header">🏆 UniSport Booking</div>
            <div class="login-body">
                <h5>Crear cuenta</h5>

                <?php if ($error != ""): ?>
                    <div class="alerta error-login"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($success != ""): ?>
                    <div class="alerta success-login"><?= $success ?></div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Nombre completo</label>
                            <input type="text" name="nombre" value="<?= $_POST['nombre'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email (@unisport.es para Alumno)</label>
                            <input type="email" name="email" value="<?= $_POST['email'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contraseña (mín. 8)</label>
                            <input type="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirmar contraseña</label>
                            <input type="password" name="confirm" required>
                        </div>
                        <button type="submit" class="btn-entrar">Registrarse</button>
                    </form>
                <?php endif; ?>

                <p class="login-registro">
                    ¿Ya tienes cuenta? <a href="login.php">Entra aquí</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>