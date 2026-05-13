<?php
session_start();
require_once 'config.php';

// Si ya está logueado, mandarlo al inicio
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Si el usuario hace clic en el botón de entrar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($email == '' || $password == '') {
        $error = 'Por favor, rellena todos los campos.';
    } else {
        $db = getDB();
        
        // Buscar al usuario por email
        $sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";
        $consulta = $db->prepare($sql);
        $consulta->execute([$email]);
        $usuario = $consulta->fetch();

        // Si el usuario existe, revisamos la contraseña
        if ($usuario) {
            
            // Comprobar si la contraseña es correcta (ya sea hash o texto plano)
            if (password_verify($password, $usuario['password']) || $password == $usuario['password']) {
                
                // Guardar datos en la sesión
                $_SESSION['usuario_id']     = $usuario['id_user'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_email']  = $usuario['email'];
                $_SESSION['usuario_saldo']  = $usuario['saldo'];
                
                header('Location: index.php');
                exit;
                
            } else {
                $error = 'Email o contraseña incorrectos.';
            }
            
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-screen">
        <div class="login-card">
            <div class="login-header">🏆 UniSport Booking</div>
            <div class="login-body">
                <h5>Iniciar Sesión</h5>

                <?php if ($error): ?>
                    <div class="alerta error-login"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="ejemplo@correo.com">
                    </div>       
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" required placeholder="********">
                    </div>          
                    <button type="submit" class="btn-entrar">Entrar al Sistema</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>