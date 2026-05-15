<?php
// =============================================
//  UNISPORT BOOKING - config.php
// =============================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'unisport_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
    }
    return $pdo;
}

// Helper: redirigir si no hay sesión
function requireLogin(): void {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Helper: redirigir si el rol no es el esperado
function requireRol(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['usuario_rol'] ?? '', $roles, true)) {
        header('Location: index.php');
        exit;
    }
}
