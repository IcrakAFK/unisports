<?php
session_start();
require_once 'config.php';

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
        $sql = 'SELECT * FROM usuarios WHERE email = ? LIMIT 1';
        $st  = $db->prepare($sql);
        $st->execute([$email]);
        $usuario = $st->fetch();

        if ($usuario && $password === $usuario['password']) {
            $_SESSION['usuario_id']     = $usuario['id_user'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_saldo']  = $usuario['saldo'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            // Redirigir según rol
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
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-screen">
        <div class="login-caja">
            <div class="login-header">🏆 UniSport Booking</div>
            <div class="login-body">
                <h5>Iniciar Sesión</h5>

                <?php if ($error): ?>
                    <div class="alerta error-login"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?= $_POST['email'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn-entrar">Entrar</button>
                </form>

                <p class="login-registro">¿No tienes cuenta?
                    <a href="registro.php">Regístrate aquí</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
