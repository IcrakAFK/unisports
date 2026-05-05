<?php
session_start();
require 'config.php';

if (isset($_SESSION['usuario_id'])) header('Location: index.php');

$error = '';
if ($_POST) {
    $email = $_POST['email'] ?? '';
    $pass  = $_POST['password'] ?? '';

    $stmt = getDB()->prepare('SELECT * FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && (password_verify($pass, $u['password']) || $pass === $u['password'])) {
        $_SESSION = array_merge($_SESSION, [
            'usuario_id'     => $u['id'],
            'usuario_nombre' => $u['nombre'],
            'usuario_email'  => $u['email'],
            'usuario_saldo'  => $u['saldo']
        ]);
        header('Location: index.php');
        exit;
    }
    $error = 'Datos incorrectos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login – UniSport</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-body">

    <div class="login-container">
        <h2 class="login-title">🏆 UniSport</h2>
        <hr class="separator">
        
        <?php if ($error): ?>
            <div class="error-msg"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" class="input-field" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="input-field" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="login-button">Entrar</button>
        </form>
    </div>

</body>
</html>
<style>
* {
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

/* Fondo de pantalla centrado */
.login-body {
    background-color: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    margin: 0;
}

/* La tarjeta (Card) */
.login-container {
    background: white;
    width: 500px;
    padding: 3rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 0; /* Bordes rectos */
}

/* Título */
.login-title {
    text-align: center;
    color: #007bff;
    margin-bottom: 1.5rem;
    font-weight: bold;
}

.separator {
    border: 0;
    border-top: 1px solid #ddd;
    margin-bottom: 2rem;
}

/* Mensaje de error */
.error-msg {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    border: 1px solid #f5c6cb;
}

/* Inputs */
.form-group {
    margin-bottom: 1.5rem;
}

.input-field {
    width: 100%;
    padding: 12px 15px;
    font-size: 1.1rem;
    border: 1px solid #ccc;
    border-radius: 0; /* Bordes rectos */
    outline: none;
}

.input-field:focus {
    border-color: #007bff;
}

/* Botón */
.login-button {
    width: 100%;
    padding: 12px;
    background-color: #007bff;
    color: white;
    border: none;
    font-size: 1.2rem;
    font-weight: bold;
    cursor: pointer;
    border-radius: 0; /* Bordes rectos */
    transition: background 0.3s;
}

.login-button:hover {
    background-color: #0056b3;
}
</style>