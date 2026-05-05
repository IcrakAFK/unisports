<?php
// generar_hash.php
// Abre este archivo en: http://localhost/unisport/generar_hash.php
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "<b>Hash generado:</b><br><code>$hash</code><br><br>";
echo "<b>SQL para actualizar el usuario:</b><br>";
echo "<code>UPDATE usuarios SET password = '$hash' WHERE email = 'pepe.alumno@unisport.es';</code>";
?>