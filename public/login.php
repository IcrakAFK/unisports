<?php
session_start();
require_once '../backend/config/config.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Rellena todos los campos.';
    } else {
        $db  = getDB();
        $st  = $db->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $usuario = $st->fetch();

        if ($usuario && $password === $usuario['password']) {
            $_SESSION['usuario_id'] = $usuario['id_user'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            if ($usuario['rol'] === 'admin') {
                header('Location: admin.php');
            } elseif ($usuario['rol'] === 'entrenador') {
                header('Location: entrenador.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Email o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – UniSport Booking</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="login-screen">
        <div class="login-caja">
            <div class="login-header" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:15px;">
                <img src="assets/logo.png" alt="UniSport Logo" class="logo-unisport">
                <span>UniSport Booking</span>
            </div>
<!-- INICIO SESION -->
            <div class="login-body">
                <h5>Iniciar Sesión</h5>
                <?php if ($error): ?>
                    <div class="alerta error-login"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <div class="form-grupo">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-grupo">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-entrar">Entrar</button>
                </form>
<!-- REGISTRO -->
                <p class="login-registro">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
            </div>
        </div>
    </div>
</body>
</html>
