<?php
session_start();
require_once '../backend/config/config.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Rellena todos los campos.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña es muy corta (mínimo 8 caracteres).';
    } else {
        $db = getDB();
        $check = $db->prepare('SELECT id_user FROM usuarios WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'Este email ya está en uso.';
        } else {
            $rol = str_ends_with($email, '@unisport.es') ? 'alumno' : 'externo';
            $db->prepare('INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)')
               ->execute([$nombre, $email, $password, $rol]);
            $success = 'Registrado correctamente. <a href="login.php">Inicia sesión aquí</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro – UniSport</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="login-screen">
        <div class="login-caja">
            <div class="login-header" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:15px;">
                <img src="assets/logo.png" alt="UniSport Logo" class="logo-unisport">
                <span>UniSport Booking</span>
            </div>
<!-- CREAR CUENTA -->
            <div class="login-body">
                <h5>Crear cuenta</h5>
                <?php if ($error): ?>
                    <div class="alerta error-login"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alerta success-login"><?= $success ?></div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-grupo">
                            <label>Nombre completo</label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="form-grupo">
                            <label>Email (@unisport.es para alumnos)</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-grupo">
                            <label>Contraseña (mín. 8 caracteres)</label>
                            <input type="password" name="password" required>
                        </div>
                        <div class="form-grupo">
                            <label>Confirmar contraseña</label>
                            <input type="password" name="confirm" required>
                        </div>
                        <button type="submit" class="btn-entrar">Registrarse</button>
                    </form>
                <?php endif; ?>
<!-- INICIO SESION -->
                <p class="login-registro">¿Ya tienes cuenta? <a href="login.php">Entra aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>
